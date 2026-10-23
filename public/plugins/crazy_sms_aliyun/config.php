<?php
/**
 * 阿里云短信插件
 *
 * Author：惠达浪
 * Blog： https://www.qdcrazy.cc
 * Email： crazys@126.com
 * Date： 2019/07/15
 */

return [
    'accessKeyId'         => [
        'title' => 'AccessKeyID',
        'type'  => 'text',
        'value' => '',
        'tip'   => '参考<a href="https://help.aliyun.com/document_detail/53045.html">创建AccessKey</a>'
    ],
    'accessSecret'        => [
        'title' => 'AccessKeySecret',
        'type'  => 'text',
        'value' => '',
        'tip'   => '参考<a href="https://help.aliyun.com/document_detail/53045.html">创建AccessKey</a>'
    ],
    'SignName'            => [
        'title' => '短信签名名称',
        'type'  => 'text',
        'value' => '',
        'tip'   => '请在阿里云控制台【签名管理】页面【签名名称】一列查看。'
    ],
    'CaptchaTemplateCode' => [
        'title' => '验证码模板ID',
        'type'  => 'text',
        'value' => '',
        'tip'   => '短信模板ID。请在阿里云控制台【模板管理】页面【模板CODE】一列查看。'
    ],
    'expire'              => [
        'title' => '验证码有效期',
        'type'  => 'text',
        'value' => '5',
        'tip'   => '单位：分钟'
    ],
    'MsgTemplateCode'     => [
        'title' => '通知模板ID',
        'type'  => 'text',
        'value' => '',
        'tip'   => '短信模板ID。请在阿里云控制台【模板管理】页面【模板CODE】一列查看。'
    ]
];