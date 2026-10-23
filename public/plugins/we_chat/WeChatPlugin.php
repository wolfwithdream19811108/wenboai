<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://xiaoji.name All rights reserved.
// +----------------------------------------------------------------------
// | Author: jackie <297129853@qq.com>
// +----------------------------------------------------------------------
namespace plugins\we_chat;

use cmf\lib\Plugin;
use think\Db;

class WeChatPlugin extends Plugin
{
    public $info = array(
        'name' => 'WeChat',
        'title' => '微信支付以及退款',
        'description' => 'app，小程序,支付',
        'status' => 1,
        'author' => 'wudun',
        'version' => '1.0'
    );

    public $has_admin = 0;//插件是否有后台管理界面

    public function install()
    {//安装方法必须实现
        $where_data1 = [
            'type' => 1,
            'once' => 1,
            'name' => 'app预支付',
            'hook' => 'app_get_order_id',
            'app' => 'cmf',
            'description' => 'app预支付'
        ];
        $test = Db::name('hook')->where($where_data1)->find();
        if (empty($test)) {
            Db::name('hook')->insert($where_data1);
        }
        $where_data2 = [
            'type' => 1,
            'once' => 1,
            'name' => '小程序预支付',
            'hook' => 'jsapi_get_order_id',
            'app' => 'cmf',
            'description' => '小程序预支付'
        ];
        $test = Db::name('hook')->where($where_data2)->find();
        if (empty($test)) {
            Db::name('hook')->insert($where_data2);
        }
        $where_data3 = [
            'type' => 1,
            'once' => 1,
            'name' => '微信支付退款',
            'hook' => 'create_refund',
            'app' => 'cmf',
            'description' => '微信支付退款'
        ];
        $test = Db::name('hook')->where($where_data3)->find();
        if (empty($test)) {
            Db::name('hook')->insert($where_data3);
        }
        return true;//安装成功返回true，失败false
    }

    public function uninstall()
    {//卸载方法必须实现
        $where_data1 = [
            'type' => 1,
            'once' => 1,
            'name' => 'app预支付',
            'hook' => 'app_get_order_id',
            'app' => 'cmf',
            'description' => 'app预支付'
        ];
        $test = Db::name('hook')->where($where_data1)->find();
        if (!empty($test)) {
            Db::name('hook')->where($where_data1)->delete();
        }
        $where_data2 = [
            'type' => 1,
            'once' => 1,
            'name' => '小程序预支付',
            'hook' => 'jsapi_get_order_id',
            'app' => 'cmf',
            'description' => '小程序预支付'

        ];
        $test = Db::name('hook')->where($where_data2)->find();
        if (!empty($test)) {
            Db::name('hook')->where($where_data2)->delete();
        }
        $where_data3 = [
            'type' => 1,
            'once' => 1,
            'name' => '微信支付退款',
            'hook' => 'create_refund',
            'app' => 'cmf',
            'description' => '微信支付退款'
        ];
        $test = Db::name('hook')->where($where_data3)->find();
        if (!empty($test)) {
            Db::name('hook')->where($where_data3)->delete();
        }
        return true;//卸载成功返回true，失败false
    }

    //实现的app_get_order_id钩子方法,获取微信预支付信息
    // param{array[body_content,order_sn,notify_url,amount]}
    public function appGetOrderId($param)
    {
        // 1. 手动加载入口文件
        include __DIR__ . DIRECTORY_SEPARATOR . "include.php";
        // 2. 准备公众号配置参数
        $config = $this->getConfig();
        // 3. 创建接口实例
        $wechat = new \WeChat\Pay($config);
        // 4. 组装参数，可以参考官方商户文档
        $body_content = $param['body_content'];     //订单描述
        $order_sn = $param['order_sn'];             //订单号
        $notify_url = $param['notify_url'];             //订单号
        $total_fee = floatval($param['amount']) * 100;             //订单金额
        $options = [
            'body' => $body_content,
            'notify_url' => $notify_url,
            'out_trade_no' => $order_sn,
            'spbill_create_ip' => get_client_ip($type = 0, $adv = false),
            'total_fee' => $total_fee,
            'trade_type' => 'APP'                //app来的支付申请
        ];
        // 生成预支付码
        $result = $wechat->createOrder($options);
        if ($result['return_code'] !== 'SUCCESS') {
            $back_result = array(
                'error' => 9,
                'message' => '预支付码创建失败！',
            );
            return $back_result;
        }
        //app支付请求数据
        $pay = new \WePay\Order($config);
        $pay_data = $pay->appParams($result['prepay_id']);
        $back_result = array(
            'error' => '',
            'message' => '预支付订单创建成功！',
            'data' => $pay_data
        );
        return $back_result;

    }

    //实现的jsapi_get_order_id钩子方法,获取微信预支付信息
    // param{array[open_id,body_content,order_sn,notify_url,amount]}
    public function jsapiGetOrderId($param)
    {
  
        $open_id = $param['open_id'];
        
        // 1. 手动加载入口文件
        include __DIR__ . DIRECTORY_SEPARATOR . "include.php";
        // 2. 准备公众号配置参数
        $config = $this->getConfig();
        // 3. 创建接口实例
        $wechat = new \WeChat\Pay($config);
        // 4. 组装参数，可以参考官方商户文档
        $body_content = $param['body_content'];     //订单描述
        $order_sn = $param['order_sn'];             //订单号
        $notify_url = $param['notify_url'];             //订单号
        $total_fee = floatval($param['amount']) * 100;             //订单金额
        $options = [
            'openid' => $open_id,
            'body' => $body_content,
            'notify_url' => $notify_url,
            'out_trade_no' => $order_sn,
            'spbill_create_ip' => get_client_ip($type = 0, $adv = false),
            'total_fee' => $total_fee,
            'trade_type' => 'JSAPI'                //小程序来的支付申请
        ];
        // 生成预支付码
        $result = $wechat->createOrder($options);
        if ($result['return_code'] !== 'SUCCESS') {
            $back_result = array(
                'error' => 9,
                'message' => '预支付码创建失败！',
                'data' => ''
            );
            return $back_result;
        }
        // 创建JSAPI参数签名
        $options = $wechat->createParamsForJsApi($result['prepay_id']);
        $back_result = array(
            'error' => 9,
            'message' => '预支付订单创建成功！',
            'data' => $options
        );
        return $back_result;
    }

    //实现的create_refund钩子方法,微信支付原路退款
    // param{array[out_trade_no,out_refund_no,total_fee,refund_fee]}
    public function createRefund($param)
    {
        // 1. 手动加载入口文件
        include __DIR__ . DIRECTORY_SEPARATOR . "include.php";
        // 2. 准备公众号配置参数
        $config = $this->getConfig();
        // 3. 创建接口实例
        $wechat = new \WeChat\Pay($config);
        // 4. 组装参数，可以参考官方商户文档
        $options = [
            'out_trade_no' => $param['out_trade_no'],
            'out_refund_no' => $param['out_refund_no'],
            'total_fee' => floatval($param['total_fee']) * 100,
            'refund_fee' => floatval($param['refund_fee']) * 100,
        ];
        $result = $wechat->createRefund($options);
        return $result;
    }

}
