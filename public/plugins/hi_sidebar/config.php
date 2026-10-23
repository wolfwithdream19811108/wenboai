<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: HiHelp <455502926@qq.com>
// +----------------------------------------------------------------------
return array (
	'hi_sidebar_bgcolor'=> [
        'title' => '背景色',
        'type'  => 'color',
        'value' => '#1c81e9'
    ],
    'hi_sidebar_qq'     => [
        'title' => 'QQ咨询', 
        'type'  => 'text',
        'value' => '455502926' 
    ],
    'hi_sidebar_online' => [
        'title' => '在线咨询', 
        'type'  => 'text',
        'value' => '#',
        'tip'   => '在线咨询URL' 
    ],
    'hi_sidebar_wechat' => [
        'title' => '二维码', 
        'type'  => 'image',
        'value' => '',
        'tip'   => '二维码或者图片' 
    ],
    'hi_sidebar_wechat_on'  => [
        'title'   => '二维码',
        'type'    => 'select',
        'options' => [
            '1' => '显示',
            '0' => '隐藏'
        ],
        'value'   => '1'
    ],
    'hi_sidebar_phone'  => [
        'title' => '座机号码', 
        'type'  => 'text',
        'value' => '0888-8888888'
    ],
    'hi_sidebar_mobile' => [
        'title' => '手机号码', 
        'type'  => 'text',
        'value' => '18888888888'
    ],
    'hi_sidebar_url'    => [
        'title' => '其他链接', 
        'type'  => 'text',
        'value' => '#',
        'tip'   => '其他URL' 
    ],
    'hi_sidebar_totop'  => [
        'title'   => '返回顶部',
        'type'    => 'select',
        'options' => [
            '1' => '显示',
            '0' => '隐藏'
        ],
        'value'   => '1',
        'tip'     => '控制返回顶部按钮，PS：以上参数不填写即隐藏。'
    ],
);
					