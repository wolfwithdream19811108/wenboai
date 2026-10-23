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
use app\portal\service\ApiService;


class ArtistpostlistController extends HomeBaseController
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
        $author                  = $this->request->param('author');
    
		$portalPostModel = new PortalPostModel();
		$PortalCategoryModel = new PortalCategoryModel();
		$PortalMuseumModel = new PortalMuseumModel();
		
        $category_id                  = $this->request->param('category', 0, 'intval');
		$museum_id                  = $this->request->param('museum');
		$page                  = $this->request->param('page');		
		

       $category_ids = $portalPostModel->Distinct(true)->field('post_category_id')->where('post_author', $author)->where('delete_time', 0)->order('post_category_id ASC')->select();
	   $categories = array();
		foreach ($category_ids as $val){
			$category = $PortalCategoryModel->where('id', $val['post_category_id'])->find();
			array_push($categories,$category);
		}
        if($category_id != 0){
            $museum_ids = $portalPostModel->Distinct(true)->field('post_museum_id')->where('post_author', $author)->where('delete_time', 0)->where('post_category_id', $category_id)->order('post_museum_id ASC')->select();
        }else{
            $museum_ids = $portalPostModel->Distinct(true)->field('post_museum_id')->where('post_author', $author)->order('post_museum_id ASC')->select();
        }

	   $museums = array();
		foreach ($museum_ids as $val){
			$museum = $PortalMuseumModel->where('id', $val['post_museum_id'])->find();
			array_push($museums,$museum);
		}

       
       $this->assign('author', $author);
	   $this->assign('categories', $categories);
	   $this->assign('museums', $museums);




		$params = array();
		$params['museum_id'] = $museum_id;
		$params['category_ids'] = $category_id;
		$params['author'] = $author;
		$params['page'] = 50;
		$ApiService = new ApiService();
		$data = ApiService::articles($params);
 
 
 		$total_pages = $data['total_pages'];
 		//页数大于等于总页数，或者
		if($page>$total_pages && $total_pages!=0){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		}  
	
		


		$this->assign('articles', $data['articles']);
		$this->assign('page', $data['page']);
        $this->assign('pageIsOver', $pageIsOver);
        $this->assign('category', $category_id);
        $this->assign('total_pages', $total_pages);



	   

        $listTpl = 'artist_article_list';
		

        return $this->fetch('/' . $listTpl);
    }

}
