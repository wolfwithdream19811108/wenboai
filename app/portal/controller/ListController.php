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

class ListController extends HomeBaseController
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

		if($times=='长卷'){

			$portalPostModel = new PortalPostModel();
			$data = $portalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select();
			$museums=array();
			$PortalMuseumModel = new PortalMuseumModel();
			foreach($data as $val){
				$museum = $PortalMuseumModel->where('id',$val['post_museum_id'])->find();
				array_push($museums,$museum);

			}

			//获取和分类相关联的所有标签和标签类型
			$tags = array();//不知道有多少个类别，暂时创建10个，肯定不会超出的

			$tagTypeArray=array();

			$PortalTagTypeCategorymModel = new PortalCategoryTagtypeModel();
			$tagTypeArray = $PortalTagTypeCategorymModel->where('category_id',$category_id)->select();
			
			$PortalTagTagTypeModel = new PortalTagTagtypeModel();
			$PortalTagModel = new PortalTagModel();
			foreach($tagTypeArray as $key =>$val){
				//找此标签类别向下的所有标签id
				$tags[$key] = $PortalTagTagTypeModel->where('tag_type_id',$val['tag_type_id'])->select();
				//为每个tag寻找名字
				foreach($tags[$key] as $index => $tag){
					$tagId = $tags[$key][$index]['tag_id'];
					$tags[$key][$index] = $PortalTagModel->where('id',$tagId)->find()->getData();
					//dump($tags[$key][$index]);
				}


			}

		   //$portalCategoryModel = new PortalCategoryModel();
		  //  $category = $portalCategoryModel->where('id', $id)->where('status', 1)->find();
			$params = array();
			$params['age'] = $times;
			$params['museum_id'] = $museum_id;
			$params['category_ids'] = $category_id;
			$params['page'] = 300;
			$page=$params['page'] ;
			
		

	

		  $articles = $portalPostModel->alias('post')
			    ->where('post.firstpic_width > 10*post.firstpic_height AND post.firstpic_width=9500' )//加上这句降低查询速度
				->group('post.id');

			$return = [];

			if (empty($page)) {
				$articles = $articles->limit(50)->select();

				if (!empty($relation) && !empty($articles['items'])) {
					$articles->load($relation);
				}

				$return['articles'] = $articles;
			} else {

				if (is_array($page)) {
					if (empty($page['list_rows'])) {
						$page['list_rows'] = 10;
					}

					$articles = $articles->paginate($page);
				} else {
					$articles = $articles->paginate(intval($page));
				}

				if (!empty($relation) && !empty($articles['items'])) {
					$articles->load($relation);
				}

				$articles->appends(request()->get());
				$articles->appends(request()->post());

				$return['articles']    = $articles->items();
				$return['page']        = $articles->render();
				$return['total']       = $articles->total();
				$return['total_pages'] = $articles->lastPage();
			}




		    $this->assign('category', $category_id);
			$this->assign('age', $times);
			$this->assign('museums', $museums);
			$this->assign('articles', $return['articles']);
			$this->assign('page', $return['page']);
			$this->assign('tagTypes', $tagTypeArray);
			$this->assign('tags', $tags);
			$this->assign('total_pages', $return['total_pages']);
			$listTpl = 'list';
		   // $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

			return $this->fetch('/' . $listTpl);
			



		}else{
					
			$PortalPostModel = new PortalPostModel();
			$data = $PortalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select();
			
			
            $cacheKey = md5(json_encode($where));
            //$museums = cache($cacheKey);        
            if(empty($museums)){
    			$PortalMuseumModel = new PortalMuseumModel();
                $postMuseumIds = array_unique(array_column($data, 'post_museum_id'));

                // 初始化博物馆数组
                $museums = [];
                
                if (!empty($postMuseumIds)) {
                    // 一次性查询所有可见的博物馆
                    $museumList = PortalMuseumModel::where('is_visible', 1)
                        ->whereIn('id', $postMuseumIds)
                        ->select()
                        ->column('*', 'id'); // 将结果以ID为键映射
                    
                    // 遍历原始数据并匹配博物馆
                    foreach ($data as $val) {
                        $museumId = $val['post_museum_id'];
                        $museums[] = $museumList[$museumId] ?? null;
                    }
                }
                
                // 若postMuseumIds为空，$museums保持为空数组    			

    			cache($cacheKey, $museums);
            }			
			
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
			$this->assign('tagTypes', $tagTypeArray);
			$this->assign('tags', $tags);
			$this->assign('total_pages', $total_pages);
			$listTpl = 'list';
		   // $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

			return $this->fetch('/' . $listTpl);
			}

    }





}
