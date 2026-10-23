<?php
/**
 * Copyright (c) 2013-2017 http://www.syousoft.com All rights reserved.
 * Author: WelkinVan(welkinvan@qq.com)
 * Date: 6/11/2017
 * Time: 10:18 PM
 */

namespace plugins\sy_products_gallery;

use cmf\lib\Plugin;
use app\portal\model\PortalPostModel;
use think\request;

class SyProductsGalleryPlugin extends Plugin
{

    public $info = [
        'name' => 'SyProductsGallery',
        'title' => '产品相册插件',
        'description' => '将文章中的相册展现',
        'status' => 1,
        'author' => 'UpStream',
        'version' => '1.1',
        'demo_url' => 'http://www.syousoft.com',
        'author_url' => 'http://www.syousoft.com'
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

    //before_content加载主代码
    public function beforeContent($param)
    {
        $config = $this->getConfig();
        $this->assign($config);
        $param = request::instance()->param();
        $portalPostModel = new PortalPostModel();
        $photosObj = $portalPostModel->where(['id' => $param['id']])->find();
        //判断是否存在，优化输出
        if (isset($photosObj['more']['photos'])) {
            $this->assign('photos', $photosObj['more']['photos']);
        } else {
            $this->assign('photos', "");
        }
        echo $this->fetch('widget');

    }

    //实现的before_body_end钩子方法挂载js代码
    public function beforeHeadEnd($param)
    {
        echo $this->fetch('css');
    }

    //实现的before_body_end钩子方法挂载js代码
    public function beforeBodyEnd($param)
    {
        echo $this->fetch('js');
    }
}