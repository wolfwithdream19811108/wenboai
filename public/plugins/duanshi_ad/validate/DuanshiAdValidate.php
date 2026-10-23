<?php
// +----------------------------------------------------------------------
// | DuanshiAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: duanshi <352534101@qq.com>
// +----------------------------------------------------------------------
namespace plugins\duanshi_ad\validate;
use think\Validate;

class DuanshiAdValidate extends Validate
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
