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
class ImageSearchController extends RestBaseController
{
    private $accessKey='';
    private $secretKey = '';
    private $client;
 

    public function __construct()
    {
        parent::__construct();
        $this->accessKey = $this->envValue('QINIU_ACCESS_KEY');
        $this->secretKey = $this->envValue('QINIU_SECRET_KEY');
        $auth = new Auth($this->accessKey, $this->secretKey);
        $this->client = new AppClient($auth);
        
    }

    private function envValue($name, $default = '')
    {
        return cmf_service_config($name, $default);
    }

    /**
     * 显示所有图像库信息
     */
    public function groups()
    {
		

		$url = "http://ai.qiniuapi.com/v1/image/group";	
		$resp=$this->client->get($url);
		$this->success('请求成功!', $resp);
     

    }
    
        /**
     * 显示指定图像库 返回指定图像库中图片个数
     */
    public function group()
    {
		$groupId = $this->request->get('groupId');

		$url = "http://ai.qiniuapi.com/v1/image/group/".$groupId."/info";	
		$resp=$this->client->get($url);
		$this->success('请求成功!', $resp);
     

    }
    
            /**
     * 显示指定图像库所有图片信息
     */
    public function imagesOfGroup()
    {
		$groupId = $this->request->get('groupId');
		$marker = $this->request->get('marker');
		$limit = $this->request->get('limit');
		$url = "http://ai.qiniuapi.com/v1/image/group/".$groupId;
		
		if(!empty($marker) && !empty($limit)){
			$url = $url."?marker=".$marker."&limit=".$limit;
		}else if (!empty($limit)) {
			$url = $url."?limit=".$limit;
		}
		
		$resp=$this->client->get($url);
		$this->success('请求成功!', $resp);
     

    }
    
    
                /**
 //    * 显示指定图像库所有图片信息
 //    */
 //   public function searchInGroup()
 //   {
	// 	$imageUri = $this->request->get('imageUri');
	// 	$posturl = "http://ai.qiniuapi.com/v1/image/groups/search";



	// 	//$imageUri = "https://image.hongmengyishu.com/%E5%8E%8B%E7%BC%A9%E5%9B%BE/%E5%8D%9A%E7%89%A9%E9%A6%86%E8%97%8F/%E5%A4%A7%E8%8B%B1%E5%8D%9A%E7%89%A9%E9%A6%86/dish_PDF,A.840/dish_PDF,A.840_0.jpg!w900";

	// 	$data = array('uri'=>$imageUri);
	// //	$groups = array('101','116');
	// 	$groups = array('116');
	// 	$params = array('groups' => $groups,'limit' => 100,'threshold' => 0.5);
		
	// 	$body = array('data' => $data, 'params' => $params);
	// 	$body = json_encode($body);
		
	// 	$resp = $this->client->post($posturl,$body);
		
	// 	$sameImageList = $resp[0]['result'];
		
	// 	if(empty($sameImageList)){
	// 		$this->error('请求失败!', '没有匹配到结果');
	// 	}else{

	// 		$postModel = new PortalPostModel();
	// 		$articles = Array();
	// 		foreach($sameImageList as $image){
	// 			$start = stripos($image['id'],"_")+1;
	// 			$end = strripos($image['id'],"_");
	// 			$articleId = substr($image['id'],$start,$end-$start);
	// 			$params['id']  = $articleId;
	// 			$article = "";
				
	// 			// $article = cache('api_article_'.json_encode($params));
	// 			if($article==""){
	// 				$article = $postModel->getDatas($params)->toArray();
	// 				cache('api_article_'.json_encode($params), $article);
	// 			} 
	// 			array_push($articles,$article);		
	
	// 		}
			
	// 		$this->success('请求成功!', $articles);			
	// 	}

 //   }
    
    
    
                /**
     * 显示指定图像库所有图片信息
     */
    public function searchInGroup($groupId,$imageUri)
    {
		$posturl = "http://ai.qiniuapi.com/v1/image/groups/search";
		$data = array('uri'=>$imageUri);
		$groups = array(strval($groupId));
		$params = array('groups' => $groups,'limit' => 50,'threshold' => 0.2);
		
		$body = array('data' => $data, 'params' => $params);
		$body = json_encode($body);
		
		$resp = $this->client->post($posturl,$body);
		
		
		$sameImageList = $resp[0]['result'];
		return $sameImageList;
	

    }
    
 

  public function multiple_threads_request($groups,$imageUri,$wait_usec = 0){
  	
    	$result = [];

        $mh = curl_multi_init(); //初始化一个curl批处理句柄资源
        $handle = [];
        $posturl = "http://ai.qiniuapi.com/v1/image/groups/search";
    
     	$wait_usec = 500000;
 
        foreach ($groups as $k=>$val)
        {
    
			$t1 = microtime(true);
			$groupId = $val['id'];
			$data = array('uri'=>$imageUri);
			$groupId = array(strval($groupId));
			$params = array('groups' => $groupId,'limit' => 50,'threshold' => 0.5);
			$body = array('data' => $data, 'params' => $params);
			$body = json_encode($body);       	
			
			$token = $this->client->getToken($posturl, "POST", $body);
			
			       $headers = array();
			    foreach ($token as $key => $val) {
			        array_push($headers, "$key: $val");
			    }     
			
			$userAgent = $this->client->getUserAgent();
			// $curl_array[$k] = curl_init($posturl);
			$handle[$k] = curl_init($posturl);
			
			$options = array(
			CURLOPT_USERAGENT => $userAgent,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER => true,
			CURLOPT_NOBODY => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_URL => $posturl,
			);         
			$options[CURLOPT_HTTPHEADER] = $headers;
			$options[CURLOPT_POSTFIELDS] = $body;
			curl_setopt_array($handle[$k], $options);
			curl_multi_add_handle($mh, $handle[$k]);
        }
        
        
        $running = 0;
        do {
            //解析一个curl批处理句柄，curl_multi_exec函数有两个参数，第一个表示一个批处理句柄资源，第二个参数是一个应用值参数，表示
            //剩余需要处理的单个的curl句柄资源。
            curl_multi_exec($mh, $running);//持续调用curl_multi_exec
            if ($wait_usec > 0) /* 每个 connect 要间隔多久 */
                usleep($wait_usec); // 250000 = 0.25 sec
        } while ($running > 0);

        foreach ($groups as $k=>$val)
        {
            $return = curl_multi_getcontent($handle[$k]);//根据需要循环调用curl_multi_getcontent

            $return[$k] = (curl_errno($handle[$k]) == 0) ? $return : false;
            $http_code = curl_getinfo($handle[$k],CURLINFO_HTTP_CODE); //获取http错误码
            $curl_error = curl_error($handle[$k]); //获取curl错误码
            if($http_code==200){
            	$start = strpos($return,'{"code"');
            	$end = strrpos($return,'}');
            	$response = substr($return,$start,$end-$start+1);
            	$response = json_decode($response,true);
            	if($response['code'] == 0 && !empty($response['result'])){
            		$list = $response['result'];
            		foreach ($list as $item){
            			array_push($result,$item);
            		}
            	}
            	
            	
            }
            //移除curl批处理句柄资源中的某个句柄资源，curl_multi_remove_handle()函数有两个参数，第一个参数表示一个curl批处理句柄资源，第二个参数表示一个单独的curl句柄资源。
            curl_multi_remove_handle($mh,$handle[$k]);
        }
        curl_multi_close($mh);
    
    return $result;
}
    
                /**
     * 显示指定图像库所有图片信息
     */
  /*  public function search()
    {

		$imageUri = $this->request->get('imageUri');
		$groupId = $this->request->get('groupId');
		$posturl = "http://ai.qiniuapi.com/v1/image/groups/search";
		
		if(empty($groupId)){
			//如果没有
			$groups = Db::name("portal_category")->field('id')->select()->toArray();
		}else{
			$groups[0]['id'] = $groupId;
		}
		$sameImageList = array();
		


		
		$sameImageList = $this->multiple_threads_request($groups,$imageUri);


		
		if(empty($sameImageList)){
			$this->error('请求失败!', '没有匹配到结果');
		}else{

			$postModel = new PortalPostModel();
			$articles = Array();
			foreach($sameImageList as $image){
				$start = stripos($image['id'],"_")+1;
				$end = strripos($image['id'],"_");
				$articleId = substr($image['id'],$start,$end-$start);
				$params['id']  = $articleId;
				$article = "";
				
			    $article = cache('api_article_'.json_encode($params));
				if($article==""){
					$article = $postModel->getDatas($params);
					if(!empty($article)){
						$article = $article->toArray();
						cache('api_article_'.json_encode($params), $article);
					}else{
					}
				} 
				array_push($articles,$article);		
	
			}
			
			$this->success('请求成功!', $articles);			
		}

    }*/
    
    
         /**
     * 发起http post请求(REST API), 并获取REST请求的结果
     * @param string $url
     * @param string $param
     * @return - http response body if succeeds, else false.
     */
    public function search()
    {
        
        $token = $this->getAccessToken();
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/realtime_search/similar/search?access_token=' . $token;
       // $imageUrl="http://www.hongmengyishu.com/upload/20211103/699405638b5bccb41a90ee6ac0e4ebb7.jpg";
        $imageUrl = $this->request->get('imageUri');
        $category_id = intval($this->request->get('category'));
       // dump($category_id);
       if($category_id!=null){
            $bodys = array(
                'url' => $imageUrl,
                'tags' => $category_id,
                'pn' => 1,
                'rn' => 200//只返回前200个搜索结果
                
            );     
       }else{
             $bodys = array(
                'url' => $imageUrl,
                'pn' => 1,
                'rn' => 200//只返回前200个搜索结果
                
            );             
       }
        
    

        $res = $this->request_post($url, $bodys);
        $data=json_decode($res,true);
		
		$sameImageList = $data['result'];


		
		if(empty($sameImageList)){
			$this->error('请求失败!', '没有匹配到结果');
		}else{

			$postModel = new PortalPostModel();
			$articles = Array();
			foreach($sameImageList as $image){
                $brief = json_decode($image["brief"],true);
                $articleId = intval($brief ["articleId"]);
				$params['id']  = $articleId;
				$article = "";
				
			    $article = cache('api_article_'.json_encode($params));
				if($article==""){
					$article = $postModel->getDatas($params);
					if(!empty($article)){
						$article = $article->toArray();
						cache('api_article_'.json_encode($params), $article);
					}else{
					}
				} 
				array_push($articles,$article);		
	
			}

		
			
			$this->success('请求成功!', $articles);			
		}      
        
        
    }
    
    function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $postUrl = $url;
        $curlPost = $param;
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        
        
        
        return $data;
    }
    
    public function getAccessToken(){
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = 'QIyPITvvtb5NAcQ6PDSEzGz1';
        $post_data['client_secret'] = 'x1KRGHjGXqGZUFtCwjpabCO3VDdmTeGr';
        $o = "";
        foreach ( $post_data as $k => $v ) 
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
        
        $res = $this->request_post($url, $post_data);
        $data=json_decode($res,true);
        return $data['access_token'];
    }
    
   
    

}
