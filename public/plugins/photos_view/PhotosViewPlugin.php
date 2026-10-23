<?php
// +----------------------------------------------------------------------
// | NotFound [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 SkyFlying All rights reserved.
// +----------------------------------------------------------------------
// | Author: SkyFlying 
// +----------------------------------------------------------------------
namespace plugins\photos_view;
use cmf\lib\Plugin;
use app\portal\model\PortalPostModel;
use think\request;
class PhotosViewPlugin extends Plugin
{

    public $info = [
        'name'        => 'PhotosView',
        'title'       => '相册幻灯',
        'description' => '相册幻灯',
        'status'      => 1,
        'author'      => 'SkyFlying',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => ''
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

    public function beforeContent($param)
    {
        $config = $this->getConfig();
		
        $this->assign($config);
        $param = request::instance()->param();
		$portalPostModel = new PortalPostModel();
        $PostObj = $portalPostModel->where(['id' => $param['id']])->find();

		
        if (isset($PostObj['more']['photos'])) {
            $this->assign('photos', $PostObj['more']['photos']);

        } else {
            $this->assign('photos', "");
        }
        if (isset($PostObj['more']['video'])) {
            $this->assign('video', $PostObj['more']['video']);
        } else {
            $this->assign('video', "");
        }
        echo $this->fetch('photos');
    }
}