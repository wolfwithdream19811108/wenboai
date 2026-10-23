<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalMuseumModel;
use app\portal\model\PortalPostModel;
use app\portal\model\PortalAuctionPostModel;
use app\portal\model\PortalCategoryTagtypeModel;
use app\portal\model\PortalTagTagtypeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;

class UploadimageController extends HomeBaseController
{
    /***
     * 文章列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
     public function index()
    {

        $imageUrl = $this->request->get('image_url');
        $searchType = $this->request->get('search_type');

        

        
        $result=$error=$articles='';
        $image = null;        

        $error = "请上传要识别的图片";
        $this->assign('error', $error);
		$this->assign('imageUrl', $imageUrl);
        return $this->fetch('/uploadimage');

  

    }

}
