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

class MuseumController extends RestBaseController
{
    protected $museumModel;

    public function __construct(PortalMuseumModel $museumModel)
    {
        parent::__construct();
        $this->$museumModel = $museumModel;
    }

    /**
     * 获取分类列表
     */
    public function index()
    {
        $params = $this->request->get();
        $museumModel = new PortalMuseumModel();
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
        $data = cache('api_museums_'.json_encode($params));
        if(empty($data)){
			$data   = $museumModel->where(['is_visible' => 1])->order('id ASC')->select();
			cache('api_museums_'.json_encode($params), $data);
			$cacheflag=false;
        }  
        


        $response ['list'] =  $data;
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
        $museumModel = new PortalMuseumModel();
        $params['id'] = $id;
        $data         = $this->$museumModel->where($params)->select();
        $this->success('请求成功!', $data);
    }


}