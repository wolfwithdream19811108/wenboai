<?php
return [
    'appid'     => [
        'title' => 'appid',
        'type'  => 'text',
        'value' => '',
        'tip'   => 'appid'
    ],
    'mchid'     => [
        'title' => 'mchid',
        'type'  => 'text',
        'value' => '',
        'tip'   => '商户号'
    ],
    'callback'     => [
        'title' => 'callback',
        'type'  => 'text',
        'value' => '',
        'tip'   => '回调地址(域名/plugin/wx_pay/Index/callback.html)'
    ],
    'key'     => [
        'title' => 'key',
        'type'  => 'text',
        'value' => '',
        'tip'   => '商户支付密钥'
    ],
    'appsecret'     => [
        'title' => 'appsecret',
        'type'  => 'text',
        'value' => '',
        'tip'   => '公众帐号secert'
    ],
    'sslCertPath'     => [
        'title' => 'sslCertPath',
        'type'  => 'text',
        'value' => '',
        'tip'   => '商户证书Cert路径'
    ],
    'sslKeyPath'     => [
        'title' => 'sslKeyPath',
        'type'  => 'text',
        'value' => '',
        'tip'   => '商户证书Key路径'
    ],
    'templateId'     => [
        'title' => 'templateId',
        'type'  => 'text',
        'value' => '',
        'tip'   => '订单消息模板id'
    ],
];
// 插件丢插件目录下\public\plugins\



// 加入hook配置 \app\portal\hooks.php

//     'wx_pay' => [

//         "type"        => 3,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)

//         "name"        => '调用微信支付', // 钩子名称

//         "description" => "调用微信支付", //钩子描述

//         "once"        => 0 // 是否只执行一次

//     ],



// 前台使用<hook name="wx_pay"/>



// 后台同步钩子

// 安装插件并配置各个参数即可



// 业务订单表

// DROP TABLE IF EXISTS `cmf_recharge`;

// CREATE TABLE `cmf_recharge` (

//   `id` int(10) NOT NULL AUTO_INCREMENT,

//   `uid` int(10) DEFAULT '0' COMMENT '用户id',

//   `total_fee` decimal(20,2) DEFAULT '0.00' COMMENT '费用',

//   `out_trade_no` varchar(30) DEFAULT '' COMMENT '商户订单号',

//   `transaction_id` varchar(30) DEFAULT '' COMMENT '第三方支付的支付号',

//   `create_time` int(10) DEFAULT '0' COMMENT '创建时间',

//   `end_time` int(10) DEFAULT '0' COMMENT '完成时间',

//   `type` tinyint(1) DEFAULT '1' COMMENT '支付类型 1=微信支付 2=支付宝支付 3=paypal',

//   `status` tinyint(1) DEFAULT '1' COMMENT '1=未支付 2=已支付',

//   PRIMARY KEY (`id`)

// ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;





// 交流群 925950557 有问题随时群里问




