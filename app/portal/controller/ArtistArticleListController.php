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


class ArtistArticleListController extends HomeBaseController
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
        
        $author = $this->request->param('author', 0, 'strval');
        if (empty($author)) {
            throw new \Exception('作者参数不能为空');
        }        
        
        

    
        // 获取分类ID列表（使用group替代Distinct）
        // 优化后 - 使用JOIN一次性获取
        $categories = PortalCategoryModel::alias('c')
            ->join('cmf_portal_post p', 'p.post_category_id = c.id')
            ->where('p.post_author', $author)
            ->group('c.id')
            ->field('c.*')
            ->select();
        


        // 获取博物馆ID列表
        $museumIds = PortalPostModel::where('post_author', $author)
            ->group('post_museum_id')
            ->column('post_museum_id');
        
        // 一次性获取所有博物馆信息
        $museums = !empty($museumIds) ? 
            PortalMuseumModel::whereIn('id', $museumIds)->select() : 
            [];

       $this->assign('author', $author);
	   $this->assign('categories', $categories);
	   $this->assign('museums', $museums);



        $category_id                  = $this->request->param('category', 0, 'intval');
		$museum_id                  = $this->request->param('museum');
		$page                  = $this->request->param('page');
		$params = array();
		$params['museum_id'] = $museum_id;
		$params['category_ids'] = $category_id;
		$params['author'] = $author;
		$params['page'] = 60;
		$ApiService = new ApiService();
		$data = ApiService::articles($params);


		$this->assign('articles', $data['articles']);
		$this->assign('page', $data['page']);


        $listTpl = 'artist_article_list';
		

        return $this->fetch('/' . $listTpl);
    }

}
