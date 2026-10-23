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
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalMuseumModel;
use app\portal\model\PortalPostModel;
use app\portal\model\PortalCategoryTagtypeModel;
use app\portal\model\PortalTagTagtypeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;

class SimilarImagesController extends HomeBaseController
{
    /***
     * 文章列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {


        $token = $this->getAccessToken();
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/realtime_search/similar/search?access_token=' . $token;
        $imageUrl="http://www.hongmengyishu.com/upload/20211103/699405638b5bccb41a90ee6ac0e4ebb7.jpg";
        //$imageUrl = $this->request->get('imageUri');
        $bodys = array(
            'url' => $imageUrl
        );        
        
    

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
				

				$article = $postModel->where($params)->select();
			//	var_dump($article);
				if(!empty($article)){
				//	$article = $article->toArray();
					array_push($articles,$article[0]);	
				}
					
	
			}
			
		}   





     
		$this->assign('articles', $articles);
		$listTpl = 'similar_images';

		return $this->fetch('/' . $listTpl);



    }

public function search($imageUrl)
    {
        
        $token = $this->getAccessToken();
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/realtime_search/similar/search?access_token=' . $token;
       // $imageUrl="http://www.hongmengyishu.com/upload/20211103/699405638b5bccb41a90ee6ac0e4ebb7.jpg";
        $imageUrl = $this->request->get('imageUri');
        $bodys = array(
            'url' => $imageUrl
        );        
        
    

        $res = $this->request_post($url, $bodys);
        $data=json_decode($res,true);
        var_dump($data);
		
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
