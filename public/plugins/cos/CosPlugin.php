<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\Cos;

use cmf\lib\Plugin;

class CosPlugin extends Plugin
{

    public $info = [
        'name'        => 'Cos',
        'title'       => '对象存储腾讯Cos',
        'description' => '对象存储腾讯Cos',
        'status'      => 1,
        'author'      => '五五',
        'version'     => '2.0.1'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        $storageOption['storages']['Cos'] = ['name' => '对象存储腾讯Cos', 'driver' => '\\plugins\\cos\\lib\\Cos'];

        cmf_set_option('storage', $storageOption);
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        unset($storageOption['storages']['Cos']);

        cmf_set_option('storage', $storageOption);
        return true;//卸载成功返回true，失败false
    }

}