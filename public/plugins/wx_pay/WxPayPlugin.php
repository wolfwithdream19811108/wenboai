<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xiaoying <360963804@qq.com>
// +----------------------------------------------------------------------
namespace plugins\wx_pay;

use cmf\lib\Plugin;
use think\Db; //tp5使用
// use think\facade\Db;//tp6使用
use think\Request;
use plugins\wx_pay\model\{
    Recharge,
    User
};

class WxPayPlugin extends Plugin
{

    public $info = [
        'name'        => 'WxPay',    //插件名字
        'title'       => '微信支付插件',   //标题
        'description' => '微信支付插件',   //描述
        'status'      => 0,          //关
        'author'      => '小影',       //作者
        'version'     => '1.0'       //版本号
    ];

    public $hasAdmin = 0; //插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $exist = Db::query('show tables like "cmf_recharge"');
        if (!$exist) {
            $sql = "CREATE TABLE IF NOT EXISTS `cmf_recharge` (
                `id` int(10) NOT NULL AUTO_INCREMENT,
                `uid` int(10) DEFAULT '0' COMMENT '用户id',
                `total_fee` decimal(20,2) DEFAULT '0.00' COMMENT '费用',
                `out_trade_no` varchar(30) DEFAULT '' COMMENT '商户订单号',
                `transaction_id` varchar(30) DEFAULT '' COMMENT '第三方支付的支付号',
                `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
                `end_time` int(10) DEFAULT '0' COMMENT '完成时间',
                `type` tinyint(1) DEFAULT '1' COMMENT '支付类型 1=微信支付 2=支付宝支付 3=paypal',
                `status` tinyint(1) DEFAULT '1' COMMENT '1=未支付 2=已支付',
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;";
            Db::execute($sql);
        }
        return true; //安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }

    //实现的WxPay钩子方法
    public function WxPay()
    {
        $config = $this->getConfig();
        $this->assign($config);
        echo $this->fetch('index');
    }
}
