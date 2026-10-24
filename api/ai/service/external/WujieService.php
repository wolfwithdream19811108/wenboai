<?php
// +----------------------------------------------------------------------
// | 无界AI 图片生成服务
// +----------------------------------------------------------------------
namespace api\ai\service\external;

class WujieService
{
    private $baseUrl = 'https://gate.wujieai.net/wj-open/v1/ai/';
    private $timeout = 60;
    private $appId;
    private $signKey;



    public function __construct()
    {
        $functionKeys = cmf_get_option('function_keys');
        $this->appId = $functionKeys['wujie_appid'] ?? '';
        $this->signKey = $functionKeys['wujie_signkey'] ?? '';

    }
    /**
     * 创建图片生成任务
     *
     * @param string $rawData 原始请求数据
     * @param string $auth 授权token
     * @param bool $isPro 是否Pro模式
     * @return array
     */
    public function createTask(string $rawData,bool $isPro = false): array
    {
        $endpoint = $isPro ? 'pro/create' : 'create';
        return $this->callApi($endpoint, 'POST', $rawData);
    }

    /**
     * 获取图片状态
     */
    public function getImageStatus(string $key): array
    {
        return $this->callApi('pro/info?key=' . urlencode($key), 'GET', null);
    }

    /**
     * 获取生成中信息
     */
    public function getGeneratingInfo(array $keys): array
    {
        return $this->callApi('pro/generating_info', 'POST', json_encode($keys));
    }

    /**
     * 加速任务
     */
    public function accelerate(array $data): array
    {
        return $this->callApi('accelerate', 'POST', json_encode($data));
    }
    /**
     * 获取模型队列信息
     */
    public function getModelQueueInfo(int $modelCode): array
    {
        return $this->callApi('model_info?model=' . $modelCode, 'GET', null);
    }
    // ==================== 认证相关（内部）====================

    /**
     * 生成Auth
     */
    private function generateAuth(): string
    {
        $timestamp = (string) time();

        $plainText = json_encode([
            'appId' => $this->appId,
            'timestamp' => $timestamp
        ], JSON_UNESCAPED_UNICODE);

        $sign = $this->sign($plainText);

        return json_encode([
            'appId' => $this->appId,
            'secretKeyVersion' => '1',
            'sign' => $sign,
            'original' => $plainText
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * RSA-SHA256 签名
     */
    private function sign(string $plainText): string
    {
        $privateKey = $this->signKey;
        if (strpos($privateKey, '-----BEGIN') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
        }

        $pkeyId = openssl_pkey_get_private($privateKey);
        if ($pkeyId === false) {
            throw new \Exception('Invalid private key: ' . openssl_error_string());
        }

        $signature = '';
        $success = openssl_sign($plainText, $signature, $pkeyId, OPENSSL_ALGO_SHA256);

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($pkeyId);
        }

        if (!$success) {
            throw new \Exception('Sign failed: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }
    /**
     * 调用无界API
     */
    private function callApi(string $endpoint, string $method, ?string $body): array
    {
        $url = $this->baseUrl . $endpoint;
        $auth = $this->generateAuth();

        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'httpCode' => $httpCode,
                'data' => null
            ];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'error' => null,
            'httpCode' => $httpCode,
            'data' => $response
        ];
    }
}
