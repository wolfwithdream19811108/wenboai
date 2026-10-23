<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use api\portal\model\PortalPostModel;
use cmf\controller\RestBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\model\UserCategoryModel;
use api\portal\model\UserModel;
use app\portal\service\ApiService;
use Qiniu\Auth;
use Qiniu\Http\Client;
use Qiniu\Rtc\AppClient;
use cmf\controller\PluginBaseController;
use plugins\qiniu\lib\Qiniu;
use think\Db;
class AiimageController extends RestBaseController
{
  
    /**
     * 获取model信息
     */
    public function getModelInfo()
    {
        $params = $this->request->get();
        $model_id = isset($params['model_id']) ? intval($params['model_id']) : 0;
        $data = Db::name('portal_aiimage_model')->where('id', '=', $model_id)->select()->toArray();
        $response['list'] = $data;
        $this->success('请求成功!', $response);		
        

    }
       /**
     * 显示model prompt
     */
    public function getModelPrompt()
    {
        $params = $this->request->get();
        $model_id = isset($params['model_id']) ? intval($params['model_id']) : 0;
        $data = Db::name('portal_prompt_tag')->where('model_id', '=', $model_id)->where('delete', '=', 0)->field('prompt_category,tag_pre_prompt,tag')->select()->toArray();
        // 创建一个新的数组，用于存储转换后的数据
        $transformedData = [];
        
        // 遍历查询结果，并对每个元素进行处理
        foreach ($data as $item) {
            // 将 prompt_category 和 tag_pre_prompt 拼接，并创建一个新对象
            $transformedItem = [
                'pre_prompt' => $item ["prompt_category"] .",". (!empty($item ["tag_pre_prompt"]) ? ',' . $item ["tag_pre_prompt"] : ''),
                'tag_prompt' => $item ["tag"]
            ];
            // 将新对象添加到结果数组中
            $transformedData[] = $transformedItem;
        }


        $response['list'] = $transformedData;
        $this->success('请求成功!', $response);		
        

    } 
    public function getEverything(){
        $category = $this->request->get('category', null, 'string');
        if(empty($category)){
           $everythingimgArray = Db::name('portal_aiimage_everything')->where('open', '=', 1)->select()->toArray();
        }else{
           $everythingimgArray = Db::name('portal_aiimage_everything')->where('category', '=', $category)->select()->toArray();
            
        }
        
        $response ['list'] =  $everythingimgArray;
        $this->success('请求成功!', $response);
    }
    public function getPoemSentence(){
        $category = $this->request->get('category', null, 'string');
        $poem = Db::name('ai_poemtoart')->where('category', '=', $category)->orderRaw('rand()')->find();
        $response ['list'] =  $poem;
        $this->success('请求成功!', $response);
    }   
     public function getBookDesign(){
        $category = $this->request->get('category', null, 'string');
        $book = $this->request->get('book', null, 'string');
        $where['category'] = $category;
        $where['book'] = $book;
        $bookDesignArray = Db::name('ai_booktoart')->where($where)->orderRaw('rand()')->limit(10)->select()->toArray();
        $response ['list'] =  $bookDesignArray;
        $this->success('请求成功!', $response);
    }
     public function getPoemDesign(){
        $category = $this->request->get('category', null, 'string');
        $poem = $this->request->get('poem', null, 'string');
        $poemmode = $this->request->get('poemmode', null, 'string');
        if($poemmode=='poemsea'){
            $DesignArray = Db::name('ai_poemtoart')->orderRaw('rand()')->limit(20)->select()->toArray();            
        }else{
            $where['category'] = $category;
            $where['poem'] = $poem;
            $DesignArray = Db::name('ai_poemtoart')->where($where)->orderRaw('rand()')->select()->toArray();
        }
        $response ['list'] =  $DesignArray;
        $this->success('请求成功!', $response); 

    }       
    public function isLogin()
    {
       
        if (cmf_is_user_login()) {
            $response ['user'] =  cmf_get_current_user();
            $this->success("请求成功",$response);
        } else {
            $this->success("请求成功",null);
        }
    }
}
