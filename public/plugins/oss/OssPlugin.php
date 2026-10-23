<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\oss;

use cmf\lib\Plugin;

class OssPlugin extends Plugin
{

    public $info = [
        'name'        => 'Oss',
        'title'       => '云存储阿里oss',
        'description' => '云存储阿里oss',
        'status'      => 1,
        'author'      => '五五',
        'version'     => '2.6.4'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        $storageOption['storages']['Oss'] = ['name' => '阿里云存储oss', 'driver' => '\\plugins\\oss\\lib\\Oss'];

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

        unset($storageOption['storages']['Oss']);

        cmf_set_option('storage', $storageOption);
        return true;//卸载成功返回true，失败false
    }

    public function fetchUploadView()
    {
        $tab = request()->param('tab');

        if ($tab === 'cloud') {
            $config     = $this->getConfig();
            $content = $this->fetch('upload');
        } else {
            $content = "has_cloud_storage";
        }

        return $content;
    }

    public function cloudStorageTab(&$param)
    {



    }
}