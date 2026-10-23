<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\portal\controller;

use cmf\controller\RestBaseController;
use think\Db;

class AuthorController extends RestBaseController
{


    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 获取分类列表
     */
    public function index()
    {
        $params = $this->request->get();
        $where = [];
 
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true; 
        $artistList = cache('api_artists_'.json_encode($params));
        if(empty($artistList)){
            $artistList = array();
            $where['id'] = array('lt',24);
            $ages = Db::name('portal_age')->field('id,name')->where($where)->order('id')->select();
           
	    

           $where = [];
 			foreach ($ages as $age){
 			    
 			    $where['age'] = $age['name'];
 			    $where['nation'] ='中国';
 			  //  $where['works_num'] = array('gt',5);
 			  if($age['name']=='夏'||$age['name']=='周'){
 			      //如果是夏朝或者周朝，跳过
 			      continue;
 			  }  
 			    if($age['name']=='近现代'){
		            //$where['works_num'] = array('gt',5);
		            $artists = Db::name('portal_artist')->field('id,name,age,works_num')->where($where)->where('works_num', '<>', 0)->order('works_num')->limit(500)->select();
 			    }else{
 			        $where['works_num'] = array('gt',5);
				    $artists = Db::name('portal_artist')->field('id,name,age,works_num')->where($where)->order('works_num')->select();
 			    }
 			    if(count($artists)>0){
    				$temp['name'] = $age['name'];
    				$temp['authorlist'] = $artists;
    				array_push($artistList,$temp);
 			    }
				//$artistList[$age['name']] = $artists;
			}  
			
			
/*			$temp['name'] = '日本';
 		    $where['nation'] ='日本';
			$artists = Db::name('portal_artist')->field('id,name,age,works_num')->where($where)->order('works_num')->select();
			$temp['authorlist'] = $artists;	
			array_push($artistList,$temp);*/
			
			
/*			$temp['name'] = '西方';
 		    $where['nation'] ='国外';
			$artists = Db::name('portal_artist')->field('id,name,age,works_num')->where($where)->order('works_num')->select();
			$temp['authorlist'] = $artists;	
			array_push($artistList,$temp);	*/			
			
            
			cache('api_artists_'.json_encode($params), $artistList);
			$cacheflag=false;
        }  
        
        $response ['list'] =  $artistList;
        $response['cacheflag']=$cacheflag;

      

        $this->success('请求成功!', $response);
    }

    /**
     * 显示指定的分类
     * @param int $id
     */
    public function read($id)
    {
        $params       = $this->request->get();
        $params['id'] = $id;
        $data         = $this->$museumModel->getDatas($params);
        $this->success('请求成功!', $data);
    }


}