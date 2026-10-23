<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
return [
    'appid'     => [
        'title' => '微信服务号appID',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'appsecret'     => [
        'title' => '微信服务号appsecret',
        'type'  => 'text',
        'value' => '',
        'tip'   => '注意：需要在服务号后台设置授权回调页面域名！'
    ],
    'config_adminlogin_switch' => [
        'title' => '后台登录设置',
        'type'  => 'select',
		'options' => [
            '1' => '账号登录 + 扫码登录',
            '2' => '仅账号登录',
            '3' => '仅扫码登录'
        ],
        'value' => '1',
        'tip'   => '请根据需要选择性开启'
    ],
	'config_adminlogin_title' => [
	    'title' => '后台标题文字',
	    'type'  => 'text',
	    'value' => 'Sam后台登录',
	    'tip'   => '用于后台登录的title显示'
	],
    'config_adminlogin_bg' => [
        'title' => '背景图片URL',
        'type'  => 'image',
        'value' => '',
        'tip'   => '后台登录的背景图片'
    ]
];