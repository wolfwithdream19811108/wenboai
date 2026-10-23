<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\ai\controller;


use cmf\controller\HomeBaseController;
use Qcloud\Cos\Exception\ServiceResponseException;

class AidetectionController extends HomeBaseController
{
    
    //0表示待加密，1表示待解密，2表示已加密，3表示已解密

    const REGION = "ap-shanghai"; //替换为用户的 region，已创建桶归属的region可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket  
    const IMAGE_URL_PREFIX = "dianzicunzheng-1256990416.picsh.myqcloud.com/";
     
 
    public function index(){
   
       return $this->fetch("/aidetection");
    }
    public function severIsLive(){
      
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.aiornot.com/v1/system/live',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'Accept: application/json'
        ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        echo $response;        
    }
    
    public function tokenIsValid(){
      
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.aiornot.com/v1/credentials/tokens',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $this->envValue('AIORNOT_API_TOKEN'),
            'Accept: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        echo $response;       
    }    
    
    
     public function submitimage(){
         
   
    // 允许所有域名访问
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');


    // 检查是否有 GET 参数 'image_url' 被传递
        if (isset($_GET['image'])) {
            // 远程图片的 URL 从 GET 参数获取
            $imageUrl = $_GET['image'];  
        } else {
            // 如果没有提供 'image_url' 参数，可以设置一个默认值或返回错误
            $this->error('图片地址或者格式有误');
        }




        $curl = curl_init();
        
        // 远程图片的 URL
        //$imageUrl = 'https://dianzicunzheng-1256990416.picsh.myqcloud.com/test/99_2417kb.jpg'; // 替换为你的远程图片 URL
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.aiornot.com/v1/reports/image', // 注意移除末尾的单引号
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          // 发送 JSON 数据
          CURLOPT_POSTFIELDS => json_encode(array('object' => $imageUrl)),
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $this->envValue('AIORNOT_API_TOKEN'),
            'Content-Type: application/json', // 设置内容类型为 JSON
            'Accept: application/json'
          ),
        ));
        sleep(4);
        

		$response = cache('api_aiornot_2'.$imageUrl);
		if(empty($response)){
		    $response = curl_exec($curl);
			cache('api_aiornot_2'.$imageUrl, $response);
		}        
        
        
        //$response = json_decode($response);
        return $response;
        

        
        curl_close($curl);
        
     }

    private function envValue($name, $default = '')
    {
        return cmf_service_config($name, $default);
    }
     

    
    

}
