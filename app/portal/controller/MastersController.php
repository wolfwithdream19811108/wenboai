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
use app\portal\model\PortalCurrentArtistsModel;
use think\Db;

class MastersController extends HomeBaseController
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

		$category                  = $this->request->param('category', 0, 'strval');
		$province                  = $this->request->param('province', 0, 'strval');
		$currentPage                  = $this->request->param('page', 0, 'intval');
		$page = 20;
		$artistList = array();
		$PortalCurrentArtistsModel = new PortalCurrentArtistsModel();
		
		
		$where = array();
        if(!empty($category)){
            $where['category'] = $category;
        }
        if(!empty($province)){
            $where['province'] = $province;
        }

		//先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
	//	$artistList = cache('web_masters_'.json_encode($category).json_encode($province));
		if(empty($artistList)){
			//$where['hasavatar'] = 1;
            $artistList = $PortalCurrentArtistsModel->where($where)->order("weight DESC");
			cache('web_masters_'.json_encode($category).json_encode($province), $artistList);
		}     
		
		$artistList = $artistList->paginate(intval($page));	
		$artistList->appends(request()->get());
		$artistList->appends(request()->post());

		$data['artistList']    = $artistList->items();
		$data['page']        = $artistList->render();
		$data['total']       = $artistList->total();
		$data['total_pages'] = $artistList->lastPage();
		
		$artists = array();
		foreach ($data['artistList'] as $artist){
		    $artist['avatar'] = json_decode($artist['avatar'],true);
		    array_push($artists,$artist->getData());
		}

		$total_pages = $data['total_pages'];
		if($currentPage>$total_pages){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		}

		$this->assign('pageIsOver', $pageIsOver);
		$this->assign('artistList',$artists);
		$this->assign('page', $data['page']);
		$this->assign('currentPage', $currentPage);
		$this->assign('totalPages', $data['total_pages']);

		$listTpl = 'masters';
		return $this->fetch('/' . $listTpl);
    }

}
