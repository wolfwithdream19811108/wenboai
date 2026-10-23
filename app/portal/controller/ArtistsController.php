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
use app\portal\model\PortalArtistModel;
use think\Db;

class ArtistsController extends HomeBaseController
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



		$age                  = $this->request->param('times', 0, 'strval');
		$currentPage                  = $this->request->param('page', 0, 'intval');
		$page = 20;
		$artistList = array();
		$PortalArtistModel = new PortalArtistModel();
		
		
		
		
	//	$ages = $PortalArtistModel->Distinct(true)->field('age')->select()->toArray();
	//	dump($ages);


		//先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
		//$artistList = cache('web_artists_'.json_encode($age));
		if(empty($artistList)){
			$where['nation'] = '中国';
			$where['name'] = ['neq', '佚名'];


			if($age!='全部'){
				$where['age'] = $age;
				$artistList = $PortalArtistModel->field('id,name,age,works_num,work_image')->where($where)->order('works_num desc')->group('id');
			}else{

				$artistList = $PortalArtistModel->field('id,name,age,works_num,work_image')->where($where)->order('works_num desc')->group('id');
			}
			
			
			//cache('web_artists_'.json_encode($age), $artistList);
		}           
		$artistList = $artistList->paginate(intval($page));	
		$artistList->appends(request()->get());
		$artistList->appends(request()->post());

		$data['artistList']    = $artistList->items();
		$data['page']        = $artistList->render();
		$data['total']       = $artistList->total();
		$data['total_pages'] = $artistList->lastPage();

		$total_pages = $data['total_pages'];
		if($currentPage>$total_pages){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		}


		$this->assign('pageIsOver', $pageIsOver);
		$this->assign('artistList',$artistList);
		$this->assign('page', $data['page']);
		$this->assign('currentPage', $currentPage);
		$this->assign('totalPages', $data['total_pages']);

		$listTpl = 'artists';
		return $this->fetch('/' . $listTpl);
    }

}
