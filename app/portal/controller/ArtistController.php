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

use app\portal\service\ArtistService;
use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use app\portal\model\PortalTagModel;
use think\Db;

class ArtistController extends HomeBaseController
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
    
  
        $postService         = new PostService();
        $artistService       = new ArtistService();

        $artistId  = $this->request->param('id', 0, 'intval');
        $articles    = $postService->publishedArticlebyArtist($artistId);

        if (empty($articles)) {
            abort(404, '文章不存在!');
        }
        $this->assign('articles', $articles);

        $tplName = 'article';


        //Db::name('portal_post')->where('id', $articleId)->setInc('post_hits');


        //hook('portal_before_assign_article', $article);


        $artist = $artistService->Artist($artistId);
        $this->assign('artist', $artist);
        dump($artist);

        $artistTpl = 'artist';
        return $this->fetch('/' . $artistTpl);
    }

    public function getArticlesOfAuthor($author){
        $portalPostModel = new PortalPostModel();
        $posts            = $portalPostModel->where('post_author', $author)->select();
        $author_articles = array();
        foreach ($posts as $post){
            array_push($author_articles,$post->getData());
        }
        $this->assign('author_articles', $author_articles);

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
