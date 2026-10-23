<?php
/**
 * 阿里云短信插件
 *
 * Author：惠达浪
 * Blog: https://www.qdcrazy.cc
 * Email： crazys@126.com
 * Date： 2019/07/14
 */

namespace plugins\crazy_sms_aliyun;

use cmf\lib\Plugin;
use plugins\crazy_sms_aliyun\service\SmsService;

class CrazySmsAliyunPlugin extends Plugin
{
    public $info = [
        'name'        => 'CrazySmsAliyun',
        'title'       => '阿里云短信插件',
        'description' => '阿里云验证码、通知短信通用插件',
        'status'      => 1,
        'author'      => '惠达浪',
        'version'     => '1.1.0',
        'demo_url'    => 'https://www.crazys.pub',
        'author_url'  => 'https://www.qdcrazy.cc'
    ];

    /**
     * 安装插件
     * @return bool
     */
    public function install()
    {
        if (class_exists('AlibabaCloud\Client\AlibabaCloud')) {
            return true;
        }

        if (function_exists('cmf_version')) {
            if (version_compare(cmf_version(), '5.0.190312') >= 0 && class_exists('ZipArchive')) {
                $sdkFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'aliyuncloud.zip';
                $zip     = new \ZipArchive();
                if ($zip->open($sdkFile) === true) {
                    return $zip->extractTo(__DIR__);
                }
            }
        }

        return false;
    }

    /**
     * 卸载插件
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 实现send_mobile_verification_code钩子方法
     * @param array $param 参数
     * @return array
     */
    public function sendMobileVerificationCode(array $param)
    {
        if (empty($param['mobile'])) {
            return ['error' => 1, 'message' => '未提供手机号码'];
        }

        $sms = new SmsService($this->getConfig());
        if (array_key_exists('code', $param)) {
            $result = $sms->sendCode($param);
        } else {
            $result = $sms->sendMessage($param);
        }
        return $result;
    }
}