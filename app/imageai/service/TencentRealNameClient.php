<?php

namespace app\imageai\service;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Faceid\V20180301\FaceidClient;
use TencentCloud\Faceid\V20180301\Models\IdCardVerificationRequest;

class TencentRealNameClient
{
    public function verify($name, $idCard, array $settings = null, array $credentials = null)
    {
        $settings = $settings === null ? RealNameConfig::settings() : $settings;
        $credentials = $credentials === null ? RealNameConfig::credentials() : $credentials;
        if ($credentials['secret_id'] === '' || $credentials['secret_key'] === '') {
            throw new \RuntimeException('腾讯云实名认证服务尚未配置');
        }

        $credential = new Credential($credentials['secret_id'], $credentials['secret_key']);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint('faceid.tencentcloudapi.com');
        $httpProfile->setReqTimeout(max(3, min(30, (int)$settings['timeout'])));
        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);

        $client = new FaceidClient($credential, '', $clientProfile);
        $request = new IdCardVerificationRequest();
        $request->fromJsonString(json_encode([
            'Name' => (string)$name,
            'IdCard' => (string)$idCard,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = json_decode($client->IdCardVerification($request)->toJsonString(), true);
        if (!is_array($response)) {
            throw new \RuntimeException('腾讯云实名认证服务返回格式异常');
        }

        return [
            'result' => (string)($response['Result'] ?? ''),
            'description' => (string)($response['Description'] ?? ''),
            'request_id' => (string)($response['RequestId'] ?? ''),
        ];
    }
}
