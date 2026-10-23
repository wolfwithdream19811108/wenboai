<?php
// +----------------------------------------------------------------------
// | Alipay [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <admin@ytecn.com>
// +----------------------------------------------------------------------
namespace plugins\alipay;
use cmf\lib\Plugin;

class AlipayPlugin extends Plugin
{

    public $info = array(
        'name'        => 'Alipay',
        'title'       => '支付宝接口',
        'description' => '支付宝接口',
        'status'      => 1,
        'author'      => 'Tangchao',
        'version'     => '1.0'
    );

    public $hasAdmin = 0;

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

}