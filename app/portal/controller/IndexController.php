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
use think\Db; 

class IndexController extends HomeBaseController
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
        $questions = $this->getPreQuestion();
        $functionSettings  = cmf_get_option('function_settings');
        $this->assign("analysis_object", $functionSettings['analysis_object']);
        $this->assign('questions', $questions);
        return $this->fetch('/chatstreamwithhead');
	}
	
	public function dianku(){
        $category_id          = $this->request->param('category', 0, 'intval');
		$times                  = $this->request->param('times');
		$museum_id            = $this->request->param('museum');
		$page                 = $this->request->param('page');
		

		
		
		$where=array();
        if (empty($category_id)) {
            //如果没有类别，默认当前类别是国画，否则会在paginate函数中count * 慢查询
            $category_id = 101;

        } 		
		
        if (!empty($category_id)) {
            $where['post_category_id'] = $category_id;

        } 
        if (!empty($times)&&($times!='长卷')) {
            $where['post_age'] = $times;

        } 
        $where['delete_time'] = 0;
        
		$PortalPostModel = new PortalPostModel();
        //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $data = cache('ysyk_portal_post_museum_id_'.json_encode($where));
        if(empty($data)){
            $data = $PortalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select();
            cache('ysyk_portal_post_museum_id_'.json_encode($where), $data);
        }
    					
		
        //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $museums = cache('ysyk_portal_post_museums_'.json_encode($where));
        if(empty($museums)){
			$museums=array();
			$PortalMuseumModel = new PortalMuseumModel();
			
            // 提取所有 post_museum_id 并去重
            $postMuseumIds = [];
            foreach ($data as $item) {
                $postMuseumIds[] = $item->post_museum_id;
            }
            $postMuseumIds = array_unique($postMuseumIds);
            
            // 初始化博物馆数组
            $museums = [];
            
            if (!empty($postMuseumIds)) {
                // 一次性查询所有可见的博物馆
                $museums = PortalMuseumModel::where('is_visible', 1)
                    ->whereIn('id', $postMuseumIds)
                    ->select(); // 将结果以ID为键映射
                
    
            }   
            
            cache('ysyk_portal_post_museums_'.json_encode($where), $museums);
        }




	   //$portalCategoryModel = new PortalCategoryModel();
	  //  $category = $portalCategoryModel->where('id', $id)->where('status', 1)->find();
		$params = array();
		$params['age'] = $times;
		$params['museum_id'] = $museum_id;
		$params['category_ids'] = $category_id;
		$params['page'] = 60;
		$ApiService = new ApiService();		
		$data = ApiService::articles($params);
		$total_pages = $data['total_pages'];
		if($page>$total_pages && $page>1){
			$pageIsOver = true;
		}else{
			$pageIsOver = false;
		} 
        
        if($museum_id > 0){
          $idInMuseumList = false;
          foreach($museums as $key =>$val){
            if($museum_id == $val["id"]){
              $idInMuseumList = true;
              break;
            }
       	  }
        }else{
          $idInMuseumList = true;
        }
        


	    $this->assign('category', $category_id);
        $this->assign('pageIsOver', $pageIsOver);
        $this->assign('idInMuseumList', $idInMuseumList);
		$this->assign('age', $times);
		$this->assign('museums', $museums);
		$this->assign('articles', $data['articles']);
		$this->assign('page', $data['page']);
/*			$this->assign('tagTypes', $tagTypeArray);
		$this->assign('tags', $tags);*/
		$this->assign('total_pages', $total_pages);
		$listTpl = 'list';
	   // $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

		return $this->fetch('/' . $listTpl);	    
	}
	public function home(){
	    
        $questions = $this->getPreQuestion();
        $functionSettings  = cmf_get_option('function_settings');
        $this->assign("analysis_object", $functionSettings['analysis_object']);
        $this->assign('questions', $questions);
   
	    
	    
	    
	    
	    
	    
	    
	    
	    return $this->fetch('/home');
	}
    public function getPreQuestion()
    {
        $allQuestions = Db::name('portal_chat_question')
            ->field('question')
            ->select()->toArray();
        
        shuffle($allQuestions); // PHP端随机打乱
        $questions = array_column($allQuestions, 'question');
        return array_slice($questions, 0, 3);

    }
    public function test()
    {
        return $this->fetch('/test');
    }
    public function test2()
    {
        return $this->fetch('/test2');
    }

}
