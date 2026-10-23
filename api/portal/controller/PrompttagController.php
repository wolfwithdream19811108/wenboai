<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use api\portal\model\PortalPostModel;
use cmf\controller\RestBaseController;
use api\portal\model\PortalTagModel;
use app\portal\model\PortalPostTagModel;
use think\Db;

class PrompttagController extends RestBaseController
{


    /**
     * 获取标签列表
     */
    public function getPrompt()
    {
        $params = $this->request->get();
        if(isset($params['category'])){ 
            $prompt_category = $params['category'];
        }else {
            $prompt_category = null;
        }          

        if($prompt_category){ 
		    $data = Db::name('portal_prompt_tag')->where('prompt_category', '=', $prompt_category)->select(true)->toArray();
        } else{ 
            $data = Db::name('portal_prompt_tag')->select(true)->toArray();
        }
        

        $response ['list'] =  $data;

        

        $this->success('请求成功!', $response);
    }
public function getPrompts()
{
    $params = $this->request->get();
    $first = isset($params['first']) ? intval($params['first']) : 0;
    $offset = isset($params['offset']) ? intval($params['offset']) : 10;
    $sorttype = isset($params['sorttype']) ? $params['sorttype'] : 'asc';
    $category = isset($params['category']) ? $params['category'] : null;
    
   if ($sorttype !== 'asc' && $sorttype !== 'desc') {
        $this->error('参数错误!');
    }

    $query = Db::name('portal_prompt_tag');
    if ($category) {
        $total = $query->where('prompt_category', '=', $category)->count();
        $data = $query->where('prompt_category', '=', $category)->order('use_frequency', $sorttype)->limit($first, $offset)->select(true)->toArray();
    }else{
        $total = $query->count();
        $data = $query->order('use_frequency', $sorttype)->limit($first, $offset)->select(true)->toArray();
    }
    

    $response['list'] = $data;
    $response['total'] = $total;

    $this->success('请求成功!', $response);
}



    public function searchPromptTags()
    {
        
        $params = $this->request->get();
        $first = isset($params['first']) ? intval($params['first']) : 0;
        $offset = isset($params['offset']) ? intval($params['offset']) : 10;
        $sorttype = isset($params['sorttype']) ? $params['sorttype'] : 'asc';
        $searchTag = isset($params['searchTag']) ? $params['searchTag'] : null;
        
       if ($sorttype !== 'asc' && $sorttype !== 'desc') {
            $this->error('参数错误!');
        }
    
        $query = Db::name('portal_prompt_tag');
        if ($searchTag) {
            $total = $query->where('tag', 'like', '%' . $searchTag . '%')->count();
            $data = $query->where('tag', 'like', '%' . $searchTag . '%')->order('use_frequency', $sorttype)->limit($first, $offset)->select(true)->toArray();
        }else{
            $this->error('searchTag为空');  
        }
        
    
        $response['list'] = $data;
        $response['total'] = $total;
    
        $this->success('请求成功!', $response);        

    }
    
    public function getPromptCategories()
    {
        $data = Db::name('portal_prompt_tag')->distinct(true)->column('prompt_category');
    
        $response['list'] = $data;
    
        $this->success('请求成功!', $response);
    }


    
}
