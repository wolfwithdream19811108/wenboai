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
use app\portal\model\PortalMuseumModel;

class MapController extends RestBaseController
{

    /**
     * 获取分类列表
     */
    public function index()
    {
          $params       = $this->request->get();
          
          if(isset($params['minworks'])){ 
            $minworks = $params['minworks'];
          }else {
              $minworks = 10;//默认10件以上作品
          }   
          $museumModel = new PortalMuseumModel();
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
       // $data = cache('api_museums_map');
       
        $where['id'] = array(array('gt',4000),array('lt' ,1000), 'or');
        $where['works_num']= array('gt' ,$minworks);
        if(empty($data)){
            
			$data   = $museumModel->field('id,museum_name,is_visible,lng,lat,works_num')->where($where)->order('id ASC')->select();
		//	$data   = $museumModel->field('id,museum_name,is_visible,lng,lat,works_num')->where('id', '<', 1000)->whereOr('id', '>', 4000)->order('id ASC')->select();
/*			foreach($data as $index =>$item){
				$tagId = $tags[$key][$index]['tag_id'];
				$tags[$key][$index] = $PortalTagModel->where('id',$tagId)->find()->getData();
				//dump($tags[$key][$index]);
			}		     
		     */
		     
			cache('api_museums_map', $data);
			$cacheflag=false;
        }  
     

        $response ['list'] =  $data;
        $response['cacheflag']=$cacheflag;

        

        $this->success('请求成功!', $response);
    }

    


}