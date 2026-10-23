<?php
// +----------------------------------------------------------------------
// | TcAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\tcad\validate;
use think\Validate;

class TcadValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => '广告名必须',
    ];

    protected $scene = [
        'add'  => ['name'],
        'edit' => ['name'],
    ];
}
