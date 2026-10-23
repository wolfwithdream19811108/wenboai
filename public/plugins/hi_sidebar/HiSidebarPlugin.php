<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: HiHelp <455502926@qq.com>
// +----------------------------------------------------------------------
namespace plugins\hi_sidebar;
use cmf\lib\Plugin;

class HiSidebarPlugin extends Plugin
{
    public $info = [
        'name'        => 'HiSidebar',
        'title'       => '右侧悬浮在线客服',
        'description' => '右侧悬浮在线客服，支持QQ、微信、电话、链接等',
        'status'      => 1,
        'author'      => 'HiHelp',
        'version'     => '1.0',
        'demo_url'    => 'http://demo.himvc.com',
        'author_url'  => 'https://www.softlu.com'
    ];

    public $hasAdmin = 0;

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
    
	public function beforeHeadEnd($param)
    {
    	$config = $this->getConfig();
        $this->assign($config);
        echo $this->fetch('css');
    }
    
    public function beforeBodyEnd($param)
    {
        $config = $this->getConfig();
        $this->assign($config);
        echo $this->fetch('widget');
    }

}