<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
return [
    'config_bgcolor' => [
        'title' => '导航背景色',
        'type'  => 'color',
        'value' => '#0075c2',
        'tip'   => '请选择背景色'
    ],
    'config_switch' => [
        'title' => '总开关设置',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '1',
        'tip'   => '请根据需要选择性开启'
    ],
    'config_mobile_switch' => [
        'title' => '移动端按钮数量',
        'type'  => 'select',
		'options' => [
            '3' => '3',
            '4' => '4',
            '5' => '5'
        ],
        'value' => '3',
        'tip'   => '请根据需要选择按钮数量'
    ],
    'config_home_switch' => [
        'title' => '返回首页',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '3',
        'tip'   => '请根据需要选择是否开启'
    ],
    'config_msg_switch' => [
        'title' => '在线留言',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '1',
        'tip'   => '请选择是否开启在线留言'
    ],
    'config_QQ' => [
        'title' => 'QQ客服',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'config_QQ_switch' => [
        'title' => '',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '0',
        'tip'   => '请填入QQ号，选择是否开启QQ客服'
    ],
    'config_TEL' => [
        'title' => '咨询热线',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'config_TEL_switch' => [
        'title' => '',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '0',
        'tip'   => '请填入电话号码，选择是否开启'
    ],
    'config_contact' => [
        'title' => '联系方式链接',
        'type'  => 'text',
        'value' => '',
        'tip'   => ''
    ],
    'config_contact_switch' => [
        'title' => '',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '0',
        'tip'   => '请输入完整网址链接，再选择是否开启'
    ],
    'config_webQR' => [
        'title' => '当前页面二维码',
        'type'  => 'text',
        'value' => 'http://qr.topscan.com/api.php?text=',
        'tip'   => ''
    ],
    'config_webQR_switch' => [
        'title' => '',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '2',
        'tip'   => '请输入二维码API，选择是否开启当前页面二维码'
    ],
    'config_QR' => [
        'title' => '微信二维码',
        'type'  => 'image',
        'value' => '',
        'tip'   => ''
    ],
    'config_QR_switch' =>[
        'title' => '',
        'type'  => 'select',
		'options' => [
            '0' => '不开启',
            '1' => 'PC端 + 移动端',
            '2' => '仅PC端',
            '3' => '仅移动端'
        ],
        'value' => '0',
        'tip'   => '请上传需要微信二维码的，再选择开启'
    ],
    'config_webrobot' => [
        'title' => '网站留言-钉钉机器人',
        'type'  => 'text',
        'value' => '',
        'tip'   => '请输入钉钉机器人webhook'
    ],
];
					