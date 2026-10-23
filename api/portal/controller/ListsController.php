<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use api\portal\model\PortalCategoryModel;
use api\portal\model\PortalPostModel;
use cmf\controller\RestBaseController;
use think\Db;
class ListsController extends RestBaseController
{

    /**
     * [推荐文章列表]
     * @Author:   wuwu<15093565100@163.com>
     * @DateTime: 2017-07-17T11:36:51+0800
     * @since:    1.0
     */
    public function recommended()
    {
        $param           = $this->request->param();
        $portalPostModel = new PortalPostModel();

        $param['where'] = ['recommended' => 1];

        $articles = $portalPostModel->getDatas($param);

        $this->success('ok', ['list' => $articles]);
    }

    /**
     * [getCategoryPostLists 分类文章列表]
     * @Author:    wuwu<15093565100@163.com>
     * @DateTime: 2017-07-17T15:22:41+0800
     * @since:    1.0
     */
    public function getCategoryPostLists()
    {
        $categoryId = $this->request->param('category_id', 0, 'intval');
        $bigimg = $this->request->param('bigimg');
        $museum = $this->request->param('museum');
        $age = $this->request->param('age');
        

        $portalCategoryModel = new  PortalCategoryModel();

        $findCategory = $portalCategoryModel->where('id', $categoryId)->find();

        //分类是否存在
        if (empty($findCategory)) {
            $this->error('分类不存在！');
        }

        $param = $this->request->param();

/*        if(empty($param['order'])){
            $param['order']='-post.published_time';
        }*/
        
        $params['where']=null;

		if($bigimg==1){
			$params['where']['bigimg']=1;
		}
		if($museum>0){
			$params['where']['post_museum_id']=$museum;
		}
		if($age!=''){
			$params['where']['post_age']=$age;
		}




		$cacheflag = true;
		//$articles = cache('api_getCategoryPostLists_'.json_encode($param));
	//	$articles=null;
		if(empty($articles)){
			$articles = $portalCategoryModel->paramsFilter($param, $findCategory->articles()->alias('post'))->where($params['where'])->select();
			cache('api_getCategoryPostLists_'.json_encode($param), $articles);
			$cacheflag=false;
		}   

        

        if (!empty($param['relation'])) {
            if (count($articles) > 0) {
                $articles->load('user');
                $articles->append(['user']);
            }
        }
		$return['list'] = $articles;
		$return['cacheflag'] = $cacheflag;
			$return['$bigimg'] = $bigimg;
			$return['param'] = 	$params['where'];
        $this->success('ok', $return);
    }
    
    public function getMuseumAndAgeofCategory($categoryId){
    	$params['where']['post_category_id']=$categoryId;
    	$portalPostModel = new PortalPostModel();
        $museums = $portalPostModel->Distinct(true)->field('post_museum_id')->where('post_category_id',$categoryId)->select()->toArray();
        $ages = $portalPostModel->Distinct(true)->field('post_age')->where('post_category_id',$categoryId)->select()->toArray();


		$museumArray=array();
		foreach($museums as $val){
			if(!empty($val) && $val['post_museum_id']!=0){
				$museum = Db::name("portal_museum")->where('id',$val['post_museum_id'])->find();
				if(!is_null($museum)){
				    array_push($museumArray,$museum);
				}
			}

		}
		
		$ageArray=array();
		foreach($ages as $val){
			$age = Db::name("portal_age")->where('name',$val['post_age'])->find();
			if(!is_null($age)){
			    $ageArray[$age['id']]=$age['name'];
			}
			//array_push($ageArray,$age);

		}
		ksort($ageArray);
		
		$ages = array();
		$index = 0;
		foreach($ageArray as $val){
			$ages[$index] = $val;
			$index = $index + 1;
			//array_push($ageArray,$age);

		}	
		
		$filter['museums'] = $museumArray;
		$filter['ages'] = $ages;
		
		return $filter;
		

    }
    
     public function getMuseumAndAge($params){
    
    	$portalPostModel = new PortalPostModel();
        $museums = $portalPostModel->Distinct(true)->field('post_museum_id')->where($params)->select()->toArray();
        $ages = $portalPostModel->Distinct(true)->field('post_age')->where($params)->select()->toArray();


		$museumArray=array();
		foreach($museums as $val){
			if(!empty($val) && $val['post_museum_id']!=0){
				$museum = Db::name("portal_museum")->where('id',$val['post_museum_id'])->find();
				if(!is_null($museum)){
				    array_push($museumArray,$museum);
				}
			}

		}
		
		$ageArray=array();
		foreach($ages as $val){
			$age = Db::name("portal_age")->where('name',$val['post_age'])->find();
			if(!is_null($age)){
			    $ageArray[$age['id']]=$age['name'];
			}
			//array_push($ageArray,$age);

		}
		ksort($ageArray);
		
		$ages = array();
		$index = 0;
		foreach($ageArray as $val){
			$ages[$index] = $val;
			$index = $index + 1;
			//array_push($ageArray,$age);

		}	
		
		$filter['museums'] = $museumArray;
		$filter['ages'] = $ages;
		
		return $filter;
		

    }
    
    
      public function getCategoryAndAge($params){
    
    	$portalPostModel = new PortalPostModel();
        $categoryIds = $portalPostModel->Distinct(true)->field('post_category_id')->where($params)->select()->toArray();
        $ages = $portalPostModel->Distinct(true)->field('post_age')->where($params)->select()->toArray();


		$categoryArray=array();
		foreach($categoryIds as $val){
			if(!empty($val) && $val['post_category_id']!=0){
				$category = Db::name("portal_category")->where('id',$val['post_category_id'])->find();
				array_push($categoryArray,$category);
			}

		}
		
		$ageArray=array();
		foreach($ages as $val){
			$age = Db::name("portal_age")->where('name',$val['post_age'])->find();
			if(!is_null($age)){
			    $ageArray[$age['id']]=$age['name'];
			}
			//array_push($ageArray,$age);

		}
		ksort($ageArray);
		
		$ages = array();
		$index = 0;
		foreach($ageArray as $val){
			$ages[$index] = $val;
			$index = $index + 1;
			//array_push($ageArray,$age);

		}	
		
		$filter['categories'] = $categoryArray;
		$filter['ages'] = $ages;
		
		return $filter;
		

    }
    
    
    
    /**
     * 
     * @Author:    wuwu<15093565100@163.com>
     * @DateTime: 2017-07-17T15:22:41+0800
     * @since:    1.0
     */
    public function getPostLists()
    {
        $categoryId = $this->request->param('category_id', 0, 'intval');
        $bigimg = $this->request->param('bigimg');
        $museum = $this->request->param('museum');
        $age = $this->request->param('age');
        $author = $this->request->param('author');
        $params                       = $this->request->get();
        
        

        
/*        $portalCategoryModel = new  PortalCategoryModel();

        $findCategory = $portalCategoryModel->where('id', $categoryId)->find();

        //分类是否存在
        if (empty($findCategory)) {
            $this->error('分类不存在！');
        }    */  
        
        
        

        if(empty($param['order'])){
            $params['order']='quality';
        }
        
		if($museum>0){
			$params['where']['post_museum_id']=$museum;
		}
		if($bigimg==1){
			$params['where']['bigimg']=1;
		}

		if($age!=''){
			$params['where']['post_age']=$age;
		}

		if($categoryId!=''){
			$params['where']['post_category_id']=$categoryId;
		}
		if($author!=''){
			$params['where']['post_author']=$author;
		}

		$filter =$this->getMuseumAndAge($params['where']);
        $params['where']['delete_time']=0;



    

		$cacheflag = true;
		$articles = cache('api_getPostLists_'.json_encode($params));
	//	$articles=null;
		if(empty($articles)){
            $portalPostModel = new PortalPostModel();
            $articles = $portalPostModel->getDatas($params);
			cache('api_getPostLists_'.json_encode($params), $articles);
			$cacheflag=false;
		}   

        

        if (!empty($param['relation'])) {
            if (count($articles) > 0) {
                $articles->load('user');
                $articles->append(['user']);
            }
        }
		
		$return['cacheflag'] = $cacheflag;
		$return['bigimg'] = $bigimg;
		$return['param'] = 	$params['where'];
		$return['list'] = $articles;
		$return['filter'] = $filter;
        $this->success('ok', $return);
    }
    
    /**
     * [getMuseumPostLists 博物馆文章列表]
     * @Author:    wuwu<15093565100@163.com>
     * @DateTime: 2017-07-17T15:22:41+0800
     * @since:    1.0
     */
    public function getMuseumPostLists()
    {
        $categoryId = $this->request->param('category_id', 0, 'intval');
        $bigimg = $this->request->param('bigimg');
        $museum = $this->request->param('museum');
        $age = $this->request->param('age');
        $author = $this->request->param('author');
        $params                       = $this->request->get();
        
        

        

        if(empty($param['order'])){
            $params['order']='quality';
        }
        
		if($museum>0){
			$params['where']['post_museum_id']=$museum;
		}
		if($bigimg==1){
			$params['where']['bigimg']=1;
		}
		if($categoryId!=''){
			$params['where']['post_category_id']=$categoryId;
		}
		if($age!=''){
			$params['where']['post_age']=$age;
		}

        $params['where']['delete_time']=0; 
		if($author!=''){
			$params['where']['post_author']=$author;
		}

		
  



        $portalPostModel = new PortalPostModel();
        $categoryIds = $portalPostModel->Distinct(true)->field('post_category_id')->where('post_museum_id',$museum)->select()->toArray();
		$categoryArray=array();
		foreach($categoryIds as $val){
			if(!empty($val) && $val['post_category_id']!=0){
				$category = Db::name("portal_category")->where('id',$val['post_category_id'])->find();
				array_push($categoryArray,$category);
			}

		}
		
        $ages = $portalPostModel->Distinct(true)->field('post_age')->where($params['where'])->select()->toArray();
		$ageArray=array();
		foreach($ages as $val){
			$age = Db::name("portal_age")->where('name',$val['post_age'])->find();
			if(!is_null($age)){
			    $ageArray[$age['id']]=$age['name'];
			}
			//array_push($ageArray,$age);

		}

		ksort($ageArray);
		
		$ages = array();
		$index = 0;
		foreach($ageArray as $val){
			$ages[$index] = $val;
			$index = $index + 1;
			//array_push($ageArray,$age);

		}			
	
		$filter['categories'] = $categoryArray;
		$filter['ages'] = $ages;



		if($categoryId!=''){
			$params['where']['post_category_id']=$categoryId;
		}



		$cacheflag = true;
	//	$articles = cache('api_getPostLists_'.json_encode($params));
	//	$articles=null;
		if(empty($articles)){
            $portalPostModel = new PortalPostModel();
            $articles = $portalPostModel->getDatas($params);
			cache('api_getPostLists_'.json_encode($params), $articles);
			$cacheflag=false;
		}   

        

        if (!empty($param['relation'])) {
            if (count($articles) > 0) {
                $articles->load('user');
                $articles->append(['user']);
            }
        }
		$return['list'] = $articles;
		$return['cacheflag'] = $cacheflag;
		$return['$bigimg'] = $bigimg;
		$return['param'] = 	$params['where'];
		$return['filter'] = $filter;
        $this->success('ok', $return);
    }

}
