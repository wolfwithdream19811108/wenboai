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
use app\portal\model\PortalMuseumModel;
use app\portal\service\ApiService;
class MapController extends HomeBaseController
{
    public function index()
    {
        
        
        
          $museumModel = new PortalMuseumModel();
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
       // $data = cache('api_museums_map');
        if(empty($data)){
            
			//$data   = $museumModel->field('id,museum_name,is_visible,lng,lat,works_num')->where('id', '<', 1000)->whereOr('id', '>', 4000)->where('is_visible',1)->order('id ASC')->select();
			$data   = $museumModel->field('id,museum_name,is_visible,lng,lat,works_num')->where('id', '<', 1000)->whereOr('id', '>', 4000)->order('id ASC')->select();
			cache('api_museums_map', $data);
			$cacheflag=false;
        }  
     
        $this->assign('museums', $data);
        $listTpl = 'map';
		

        return $this->fetch('/' . $listTpl);


    }




}
