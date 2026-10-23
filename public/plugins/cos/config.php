<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
return [
	'Region' => [
		'title' => 'Region地区',
		'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'tj',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Region不能为空'
        ],
        'tip'     => '例如：ap-beijing',
	],
    'secret_id'                 => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title'   => 'secret_id',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'secret_id不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/cos" target="_blank">马上获取</a>'
    ],
    'secret_key'                 => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title'   => 'secret_key',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'secret_key不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/cos" target="_blank">马上获取</a>'
    ],
    'protocol'                  => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '域名协议',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            'http'  => 'http',// 值=>显示
            'https' => 'https',
        ],
        'value'   => 'http',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '域名协议不能为空'
        ],
        'tip'     => ''
    ],
    'domain_file'                    => [
        'title'   => '数据万象 图片处理域名',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '访问域名不能为空 '
        ],
        'tip'     => '请去腾讯云 数据万象绑定空间 bucket <a href="https://cloud.tencent.com/product/ci" target="_blank">马上绑定</a>'
    ],
    'bucket'                    => [
        'title'   => '空间名称',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '空间名称不能为空'
        ],
        'tip'     => ''
    ],
    'style_separator'           => [
        'title'   => '样式分隔符',
        'type'    => 'text',
        'value'   => '!',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式分隔符不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯数据万象修改默认分隔符为!(数据万象基于腾讯cos存储)</a>'
    ],
    'styles_watermark'          => [
        'title'   => '样式-水印',
        'type'    => 'text',
        'value'   => 'watermark',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-水印不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
    'styles_avatar'             => [
        'title'   => '样式-头像',
        'type'    => 'text',
        'value'   => 'avatar',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-头像不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
    'styles_thumbnail120x120'   => [
        'title'   => '样式-缩略图120x120',
        'type'    => 'text',
        'value'   => 'thumbnail120x120',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-缩略图120x120不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
    'styles_thumbnail300x300'   => [
        'title'   => '样式-缩略图300x300',
        'type'    => 'text',
        'value'   => 'thumbnail300x300',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-缩略图300x300不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
    'styles_thumbnail640x640'   => [
        'title'   => '样式-缩略图640x640',
        'type'    => 'text',
        'value'   => 'thumbnail640x640',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-缩略图640x640不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
    'styles_thumbnail1080x1080' => [
        'title'   => '样式-缩略图1080x1080',
        'type'    => 'text',
        'value'   => 'thumbnail1080x1080',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '样式-缩略图1080x1080不能为空'
        ],
        'tip'     => '<a href="https://www.qcloud.com/product/ci" target="_blank">请到腾讯云数据万象->图片样式：添加此样式名称，并进行相应设置</a>'
    ],
];
					