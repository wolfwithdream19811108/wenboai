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
use app\portal\model\PortalCategoryTagtypeModel;
use app\portal\model\PortalTagTagtypeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;
class MuseumController extends HomeBaseController
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
        $params = [
            'category_id'  => $this->request->param('category', 0, 'intval'),
            'times'     => $this->request->param('times', ''),
            'museum_id'    => $this->request->param('museum', 0, 'intval'),
            'page'      => $this->request->param('page', 1, 'intval')
        ];
        extract($params); // 将数组转换为变量

        $where = [
            'post_museum_id' => $museum_id
        ];
        $where += array_filter([
            'post_category_id' => $category_id ?: null,
            'post_age' => ($times && $times != '长卷') ? $times : null
        ]);

		
					
		$PortalPostModel = new PortalPostModel();
		$data = $PortalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select();
		$PortalMuseumModel = new PortalMuseumModel();

		$museum = PortalMuseumModel::cache(true, 3600)->find($museum_id);
		$this->assign('museum', $museum);
		
		
		$portalCategoryModel = new PortalCategoryModel();
        $categoryIds = PortalPostModel::where('post_museum_id', $museum_id)
            ->group('post_category_id')
            ->column('post_category_id');
        
        $categories = $categoryIds ? 
            PortalCategoryModel::whereIn('id', $categoryIds)
                ->cache(true, 3600)
                ->select() : 
            [];
         $this->assign('categorys', $categories);

		$params = array();
		$params['age'] = $times;
		$params['museum_id'] = $museum_id;
		$params['category_ids'] = $category_id;
		$params['page'] = 60;
		$ApiService = new ApiService();
	
		$data = ApiService::articles($params);	
 		$total_pages = $data['total_pages'];
 		//页数大于等于总页数，或者
		if($page>$total_pages && $total_pages!=0){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		}  

	    $this->assign('category', $category_id);
	
		$this->assign('pageIsOver', $pageIsOver);
		$this->assign('age', $times);
		$this->assign('articles', $data['articles']);
		$this->assign('page', $data['page']);
		$this->assign('total_pages', $total_pages);
		$listTpl = 'museum1';


		return $this->fetch('/' . $listTpl);
			
		



    }
  



}
