<?php
// +----------------------------------------------------------------------
// | Alipay [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <admin@ytecn.com>
// +----------------------------------------------------------------------
namespace plugins\alipay\service;

use think\Db;

require_once getcwd() .'/plugins/alipay/lib/alipay_submit.class.php';


class AlipayService
{
    function AlipayAPI($parameter)
    {
        $config = $this->getPlugin('Alipay')->getConfig();
        $alipay_config['partner'] = $config['partner'];
        $alipay_config['seller_email'] = $config['alipayaccount'];
        $alipay_config['alipayname'] = $config['alipayname'];
        $alipay_config['key'] = $config['key'];
        $alipay_config['sign_type'] = strtoupper('MD5');
        $alipay_config['input_charset'] = strtolower('utf-8');
        $alipay_config['cacert'] =  getcwd() . '/plugins/alipay/lib/cacert.pem';
        $alipay_config['transport'] = $config['transport'];
        $parameter['partner'] = trim($alipay_config['partner']);
        $parameter['anti_phishing_key'] = '';
        $parameter['exter_invoke_ip'] = get_client_ip(0, true);
        $parameter['_input_charset'] = trim(strtolower($alipay_config['input_charset']));
        $parameter['notify_url'] = (isset($parameter['notify_url'])) ? $parameter['notify_url'] : (cmf_plugin_url('Alipay://Index/AlipayPayNotice_Succeed',array(),true));
        $parameter['return_url'] = (isset($parameter['return_url'])) ? $parameter['return_url'] : (cmf_plugin_url('Alipay://Index/AlipayPayReturn_Succeed',array(),true));
        $parameter['service'] = 'create_direct_pay_by_user';
        $parameter['payment_type'] = (isset($parameter['payment_type'])) ? $parameter['payment_type'] : '1';
        $parameter['seller_email'] = trim($alipay_config['seller_email']);
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '请稍后，等待支付……');
        return $html_text;
    }
}