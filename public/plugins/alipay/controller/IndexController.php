<?php
// +----------------------------------------------------------------------
// | Alipay [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <admin@ytecn.com>
// +----------------------------------------------------------------------
namespace plugins\alipay\controller;
use cmf\controller\PluginBaseController;
use think\Db;
use plugins\alipay\service\AlipayNotify;
use plugins\alipay\model\AlipayService;

require_once getcwd() .'/plugins/alipay/lib/alipay_submit.class.php';

class IndexController extends PluginBaseController
{
    function index()
    {
        $parameter = array(
            "out_trade_no" => '0123456789yt', //订单号
            "subject" => '这是测试商品' ,//标题
            "total_fee" => '0.01', //金额
            "body" => '这是一个测试支付功能的商品',//商品简介
            "extra_common_param" => 'https://www.ytecn.com',//商品链接
        );
        $alipay=new AlipayService();
        $alipay->AlipayAPI($parameter);
    }

    function AlipayPayReturn_Succeed()
    {
        //同步通知代码
        $this->success("充值成功！", $_GET['extra_common_param']);
    }
    function AlipayPayNotice_Succeed()
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
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {
            $out_trade_no = $_POST['out_trade_no'];
            $trade_no = $_POST['trade_no'];
            $trade_status = $_POST['trade_status'];
            if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
                //异步通知代码
            }
        }
    }
}