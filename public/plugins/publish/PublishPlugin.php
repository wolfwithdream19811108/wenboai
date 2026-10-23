<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\publish;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

//Demo插件英文名，改成你的插件英文就行了
class PublishPlugin extends Plugin
{

    public $info = [
        'name'        => 'Publish',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '前台发布文章插件',
        'description' => '前台发布文章插件',
        'status'      => 1,
        'author'      => '西梁',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => ''
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的footer_start钩子方法
    public function beforeHeadEnd($param)
    {
        echo $this->fetch('widget');
    }

}