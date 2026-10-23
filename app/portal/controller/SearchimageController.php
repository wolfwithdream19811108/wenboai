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
use app\portal\model\PortalAuctionPostModel;
use app\portal\model\PortalCategoryTagtypeModel;
use app\portal\model\PortalTagTagtypeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;

class SearchImageController extends HomeBaseController
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

        $imageUrl = $this->request->get('image_url');
        $searchType = $this->request->get('search_type');

        
        if (empty(cmf_get_current_user_id())) {
        $this->error('此功能需要登陆后才可使用',url('user/login/index'));
        }
        
        $result=$error=$articles='';
        $image = null;        
        if(empty($imageUrl)){
            $error = "请上传要识别的图片";
            $this->assign('error', $error);
    		$this->assign('imageUrl', $imageUrl);
            return $this->fetch('/search_image');
        }else{

            if($searchType=="auction"){
                $result = $this->search($imageUrl,$error,$articles,$searchType);
            }else{
                $result = $this->search($imageUrl,$error,$articles,$searchType);
            }    

    
    		$this->assign('error', $error);
    		$this->assign('imageUrl', $imageUrl);
    		if(!$result){
    		    $this->error($error);
    		}else{
               if($searchType=="auction"){
          
                    $prices = array_filter(array_column($articles,'price'));
                    rsort($prices);
                    $sortScoreArticles = $articles;
                    $data = $sortScoreArticles[0]->getData();
                    array_multisort(array_column($articles,'price'),SORT_DESC,$articles);
                    array_multisort(array_column($sortScoreArticles,'score'),SORT_DESC,$sortScoreArticles);
                    $this->assign('maxPrice',$prices[0]);
                    $this->assign('minPrice',$prices[count($prices)-1]);
                    $this->assign('midPrice',$prices[count($prices)/2]);
                    $this->assign('averagePrice',intval(array_sum($prices)/count($prices)));
                    $this->assign('worksNum',count(array_column($articles,'price')));
                    $estimatePrice = $data['price'];
                    if(is_null($estimatePrice)){
                        $estimatePrice = $data['estimate'];
                    }
                    $this->assign('estimatePrice',$estimatePrice);
                    $this->assign('articles', $articles);
                    return $this->fetch('/search_auction_image');	
                }else{
                  
                    $this->assign('articles', $articles);
                    return $this->fetch('/search_image');	
                }
                
    		}            
        }
   
        


 
 



    }
    
/*

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
        
        
    }*/

    public function search($imageUrl,&$error,&$articles,$searchType="artworks")
    {
        
        $token = $this->getAccessToken($searchType);
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/realtime_search/similar/search?access_token=' . $token;
        $error = '';

 
        if(strpos($imageUrl,"image.hongmengyishu.com")){
            //如果是又拍云，则先保存到本地上
            $tempurl = "./upload/searchimage/".strval(time()).".jpg";
            cmf_downloadRemoteFile($imageUrl,$tempurl);
            $imageUrl = "https://www.hongmengyishu.com".$tempurl;
        }
        else if(strpos($imageUrl,"www.hongmengyishu.com")){ 
            //如果是用户本地上传的
            if(!strpos($imageUrl,"upload/portal")){
                $error = "无效的图片地址，请填入有效的图片地址";
                return false; 
            }
        }else{ 
            $error = "无效的图片地址，请填入有效的图片地址";
            return false; 
        }    
        


        if ($imageUrl!="" && filter_var($imageUrl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            $opts = array(
              'http'=>array(
                'ignore_errors'=> true,
              )
            );
            $context = stream_context_create($opts);            
            
            
            
            $contents = file_get_contents($imageUrl,false, $context);
            $base64 = base64_encode($contents);
            if($searchType=="auction"){
                $searchNum = 1000;//最大只能搜索1000个
            }else{
                $searchNum = 200;
            }            
            

            $bodys = array(
                'image'=>$base64,
                /*'url' => $imageUrl,*/
                'rn' => $searchNum//只搜索前500个
            );   
          
        
           
            $res = $this->request_post($url, $bodys);
            $data=json_decode($res,true);
     
    		
    		if(!empty($data['error_msg'])){
    		    $error = '匹配发生错误：'.$data['error_msg'];
    		    
    		}else{
    		    
        		$sameImageList = $data['result'];
        		if(empty($sameImageList)){
        		    $error = '没有匹配到结果';
        		}else{
                    if($searchType=="auction"){
                        $postModel = new PortalAuctionPostModel();
                        $validImageList = array();
                        foreach ($sameImageList as $val){
                            if($val["score"]>0.6){
                                array_push($validImageList,$val);
                            }
                        }
                    }else{
                        $postModel = new PortalPostModel();
                        $validImageList = $sameImageList;
                    }
        			
        			$articles = Array();
        			
        			foreach($validImageList as $image){
                        $brief = json_decode($image["brief"],true);
                        $articleId = intval($brief ["articleId"]);
        				$params['id']  = $articleId;
                        $article = $postModel->where($params)->select();
        				if(!empty($article)){
        				//	$article = $article->toArray();
        				    $article[0]["score"]=$image["score"];
        					array_push($articles,$article[0]);	
        				}
        					
        			}
            		$error = "搜索成功";
        			return true;

        		}       		    
    		    
    		}
    		          
        }else if ($imageUrl!=""){
           // var_dump($$imageUrl);
            $error = '无效的图片地址';
            return false;       
        }
        
        
    }    
     /***
     * ajax获得数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStyleShift()
    {
		$imageUrl = $this->request->get('image_url');
        $shiftType = $this->request->get('shifttype');
	
	    $token = $this->getAccessToken("stylechange");
        $url = 'https://aip.baidubce.com/rest/2.0/image-process/v1/style_trans?access_token=' . $token;
        $error = '';
        $return_data = []; 

        
        if(strpos($imageUrl,"image.hongmengyishu.com")){
            //如果是又拍云，则先保存到本地上
            $tempurl = "./upload/searchimage/".strval(time()).".jpg";
            cmf_downloadRemoteFile($imageUrl,$tempurl);
            $imageUrl = "https://www.hongmengyishu.com".$tempurl;
        }
        else if(strpos($imageUrl,"www.hongmengyishu.com")){
            //如果是用户本地上传的
            if(!strpos($imageUrl,"upload/searchimage")){
                $error = "无效的图片地址，请填入有效的图片地址";
                $return_data['error'] = $error;
                return json_encode($return_data);
            }
        }       
        


        if ($imageUrl!="" && filter_var($imageUrl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            $base64 = base64_encode(file_get_contents($imageUrl));
            

            $bodys = array(
                'image'=>$base64,
                'option' => $shiftType
            );   
          
        
    
            $res = $this->request_post($url, $bodys);
            $data=json_decode($res,true);
        
    		if(!empty($data['error_msg'])){
    		    $error = '匹配发生错误：'.$data['error_msg'];
    		    $return_data['error'] = $error;
                return json_encode($return_data);
    		}else{
    		    
            	$error = "风格迁移成功";
            	$image="data:image/jpeg;base64,".$data['image'];
        		$return_data['error'] = $error;
        		$return_data['image'] = $image;
                return json_encode($return_data);
        	}       		    
    	}else if ($imageUrl!=""){
           // var_dump($$imageUrl);
            $error = '无效的图片地址';
            $return_data['error'] = $error;
            return json_encode($return_data);    
        }
		
	


    }
    
  /***
     * ajax获得数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStyleCustomize()
    {
		$imageUrl = $this->request->get('image_url');
        $styleid = $this->request->get('styleid');
	
	    $token = $this->getAccessToken("stylechange");
        $url = 'https://aip.baidubce.com/rest/2.0/image-process/v1/style_trans?access_token=' . $token;
        $error = '';
        $return_data = []; 

        
        if(strpos($imageUrl,"image.hongmengyishu.com")){
            //如果是又拍云，则先保存到本地上
            $tempurl = "./upload/searchimage/".strval(time()).".jpg";
            cmf_downloadRemoteFile($imageUrl,$tempurl);
            $imageUrl = "https://www.hongmengyishu.com".$tempurl;
        }
        else if(strpos($imageUrl,"www.hongmengyishu.com")){
            //如果是用户本地上传的
            if(!strpos($imageUrl,"upload/searchimage")){
                $error = "无效的图片地址，请填入有效的图片地址";
                $return_data['error'] = $error;
                return json_encode($return_data);
            }
        }       
        


        if ($imageUrl!="" && filter_var($imageUrl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            $base64 = base64_encode(file_get_contents($imageUrl));
            

            $bodys = array(
                'image'=>$base64,
                'option' => $shiftType
            );   
          
        
    
            $res = $this->request_post($url, $bodys);
            $data=json_decode($res,true);
        
    		if(!empty($data['error_msg'])){
    		    $error = '匹配发生错误：'.$data['error_msg'];
    		    $return_data['error'] = $error;
                return json_encode($return_data);
    		}else{
    		    
            	$error = "风格迁移成功";
            	$image="data:image/jpeg;base64,".$data['image'];
        		$return_data['error'] = $error;
        		$return_data['image'] = $image;
                return json_encode($return_data);
        	}       		    
    	}else if ($imageUrl!=""){
           // var_dump($$imageUrl);
            $error = '无效的图片地址';
            $return_data['error'] = $error;
            return json_encode($return_data);    
        }
		
	


    }    
    
     public function styleShift()
    {
        
        $imageUrl = $this->request->get('image_url');
 
        $token = $this->getAccessToken("stylechange");
        $url = 'https://aip.baidubce.com/rest/2.0/image-process/v1/style_trans?access_token=' . $token;
        $error = '';

        
        if(strpos($imageUrl,"image.hongmengyishu.com")){
            //如果是又拍云，则先保存到本地上
            $tempurl = "./upload/searchimage/".strval(time()).".jpg";
            cmf_downloadRemoteFile($imageUrl,$tempurl);
            $imageUrl = "https://www.hongmengyishu.com".$tempurl;
        }
        else if(strpos($imageUrl,"www.hongmengyishu.com")){
            //如果是用户本地上传的
            if(!strpos($imageUrl,"upload/searchimage")){
                $error = "无效的图片地址，请填入有效的图片地址";
                $this->assign('error', $error);
                return $this->fetch('/style_change');
            }
        }       
        

        $image = 0;
        if ($imageUrl!="" && filter_var($imageUrl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            $base64 = base64_encode(file_get_contents($imageUrl));
            

            $bodys = array(
                'image'=>$base64,
                'option' => "wave"
            );   

    
            $res = $this->request_post($url, $bodys);
            $data=json_decode($res,true);
        
    		if(!empty($data['error_msg'])){
    		    $error = '发生错误：'.$data['error_msg'];
    		}else{
    		    
            	$error = "风格迁移成功";
            	$image="data:image/jpeg;base64,".$data['image'];
            	
        	}       		    
    	}else if ($imageUrl!=""){
           // var_dump($$imageUrl);
            $error = '无效的图片地址';
        }
        $this->assign('imagebase64', $image);
        $this->assign('error', $error);
        $this->assign('imageUrl', $imageUrl);
        return $this->fetch('/style_change');       
        
        
    }       
    
    public function aiimage()
    {
        
        $imageUrl = $this->request->get('image_url');
        return $this->fetch('/aiimage_work');
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
    
    public function getAccessToken($searchType = "artworks"){
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        if($searchType=="auction"){
            $post_data['client_id']      = 'XCSiTNPezPGIs08QMrPTrC2h';
            $post_data['client_secret'] = '9tNRfCYERtpGAcyWZnsZY7FzhZnTWYDx';             
        }if($searchType=="stylechange"){
            $post_data['client_id']      = 'yQzCqxSt1ScVtBl7RGtkUqMF';
            $post_data['client_secret'] = 'Zjl9FAgeQ5eMgtuE7rhAmC9xAMsQXmvx'; 
        }else{
            $post_data['client_id']      = 'QIyPITvvtb5NAcQ6PDSEzGz1';
            $post_data['client_secret'] = 'x1KRGHjGXqGZUFtCwjpabCO3VDdmTeGr';           
        }

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
