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
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use app\portal\model\PortalTagModel;
use think\Db;

class ImageController extends HomeBaseController
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
        $undecode_imgurl                  = $this->request->param('image');
		$articleId               = $this->request->param('articleId');
		$imgurl                  = cmf_link_urldecode($undecode_imgurl);
      	$thumbIndex                  = 0;
		$postService             = new PostService();
		#$url = "http://yishuyunku.test.upcdn.net/%E5%8E%8B%E7%BC%A9%E5%9B%BE/%E5%8D%9A%E7%89%A9%E9%A6%86%E8%97%8F/%E6%95%85%E5%AE%AB%E5%8D%9A%E7%89%A9%E9%99%A2M/%E8%B5%B5%E9%9C%96%E6%98%AD%E9%99%B5%E5%85%AD%E9%AA%8F%E5%9B%BE%E5%8D%B7/%E8%B5%B5%E9%9C%96%E6%98%AD%E9%99%B5%E5%85%AD%E9%AA%8F%E5%9B%BE%E5%8D%B7_compose";
		$url = str_ireplace(".jpg","",$imgurl,$count);
		$files_url = $url."_files/";
		$xml_url = $url.".xml";

		$article    = $postService->publishedArticle($articleId);

      	$real_imgurl = str_replace("高清","压缩图",$undecode_imgurl);
		$photos = $article["more"]["photos"];
        foreach ($photos as $photo) {
          if(strcmp($real_imgurl,$photo['url'])==0){
            break;
          }
          $thumbIndex = $thumbIndex+1;
      	}
        
		$img_urls= json_encode($photos);
		
		
		
		$xml_url = cmf_get_image_url_with_token($xml_url);
    
          
        $this->assign('xml_url', $xml_url);
		$this->assign('files_url', $files_url);
		$this->assign('img_urls', $img_urls);
		$this->assign('photos', $photos);
		$this->assign('articleId', $articleId);
        $this->assign('thumbIndex', $thumbIndex);

        $listTpl = 'image';
		
        return $this->fetch('/' . $listTpl);
    }
    


}
