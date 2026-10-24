<?php
// +----------------------------------------------------------------------
// | 火山引擎 API 签名服务
// +----------------------------------------------------------------------
namespace api\ai\service\external;

class VolcEngineSignService
{
    private $accessKeyId;
    private $secretAccessKey;
    private $region;
    private $service;
    private $host;

    public function __construct(
        string $accessKeyId,
        string $secretAccessKey,
        string $region = 'cn-north-1',
        string $service = 'cv',
        string $host = 'visual.volcengineapi.com'
    ) {
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->region = $region;
        $this->service = $service;
        $this->host = $host;
    }

    /**
     * 调用火山引擎 API（带完整签名计算）
     *
     * @param string $action API动作名称
     * @param string $version API版本
     * @param array $payload 请求体数据
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     * @throws \Exception
     */
    public function callApi(string $action, string $version, array $payload): array
    {
        $contentType = 'application/json';
        $method = 'POST';
        $path = '/';

        // 准备请求body
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 构建query参数并排序
        $query = [
            'Action' => $action,
            'Version' => $version
        ];
        ksort($query);

        // 准备签名所需的时间和内容哈希
        $xDate = gmdate('Ymd\THis\Z');
        $shortXDate = substr($xDate, 0, 8);
        $xContentSha256 = hash('sha256', $body);

        // 计算签名
        $signedHeaderStr = 'content-type;host;x-content-sha256;x-date';

        $canonicalRequestStr = join("\n", [
            $method,
            $path,
            http_build_query($query),
            join("\n", [
                'content-type:' . $contentType,
                'host:' . $this->host,
                'x-content-sha256:' . $xContentSha256,
                'x-date:' . $xDate
            ]),
            '',
            $signedHeaderStr,
            $xContentSha256
        ]);

        $hashedCanonicalRequest = hash('sha256', $canonicalRequestStr);
        $credentialScope = join('/', [$shortXDate, $this->region, $this->service, 'request']);

        $stringToSign = join("\n", [
            'HMAC-SHA256',
            $xDate,
            $credentialScope,
            $hashedCanonicalRequest
        ]);

        // 计算签名密钥
        $kDate = hash_hmac('sha256', $shortXDate, $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // 构建 Authorization 头
        $authorization = sprintf(
            "HMAC-SHA256 Credential=%s, SignedHeaders=%s, Signature=%s",
            $this->accessKeyId . '/' . $credentialScope,
            $signedHeaderStr,
            $signature
        );

        // 构建完整URL
        $url = 'https://' . $this->host . $path . '?' . http_build_query($query);

        // 发送HTTP请求
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Host: ' . $this->host,
                'X-Content-Sha256: ' . $xContentSha256,
                'X-Date: ' . $xDate,
                'Content-Type: ' . $contentType,
                'Authorization: ' . $authorization
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno) {
            throw new \Exception('火山引擎 API 请求失败 (cURL Error ' . $curlErrno . '): ' . $curlError);
        }

        // 转换 \u0026 为 &
        $result = str_replace('\u0026', '&', $result);

        $decoded = json_decode($result, true);

        return [
            'success' => ($decoded['code'] ?? 0) === 10000,
            'data' => $decoded,
            'httpCode' => $httpCode,
            'raw' => $result
        ];
    }

    /**
     * 获取配置信息
     */
    public function getConfig(): array
    {
        return [
            'region' => $this->region,
            'service' => $this->service,
            'host' => $this->host,
        ];
    }
}
