<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\portal\controller;
use think\Db;
use cmf\controller\RestBaseController;
use api\portal\model\PortalPostModel;
use api\portal\model\PortalCategoryModel;

class CategoriesController extends RestBaseController
{
    protected $categoryModel;
    protected $postModel;

    public function __construct(PortalCategoryModel $categoryModel)
    {
        parent::__construct();
        $this->categoryModel = $categoryModel;
        
    }

    /**
     * 获取分类列表
     */
    public function index()
    {
        $params = $this->request->get();
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
      //  $data = cache('api_categories'.json_encode($params));
        if(empty($data)){
			$data   = $this->categoryModel->getDatas($params);
			cache('api_categories'.json_encode($params), $data);
			$cacheflag=false;
        }  
        

        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = $data;
        } else {
            $response = ['list' => $data];
            $response['cacheflag']=$cacheflag;
        }
        

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
        $data         = $this->categoryModel->getDatas($params);
        $this->success('请求成功!', $data);
    }

    /**
     * 获取指定分类的子分类列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function subCategories()
    {
        $id = $this->request->get('category_id', 0, 'intval');

        $categories = $this->categoryModel->where(['parent_id' => $id])->select();

        $this->success('请求成功', ['categories' => $categories]);

    }
    
    /**
     * 获取指定分类的器型列表
     */
    public function shape()
    {
        $id = $this->request->get('category_id', 0, 'intval');
        
        $data = cache('api_portal_category_shape'.json_encode($id));
        $artilceArray= array();
        $portalPostModel = new PortalPostModel();
        if(empty($data)){
			$categories = Db::name('portal_category_shape')->where('category_id', '=', $id)->select()->toArray();
    		foreach($categories as $category){
                    $params['post_type'] = 1;
                    $params['id'] = $category['product_id'];
                    
        			$artilce = $portalPostModel->Distinct(true)->where($params)->select()->toArray();
    		        array_push($artilceArray,$artilce[0]);
    
    		}			
			cache('api_portal_category_shape'.json_encode($id), $artilceArray);
        }        
        
        $response ['list'] =  $artilceArray;

        $this->success('请求成功!', $response);

    }    
    
    /**
     * 获取指定分类的器型列表
     */
    public function aiCategories()
    {
  
        
        $params = $this->request->get();
        $params['where']['isai']=1;

        
        //  先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
      //  $cacheflag = true; 
       // $data = cache('api_aicategories'.json_encode($params));
        if(empty($data)){
			$data   = $this->categoryModel->getDatas($params);
			cache('api_aicategories'.json_encode($params), $data);
			$cacheflag=false;
        }  
        

            $response = ['list' => $data];
            $response['cacheflag']=$cacheflag;

        

        $this->success('请求成功!', $response);

    }  
    
}