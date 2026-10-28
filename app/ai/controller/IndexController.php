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
namespace app\ai\controller;


use cmf\controller\HomeBaseController;
use phpseclib\Crypt\RSA;
use think\Db;
use app\user\model\UserPointsModel;
use app\portal\model\PortalPostModel;
use app\portal\service\ApiService;
use app\imageai\service\AccessService;


class IndexController extends HomeBaseController
{
    /**
     * 文章详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */





    public function index()
    {
        if (cmf_is_user_login() && AccessService::isApiOnly(cmf_get_current_user_id())) {
            $this->error('此账号仅支持通过 API 创建图片', '/imageai/account/api-keys');
        }

        $isLogin = cmf_is_user_login();
        if ($isLogin) {
            $userid = cmf_get_current_user_id();
            $UserPointsModel = new UserPointsModel();
            $currentPoints = $UserPointsModel->getUserPoints($userid); 
            $this->assign('points', $currentPoints);
        }    

        
        $base_modelsArray = array(
            array('model_id' => 0, 'model_name' => '优化', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/智能.jpg'),
            array('model_id' => 127, 'model_name' => 'SDXL', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/sdxl.jpg'),
            array('model_id' => 141, 'model_name' => '工美', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/通用.jpg'),
/*            array('model_id' => 204, 'model_name' => '仿MJ', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/仿mj.jpg'),*/
            array('model_id' => 301, 'model_name' => 'MJ5.1', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj52.jpg'),
            array('model_id' => 303, 'model_name' => 'MJ6', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj6.jpg')
        );       
        
        
      
        
        $everythingCategory = Db::name('portal_aiimage_everything')->distinct(true)->field('category')->select()->toArray();
        $everythingimgArray = Db::name('portal_aiimage_everything')->where('open', '=', 1)->select()->toArray();
        
        $productArray = Db::name('ai_image_product')->select()->toArray();

        // 遍历产品，查询每个产品的提示信息
        foreach ($productArray as &$product) {
            $infos = Db::name('ai_image_product_prompt')
                ->where('product_id', $product['id'])
                ->select()
                ->toArray();
            
            // 将提示信息作为子项添加到产品中
            $product['infos'] = $infos;
        }
      //  dump($productArray);
    
        
        $craftModelList = Db::name('portal_aiimage_model')->where('model_type', '=', "craft")->where('open', '=', "1")->select()->toArray();
        
        $chineseloraList = Db::name('portal_aiimage_model')->where('model_type', '=', "chinese")->where('open', '=', "1")->select()->toArray();
        $materialloraList = Db::name('portal_aiimage_model')->where('is_material', '=', "1")->where('open', '=', "1")->select()->toArray();
        //$materialloraList = $craftModelList;
        $patternModelList = Db::name('portal_pattern_category')->select()->toArray();
        $functionSettings  = cmf_get_option('function_settings');
   
        $this->assign("open3d", $functionSettings['open_AI3D']);   
        $this->assign('craftModelList', $craftModelList);
        $this->assign('chineseloraList', $chineseloraList);
        $this->assign('materialloraList', $materialloraList);
        $this->assign('patternmodelList', $patternModelList);
        $this->assign('everythingCategory', $everythingCategory);
        $this->assign('everythingimgArray', $everythingimgArray);
        $this->assign('productArray', $productArray);
	//	$this->assign('models', $models);
		$this->assign('base_models', $base_modelsArray);
		$this->assign('isLogin', $isLogin);
		
		//$this->renderYunkuImgList();
        return $this->fetch("/aiimage");
    }
 
 
 public function renderYunkuImgList(){



		$params = array();
		$params['age'] = "明";
		$params['category_ids'] = 116;
		$params['page'] = 60;
		$ApiService = new ApiService();		
		$data = ApiService::articles($params);

		$total_pages = $data['total_pages'];
        

		$this->assign('articles', $data['articles']);
		$this->assign('page', $data['page']);
		$this->assign('total_pages', $total_pages);		

 }


    public function getToken()
    {
         $privateKey = "-----BEGIN RSA PRIVATE KEY---
        MIICd333qhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAILXvZ+ftZh1NXJjvPnHZqGbw+ZBoJIHS21N+EsExzwHwdf8nlzfg4bn6rtwSPa/d8Z1NupMUIUZlDONqPdwVqH0QMSkrSE1nA9iRaToW58NZvzEVz1+1NnH0KBwjxRR+pnod3oFEzS7XlJlC3F370nW1LCB9SG06ciU/A2dEDn3AgMBAAECgYB0FLEvz8KNatcrEB+VPnnoQMhRHMI3K++YHsd8ztVvhbZNR9q7rLUE1/ZhquGgwAekc1NwFJqPKz1Xa4OPWwQPygtNfQC0Uqfcy/1OgT7rfBsfm6TUHAb8mC+XnyK6BVA87xXAwciprNoU27d7KR4zTuTxUlwQs//9ISf+uVA66QJBAN7yvmychW5ODxxjbb7disqGMIsbhW9wZAhTbxy97duGPNfioZKwLtA0cltV12zkksJ42tOmk8uOpTR22/FVu3MCQQCWPXDDrCCs2v1syHyn1lrV+AHDqgN/DO1D1gt43t59+4B2hQWj/0yI+Yg8RW7bQnRYAj4l5vLTm1q+SyMt7G5tAkEAtOlNVFz+eS+mW2bjWwny4bxaD8dZU8Bjq83oT+5e4M/g913TtnB4zEKCuVLC6H3iCSSAjVaSf49Ux2nbt83VuQJBAI2IhfGf9wxAJRvU5zIJynrl72uPf0myONZ+D0Kn2jwesQ92EzFCsawcPw5gbqWZvhamzjtQjRNxuBOqnxKFOCUCQDu+oLP8NFpEvWs+o+2RMiuyO9VyLGG4gPnam27YdPgAPhC6SwIok+mcHzqCllHkCFk//3wugMagQOF6eATlaYo=
        -----END RSA PRIVATE KEY-----";
                $publicKey  = "-----BEGIN RSA PUBLIC KEY---
        MIGfMA0GCSqGSIb3443UAA4GNADCBiQKBgQCC172fn7WYdTVyY7z5x2ahm8PmQaCSB0ttTfhLBMc8B8HX/J5c34OG5+q7cEj2v3fGdTbqTFCFGZQzjaj3cFah9EDEpK0hNZwPYkWk6FufDWb8xFc9ftTZx9CgcI8UUfqZ6Hd6BRM0u15SZQtxd+9J1tSwgfUhtOnIlPwNnRA59wIDAQAB
        -----END RSA PUBLIC KEY-----";
        
        // 生成签名原文
        $appid        = 'wjmpv3333mxzqqrb8di';
        $timestamp    = time();  // 当前时间戳
        $originalData = array(
          "appId"     => $appid,
          "timestamp" => $timestamp
        );
        ksort($originalData);  // 按照字母顺序排序
        $originalText = json_encode($originalData);
        
        // 使用私钥进行签名
        $signer = new \phpseclib\Crypt\RSA();
        $signer->loadKey($privateKey);
        $signer->setHash('sha256');
        $signer->setSignatureMode(RSA::SIGNATURE_PKCS1);
        $sign = $signer->sign($originalText);
        $sign = base64_encode($sign);
        
        // 添加签名到请求头部中
        $headers = array(
          "Authorization: " . json_encode(array(
            "secretKeyVersion" => "1",
            "appId"            => $appid,
            "sign"             => $sign,
            "original"         => $originalText
          ))
        );
        
        // 发起 API 请求
        $url = "https://gate.wujiebantu.com/wj-open/v1/ai/model_base_infos";
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // 处理响应数据
        $data = json_decode($response, true);
    }   
    
    
    public function doodle()
    {
        
       // $imageUrl = $this->request->get('image_url');
        return $this->fetch('/doodle_design');
    }
    public function ref_img_list()
    {
        $category_id          = $this->request->param('category', 0, 'intval');
		$times                  = $this->request->param('times');
		$museum_id            = $this->request->param('museum');
		$page                 = $this->request->param('page');

		$where=array();
        if (!empty($category_id)) {
            $where['post_category_id'] = $category_id;

        } 
        if (!empty($times)&&($times!='长卷')) {
            $where['post_age'] = $times;

        } 
        $where['delete_time'] = 0;


			$PortalPostModel = new PortalPostModel();
			$data = $PortalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select();

			$params = array();
			$params['age'] = $times;
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
            

		    $this->assign('category', $category_id);
            $this->assign('pageIsOver', $pageIsOver);
			$this->assign('age', $times);
			$this->assign('articles', $data['articles']);
			$this->assign('page', $data['page']);
			$this->assign('total_pages', $total_pages);

			return $this->fetch('/ref_img_list');
   

    }
    public function ref_img_search_list(){
        $keyword = $this->request->param('keyword');

        if (empty($keyword)) {
            $this -> error("关键词不能为空！请重新输入！");
        }


        $articles = cache('ref_img_list_noes_'.json_encode($keyword));
        if(empty($articles)){
            $portalPostModel = new PortalPostModel();
     	    $where['post.post_title|post.post_author|post.post_age|post.post_museum']    = ['like', "%$keyword%"];
     	    $where['post.delete_time'] = 0;            
            $limit       = 1000;
            $articles = $portalPostModel->alias('post')->where($where)->order('post.id ASC')->group('post.id');
            $articles = $articles->limit($limit)->select(); 

            cache('ref_img_list_noes_'.json_encode($keyword), $articles);
        } 
        $this -> assign("articles", $articles);
        $this -> assign("keyword", $keyword);
        return $this->fetch('/ref_img_search_list');

    }      
    public function pattern_img_list(){
        
        $category          = $this->request->param('category', 0, 'strval');


		$where=array();
        if (!empty($category)) {
            $where['category'] = $category;

        } 

       
        $categorys = Db::name('portal_pattern_category')->select()->toArray();
 
        $articles = cache('pattern_img_list_'.json_encode($category));
        if(empty($articles)){
            $articles = Db::name('portal_pattern')->where($where)->select()->toArray();
            cache('pattern_img_list_'.json_encode($category), $articles);
        }    
        


	    $this->assign('category_list', $categorys);
		$this->assign('articles', $articles);


		return $this->fetch('/pattern_img_list');        
    
  

    } 
    public function suggestwork(){
        $architecturalQuestions = array(
        
        "建筑设计的核心是什么",
        
        "如何平衡功能与美学",
        
        "怎样处理建筑与环境的关系",
        
        "现代建筑与传统建筑如何融合",
        
        "建筑如何体现文化特色",
        
        "如何利用自然光优化空间体验",
        
        "建筑材料的选择有哪些考量",
        
        "建筑如何实现可持续性",
        
        "怎样设计适应气候的建筑",
        
        "如何考虑建筑的使用者需求",
        
        "建筑结构如何影响设计可能性",
        
        "如何创造有互动性的公共空间",
        
        "建筑如何应对城市化挑战",
        
        "怎样处理建筑的历史背景",
        
        "如何控制建筑项目的成本",
        
        "建筑设计中科技的角色是什么",
        
        "如何确保建筑的安全性",
        
        "怎样设计灵活多变的空间",
        
        "建筑与景观如何有机结合",
        
        "如何评估建筑的成功与否");
        $selectedQuestions = array_intersect_key($architecturalQuestions, array_flip(array_rand($architecturalQuestions, 3)));

        $this->assign('questions', $selectedQuestions);
        return $this->fetch('/suggestwork');       
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
    
}
