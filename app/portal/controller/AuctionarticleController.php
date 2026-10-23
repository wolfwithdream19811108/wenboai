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
use app\portal\model\PortalAuctionPostModel;
use app\portal\model\PortalTagModel;
use think\Db;

class AuctionarticleController extends HomeBaseController
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
        $articleId  = $this->request->param('id', 0, 'intval');
        $this->assign('articleId', $articleId);
  		
        $postService         = new PostService();;
        $article    = $postService->publishedAuctionArticle($articleId);
      //  $article = $article->getData();


        Db::name('portal_auction_post')->where('id', $articleId)->setInc('post_hits');
        hook('portal_before_assign_article', $article);
       
      	$this->assign('article', $article);
      	
      //	dump($article['more']['photos']);

      	$userId = cmf_get_current_user_id();
      	
      	 $category['name'] = "拍品";
      	$this->assign('category', $category); 
        $this->assign('userId', $userId);
       
      
     
        return $this->fetch("/auction_article");
    }
   

    // 文章点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');


        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('portal_post')->where('id', $articleId)->setInc('post_like');

            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

}
