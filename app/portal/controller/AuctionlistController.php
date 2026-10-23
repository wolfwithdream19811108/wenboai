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
use app\portal\model\PortalAuctionPostModel;
use app\portal\model\PortalCategoryTagtypeModel;
use app\portal\model\PortalTagTagtypeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;

class AuctionlistController extends HomeBaseController
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
        $year         = $this->request->param('year', 0, 'intval');
		$company                 = $this->request->param('company');
		$page                 = $this->request->param('page');

		
					
		$PortalAuctionPostModel = new PortalAuctionPostModel();


		$params = array();
		$params['page'] = 60;
        if (!empty($year)) {
            $params['auction_year'] = $year;

        } 
        if (!empty($company)) {
            $params['auction_company'] = $company;

        } 		
		$ApiService = new ApiService();		
		$data = ApiService::auction_articles($params);
		$total_pages = $data['total_pages'];
		if($page>$total_pages && $page>1){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		} 
        
        
        $auction_meeting = array();
		foreach ($data['articles'] as $vo){
		    $article = $vo->getData();
		    if(!in_array($article['auction_meeting'],$auction_meeting)){
            array_push($auction_meeting,$article['auction_meeting']);

		    }
		}


        $year = array("2000","2001","2002","2003","2004","2005","2006","2007","2008","2009","2010",
        "2011","2012","2013","2014","2015","2016","2017","2018","2019","2020","2021");

        $this->assign('pageIsOver', $pageIsOver);

		$this->assign('articles', $data['articles']);
		$this->assign('year', $year);
		$this->assign('auction_meeting', $auction_meeting);
		$this->assign('page', $data['page']);
		$this->assign('total_pages', $total_pages);
		$listTpl = 'auction_list';
	   // $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

		return $this->fetch('/' . $listTpl);
		}







}
