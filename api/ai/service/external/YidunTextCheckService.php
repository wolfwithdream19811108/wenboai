<?php
// +----------------------------------------------------------------------
// | 网易易盾文本内容安全检测服务
// +----------------------------------------------------------------------
namespace api\ai\service\external;

class YidunTextCheckService
{
    private $secretId;
    private $secretKey;
    private $businessId;
    private $apiUrl     = 'http://as.dun.163.com/v5/text/check';

    // 单次请求超时（秒）
    private $timeout = 2;
    // 失败重试次数
    private $retryTimes = 1;

    public function __construct()
    {
        $functionKeys = cmf_get_option('function_keys');
        $this->secretId = trim((string)($functionKeys['yidun_secret_id'] ?? ''));
        $this->secretKey = trim((string)($functionKeys['yidun_secret_key'] ?? ''));
        $this->businessId = trim((string)($functionKeys['yidun_business_id'] ?? ''));
    }

    /**
     * 检测文本是否通过内容安全审核（fail-close策略）
     *
     * @param string $content 待检测文本
     * @return array [
     *   'available' => bool,  // 检测服务是否可用
     *   'pass'      => bool,  // 是否通过检测（available=false时恒为false）
     *   'detail'    => string // 内部日志用详情，不可返回给调用方
     * ]
     */
    public function check($content)
    {
        if ($this->secretId === '' || $this->secretKey === '' || $this->businessId === '') {
            return [
                'available' => false,
                'pass' => false,
                'detail' => '易盾文本审核配置不完整',
            ];
        }

        $lastError = '';

        // 失败自动重试，降低网络抖动导致的误拒
        for ($i = 0; $i <= $this->retryTimes; $i++) {
            $ret = $this->doCheck($content);
            if ($ret['available']) {
                return $ret;
            }
            $lastError = $ret['detail'];
        }

        return ['available' => false, 'pass' => false, 'detail' => $lastError];
    }

    /**
     * 执行单次检测
     */
    private function doCheck($content)
    {
        $params = [
            'dataId'     => 'aigc_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16),
            'content'    => $content,
            'secretId'   => $this->secretId,
            'businessId' => $this->businessId,
            'version'    => 'v5',
            'timestamp'  => (string)(time() * 1000),
            'nonce'      => sprintf('%d', rand()),
        ];

        $params              = $this->toUtf8($params);
        $params['signature'] = $this->genSignature($params);

        $response = $this->curlPost($params);

        if ($response['code'] != 200 || empty($response['data'])) {
            return [
                'available' => false,
                'pass'      => false,
                'detail'    => '易盾API请求失败: HTTP ' . $response['code'] . ' ' . $response['error'],
            ];
        }

        $decoded = json_decode($response['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'available' => false,
                'pass'      => false,
                'detail'    => '易盾响应JSON解析失败: ' . json_last_error_msg(),
            ];
        }

        // 易盾业务状态码非200视为服务异常
        if (!isset($decoded['code']) || $decoded['code'] != 200) {
            return [
                'available' => false,
                'pass'      => false,
                'detail'    => '易盾返回错误状态码: ' . ($decoded['code'] ?? 'unknown') . ' ' . ($decoded['msg'] ?? ''),
            ];
        }

        $antispam = $decoded['result']['antispam'] ?? null;
        if ($antispam === null) {
            return [
                'available' => false,
                'pass'      => false,
                'detail'    => '易盾响应缺少antispam结果',
            ];
        }

        // suggestion: 0=通过, 1=嫌疑, 2=不通过；只有0放行
        $suggestion = $antispam['suggestion'] ?? 2;
        if ($suggestion == 0) {
            return ['available' => true, 'pass' => true, 'detail' => '通过检测'];
        }

        // 命中详情仅用于内部日志，不返回给调用方
        $detail = 'suggestion=' . $suggestion;
        if (!empty($antispam['labels']) && is_array($antispam['labels'])) {
            foreach ($antispam['labels'] as $labelElement) {
                $detail .= ', label=' . ($labelElement['label'] ?? '');
            }
        }

        return ['available' => true, 'pass' => false, 'detail' => $detail];
    }

    private function genSignature($params)
    {
        ksort($params);
        $buff = '';
        foreach ($params as $key => $value) {
            $buff .= $key . $value;
        }
        $buff .= $this->secretKey;
        return md5(mb_convert_encoding($buff, 'utf8', 'auto'));
    }

    private function toUtf8($params)
    {
        foreach ($params as $key => $value) {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $params[$key] = mb_convert_encoding($value, 'UTF-8', 'GBK');
            }
        }
        return $params;
    }

    private function curlPost($data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(2, $this->timeout),
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $httpCode, 'data' => $response, 'error' => $error];
    }
}
