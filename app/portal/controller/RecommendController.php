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
use app\portal\model\UserCategoryModel;
use app\portal\service\ApiService;
use think\Db;

class RecommendController extends HomeBaseController
{
    /***
     * 推荐列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
		$page                 = $this->request->param('page');

        $userId = cmf_get_current_user_id();
        $portalCategoryModel = new PortalCategoryModel();
        $categories = $portalCategoryModel->select()->toArray();
        $hasInterest = false;
        $isLogin = false;
        if (empty($userId)) {
			$isLogin = false;
        }else{
			$UserCategoryModel = new UserCategoryModel();
			$result = $UserCategoryModel->where('user_id', $userId)->column('categoryid');
          
			//$categories = implode(",",$result);
   
			
			if(count($result) != 0){
				$hasInterest = true;
			}
     
			$isLogin = true;
			
		}
      
        $categories_json= json_encode($categories);
        $this->assign('categories_json', $categories_json);
		$this->assign('categories', $categories);
		$this->assign('isLogin', $isLogin);
        $this->assign('hasInterest', $hasInterest);
		$listTpl = 'recommend';
		return $this->fetch('/' . $listTpl);
			

    }


	 /***
     * ajax获得数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecommendArticle()
    {
		
	
		$page                 = $this->request->param('page');

        $userId = cmf_get_current_user_id();
        if (empty($userId)) {
            if ($this->request->isAjax()) {
                $this->error("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }


        $UserCategoryModel = new UserCategoryModel();
        $result = $UserCategoryModel->where('user_id', $userId)->column('categoryid');
        $categories = implode(",",$result);			
	
		$params = array();
		$params['category_ids'] = $categories;
		$params['page'] = 60;
		$ApiService = new ApiService();
	   
		
		$data = ApiService::recommendArticles($params);
		
		return json_encode($data['articles']);


    }


	 /***
     * ajax获得数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selectcategory()
    {
		
		$userId = cmf_get_current_user_id();
		$selectedCategories                 = $_POST["selectedCategories"];
		$UserCategoryModel = new UserCategoryModel();


		$userCategories = array();
		Db::name('user_category')->where('user_id',$userId)->delete();
		foreach($selectedCategories as $categoryId){
				$record = ['user_id'=>$userId,'categoryid'=>$categoryId];
				Db::name('user_category')->insert($record);
		}
		return True;
	}
}
