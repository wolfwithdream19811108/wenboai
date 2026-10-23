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

class ModelselectController extends HomeBaseController
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
      
        $categories_json= json_encode($categories);
        $this->assign('categories_json', $categories_json);
		$this->assign('categories', $categories);
		$listTpl = 'modelselect';
		return $this->fetch('/' . $listTpl);
			

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
