<?php
namespace api\ai\controller;

/** PNG 文件元数据中的 AIGC 标识处理；不依赖 ExifTool。 */
final class AigcPngMetadata
{
    private const SIGNATURE = "\x89PNG\r\n\x1a\n";
    private const XMP_KEY = 'XML:com.adobe.xmp';

    public static function replace(string $png, string $producer, string $propagator,
                                   string $prefix = 'TC260',
                                   string $namespace = 'http://www.tc260.org.cn/ns/AIGC/1.0/',
                                   string $label = '1',
                                   string $idPrefix = 'wb',
                                   ?string &$produceIdOut = null): string
    {
        if (substr($png, 0, 8) !== self::SIGNATURE) {
            throw new \RuntimeException('隐式标识仅支持 PNG 图片');
        }
        if ($producer === '' || $propagator === '' || $label === '' ||
            !preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $prefix) ||
            !preg_match('/^[A-Za-z0-9_-]{1,32}$/D', $idPrefix) || $namespace === '') {
            throw new \InvalidArgumentException('AIGC 标识配置无效');
        }

        $stamp = gmdate('YmdHis');
        // 无数据库无法严格证明永不重复；随机数提供实际可用的唯一性。
        $produceId = $idPrefix . '-' . $stamp . '-' . bin2hex(random_bytes(16));
        $produceIdOut = $produceId;
        // GB 45438-2025 附录 E：生成服务提供者首次写入标识时，
        // 内容传播编号应与内容制作编号一致。
        $propagateId = $produceId;
        $fields = [
            'Label' => $label,
            'ContentProducer' => $producer,
            'ProduceID' => $produceId,
            'ReservedCode1' => '',
            'ContentPropagator' => $propagator,
            'PropagateID' => $propagateId,
            'ReservedCode2' => '',
        ];
        $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $xml = '<?xpacket begin="' . "\xEF\xBB\xBF" . '" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            . '<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            . '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            . '<rdf:Description rdf:about="" xmlns:' . $prefix . '="'
            . htmlspecialchars($namespace, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '">'
            . '<' . $prefix . ':AIGC>' . htmlspecialchars($json, ENT_XML1, 'UTF-8')
            . '</' . $prefix . ':AIGC></rdf:Description></rdf:RDF></x:xmpmeta>'
            . '<?xpacket end="w"?>';
        $newXmp = self::chunk('iTXt', self::XMP_KEY . "\0\0\0\0\0" . $xml);

        $result = self::SIGNATURE;
        $position = 8;
        $size = strlen($png);
        $foundEnd = false;
        $inserted = false;
        while ($position + 12 <= $size) {
            $length = unpack('N', substr($png, $position, 4))[1];
            if ($length > $size - $position - 12) {
                throw new \RuntimeException('PNG 数据块长度无效');
            }
            $type = substr($png, $position + 4, 4);
            $data = substr($png, $position + 8, $length);
            $whole = substr($png, $position, $length + 12);
            $position += $length + 12;
            if (hash('crc32b', $type . $data, true) !== substr($whole, -4)) {
                throw new \RuntimeException('PNG 数据块 CRC 校验失败');
            }
            if ($type === 'IEND') {
                if (!$inserted) {
                    throw new \RuntimeException('PNG 缺少 IDAT 图片数据');
                }
                $result .= $whole;
                $foundEnd = true;
                break;
            }
            if ($type === 'IDAT' && !$inserted) {
                $result .= $newXmp;
                $inserted = true;
            }
            if (in_array($type, ['tEXt', 'zTXt', 'iTXt'], true)) {
                $replacement = self::filterTextChunk($type, $data);
                if ($replacement === null) {
                    continue;
                }
                $result .= $replacement === $data ? $whole : self::chunk($type, $replacement);
                continue;
            }
            if ($type === 'eXIf' && self::containsAigc($data)) {
                // EXIF 目录可能有交叉引用；不能安全改写时终止，防止旧标识残留。
                throw new \RuntimeException('原图的 EXIF 含 AIGC 标识，无法安全替换');
            }
            $result .= $whole;
        }
        if (!$foundEnd || $position !== $size) {
            throw new \RuntimeException('PNG 缺少有效的 IEND 或存在尾随数据');
        }
        return $result;
    }

    private static function filterTextChunk(string $type, string $data): ?string
    {
        $zero = strpos($data, "\0");
        if ($zero === false) {
            throw new \RuntimeException('PNG 文本数据块无效');
        }
        $key = substr($data, 0, $zero);
        if (strcasecmp($key, 'AIGC') === 0) {
            return null;
        }
        if (strcasecmp($key, self::XMP_KEY) !== 0) {
            if (self::containsAigc($data)) {
                return null;
            }
            return $data;
        }

        $compressed = false;
        $body = substr($data, $zero + 1);
        if ($type === 'iTXt') {
            if (strlen($body) < 4 || !in_array(ord($body[0]), [0, 1], true) || ord($body[1]) !== 0) {
                throw new \RuntimeException('PNG iTXt 格式无效');
            }
            $compressed = ord($body[0]) === 1;
            $languageEnd = strpos($body, "\0", 2);
            $translatedEnd = $languageEnd === false ? false : strpos($body, "\0", $languageEnd + 1);
            if ($translatedEnd === false) {
                throw new \RuntimeException('PNG iTXt 格式无效');
            }
            $body = substr($body, $translatedEnd + 1);
        } elseif ($type === 'zTXt') {
            if ($body === '' || ord($body[0]) !== 0) {
                throw new \RuntimeException('PNG zTXt 格式无效');
            }
            $compressed = true;
            $body = substr($body, 1);
        }
        $xml = $compressed ? @gzuncompress($body) : $body;
        if ($xml === false || !class_exists('\DOMDocument')) {
            throw new \RuntimeException('无法解析已有 XMP 元数据；请确认 PHP DOM 和 zlib 扩展可用');
        }
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('原图 XMP 格式无效，无法安全替换');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*[local-name()="AIGC"] | //@*[local-name()="AIGC"]') as $node) {
            if ($node instanceof \DOMAttr) {
                $node->ownerElement->removeAttributeNode($node);
            } else {
                $node->parentNode->removeChild($node);
            }
        }
        foreach ($xpath->query('//*[local-name()="Description"]') as $description) {
            $hasOtherProperty = false;
            foreach ($description->attributes as $attribute) {
                if ($attribute->localName !== 'about') {
                    $hasOtherProperty = true;
                    break;
                }
            }
            if (!$hasOtherProperty && !$description->hasChildNodes()) {
                $description->parentNode->removeChild($description);
            }
        }
        if ($xpath->query('//*[local-name()="Description"]')->length === 0) {
            return null;
        }
        $clean = $dom->saveXML();
        return self::XMP_KEY . "\0\0\0\0\0" . $clean;
    }

    private static function containsAigc(string $data): bool
    {
        return stripos($data, 'AIGC') !== false ||
            stripos($data, 'ContentProducer') !== false ||
            stripos($data, 'ProduceID') !== false;
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . hash('crc32b', $type . $data, true);
    }
}
