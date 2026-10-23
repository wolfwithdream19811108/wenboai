<?php
/**
 * 阿里云短信插件
 *
 * Author：惠达浪
 * Blog： https://www.qdcrazy.cc
 * Email： crazys@126.com
 * Date： 2019/07/21
 */

namespace plugins\crazy_sms_aliyun\service;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\Exception;

class SmsService
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 发送验证码
     * 如果没有提供验证码则自动生成一个
     * @param array $param
     * @return array
     */
    public function sendCode(array $param)
    {
        try {
            $mobile       = is_array($param['mobile']) ? end($param['mobile']) : $param['mobile'];
            $templateCode = empty($param['templateCode']) ? $this->config['CaptchaTemplateCode'] : $param['templateCode'];
            $code         = empty($param['code']) ? cmf_get_verification_code($mobile) : $param['code'];
            $signName     = empty($sign) ? $this->config['SignName'] : $sign;


            if ($code) {
                $result = $this->sendSms($mobile, ['code' => $code], $templateCode, $signName);
                if ($result['error'] === 0) {
                    $expire = empty($this->config['expire']) ? 0 : intval($this->config['expire']) * 60 + time();
                    empty($param['code']) && cmf_verification_code_log($mobile, $code, $expire);
                    $result['expire_time'] = $expire;
                }
            } else {
                $result = ['error' => 1, 'message' => '生成验证码失败'];
            }
        } catch (Exception $e) {
            $result = ['error' => 1, 'message' => $e->getMessage()];
        }
        return $result;
    }

    /**
     * 发送通知消息
     * @param array $params 参数
     * @return array
     */
    public function sendMessage($params)
    {
        $mobile   = is_array($params['mobile']) ? implode(',', $params['mobile']) : $params['mobile'];
        $tempCode = empty($params['templateCode']) ? $this->config['MsgTemplateCode'] : $params['templateCode'];
        $vars     = empty($params['params']) ? [] : $params['params'];
        $signName = empty($sign) ? $this->config['SignName'] : $sign;

        return $this->sendSms($mobile, $vars, $tempCode, $signName);
    }

    /**
     * 发送短信
     * @param mixed  $mobile   手机号码
     * @param array  $param    待发送的参数
     * @param string $template 使用的模板CODE
     * @param string $sign     签名
     * @return array
     */
    private function sendSms($mobile, array $param, $template, $sign)
    {
        $options = [
            'query' => [
                'PhoneNumbers'  => $mobile,
                'SignName'      => $sign,
                'TemplateCode'  => $template,
                'TemplateParam' => json_encode($param)
            ]
        ];

        // 发送短信
        try {
            AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessSecret'])
                ->regionId('cn-hangzhou')
                ->asDefaultClient();

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options($options)
                ->request()
                ->toArray();
            if ($result['Code'] === 'OK') {
                $return = ['error' => 0, 'message' => '短信发送成功'];
            } else {
                $return = ['error' => $result['Code'], 'message' => $result['Message']];
            }
        } catch (ClientException $e) {
            $return = ['error' => 1, 'message' => $e->getErrorMessage()];
        } catch (ServerException $e) {
            $return = ['error' => 1, 'message' => $e->getErrorMessage()];
        } catch (\Exception $e) {
            $return = ['error' => 1, 'message' => $e->getMessage()];
        }
        return $return;
    }
}