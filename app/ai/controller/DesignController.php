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
namespace app\ai\controller;


use cmf\controller\HomeBaseController;
use phpseclib\Crypt\RSA;
use think\Db;
use app\ai\model\AiUserPointsModel;
use app\ai\model\AiUserWorksModel;

class DesignController extends HomeBaseController
{
    /**
     * 文章详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */





    public function index()
    {

        
        $isLogin = cmf_is_user_login();
        $page = 100;
        if ($isLogin) {
            $userid = cmf_get_current_user_id();
            $aiUserPointsModel = new AiUserPointsModel();
            $currentPoints = $aiUserPointsModel->getUserPoints($userid); 
            $aiUserWorksModel = new AiUserWorksModel();
          //  $worksArray = $aiUserWorksModel->getUserWorks($userid);
            
            
// 获取记录总数
         //   $totalCount = $aiUserWorksModel->where('uid', $userid)->where('picture_url', '<>', '')->order('create_timestamp', 'DESC')->count(); 
         $totalCount = $aiUserWorksModel->where('uid', $userid)->where('picture_url', '<>', '')->order('create_timestamp', 'DESC')->count(); 
         $worksItems = $aiUserWorksModel->where('uid', $userid)->where('picture_url', '<>', '')->order('create_timestamp', 'DESC'); 
            $worksItems = $worksItems->paginate(intval($page));
            
     		$worksArray    = $worksItems->items();
    		$pages       = $worksItems->render();
    		$total_pages = $worksItems->lastPage();           
            
            
            
            $this->assign('points', $currentPoints);
            $this->assign('total', $totalCount);
            $this->assign('worksArray', $worksArray);
            $this->assign('page', $pages);
		    $this->assign('total_pages', $total_pages);  
        }else{
            $this->error("用户未登录!请先登陆");
        }
        
   

   
   
   
   
        
        $everythingCategory = Db::name('portal_aiimage_everything')->distinct(true)->field('category')->select()->toArray();
        $everythingimgArray = Db::name('portal_aiimage_everything')->where('category', '=', "交通")->select()->toArray();
        $this->assign('everythingCategory', $everythingCategory);
        $this->assign('everythingimgArray', $everythingimgArray);
		$this->assign('isLogin', $isLogin);
        return $this->fetch("/design");
    }
 

}
