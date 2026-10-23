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
use app\ai\model\AiUserPointsModel;

class MobileController extends HomeBaseController
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
        
   $isLogin = cmf_is_user_login();
        if ($isLogin) {
            $userid = cmf_get_current_user_id();
            $aiUserPointsModel = new AiUserPointsModel();
            $currentPoints = $aiUserPointsModel->getUserPoints($userid); 
            $this->assign('points', $currentPoints);
        }    

        
        $base_modelsArray = array(
            array('model_id' => 0, 'model_name' => '优化', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/智能.jpg'),
            array('model_id' => 127, 'model_name' => 'SDXL', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/sdxl.jpg'),
            array('model_id' => 141, 'model_name' => '工美', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/通用.jpg'),
            array('model_id' => 301, 'model_name' => 'MJ5.1', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj52.jpg'),
            array('model_id' => 303, 'model_name' => 'MJ6', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj6.jpg')
        );       
        
        
      
        
        $everythingCategory = Db::name('portal_aiimage_everything')->distinct(true)->field('category')->select()->toArray();
        $everythingimgArray = Db::name('portal_aiimage_everything')->where('open', '=', 1)->select()->toArray();
        

        
        $craftModelList = Db::name('portal_aiimage_model')->where('model_type', '=', "craft")->where('open', '=', "1")->select()->toArray();
        $chineseloraList = Db::name('portal_aiimage_model')->where('model_type', '=', "chinese")->where('open', '=', "1")->select()->toArray();
        $materialloraList = Db::name('portal_aiimage_model')->where('model_type', '=', "material")->where('open', '=', "1")->select()->toArray();
        $patternModelList = Db::name('portal_pattern_category')->select()->toArray();
       

        $this->assign('craftModelList', $craftModelList);
        $this->assign('chineseloraList', $chineseloraList);
        $this->assign('materialloraList', $materialloraList);
        $this->assign('patternmodelList', $patternModelList);
        $this->assign('everythingCategory', $everythingCategory);
        $this->assign('everythingimgArray', $everythingimgArray);
		$this->assign('base_models', $base_modelsArray);
		$this->assign('isLogin', $isLogin);
        return $this->fetch("/aimobile");
    }
 


    // 文章点赞
    public function create()
    {
        $return_data = []; 
        		$return_data['error'] = '1122';
        		$return_data['image'] = '6677';



        $url = 'https://ston.6pen.art/release/open-custom-model/task';

        $data = array('prompt' => '清朝牡丹瓶','model_id' => 'da3f037727f18e88','multiply' => 1,'width' => 512,'height' => 512);
        
        // 初始化CURL会话
        $ch = curl_init();
        
        // 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // 发送POST数据
       
        
        
            // 设置HTTP头信息
        $headers = [
            'ys-api-key: f6a4e5856501b8202f5727abc75db83f',
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // 执行并获取HTTP响应
        $response = curl_exec($ch);
        
        // 关闭CURL会话
        curl_close($ch);
        
/*        // 处理响应数据
        $result = json_decode($response, true);
        return $result;
		*/
		
		
		
		
		return $response;
    }

    // 文章点赞
    public function query()
    {
        $return_data = []; 
        		$return_data['error'] = '1122';
        		$return_data['image'] = '6677';



        $url = 'https://ston.6pen.art/release/open-task';

        $data = array('prompt' => '清朝牡丹瓶','model_id' => 'da3f037727f18e88');
        
        // 初始化CURL会话
        $ch = curl_init();
        
        // 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // 发送POST数据
       
        
        
            // 设置HTTP头信息
        $headers = [
            'ys-api-key: f6a4e5856501b8202f5727abc75db83f',
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // 执行并获取HTTP响应
        $response = curl_exec($ch);
        
        // 关闭CURL会话
        curl_close($ch);
        
/*        // 处理响应数据
        $result = json_decode($response, true);
        return $result;
		*/
		
		
		
		
		return $response;
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
}
