<?php
// +----------------------------------------------------------------------
// | Alipay [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <admin@ytecn.com>
// +----------------------------------------------------------------------
return [
    'partner'     => [
        'title' => '合作者身份（Partner）',
        'type'  => 'text',
        'value' => '',
        'tip'   => '马上获取'
    ],
    'key'     => [
        'title' => '安全效验码（key）',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'alipayaccount'     => [
        'title' => '支付宝账号',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'alipayname'     => [
        'title' => '支付宝账户名',
        'type'  => 'text',
        'value' => '',
        'tip'   => '必填，个人支付宝账号是真实姓名公司支付宝账号是公司名称'
    ],
    'transport'     => [
        'title' => '访问模式',
        'type'    => 'select',
        'options' => [
            '1' => 'http',
            '2' => 'https'
        ],
        'value' => '1',
        'tip'   => '根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http'
    ]
];