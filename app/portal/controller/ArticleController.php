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
use app\portal\model\PortalPostTagModel;
use app\portal\model\PortalPostEntityModel;
use app\portal\model\PortalImageColorModel;
use think\Db;

class ArticleController extends HomeBaseController
{
    /**
     * 文章详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */





    //模拟登录
    public function login_post($url, $cookie, $post) {
        $curl = curl_init();//初始化curl模块
        curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);//是否自动显示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源
    }
    //登录成功后获取数据
    public function get_content($url, $cookie) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //读取cookie
        $rs = curl_exec($ch); //执行cURL抓取页面内容
        curl_close($ch);
        return $rs;
    }

    public function test(){

    //设置post的数据
    $post = array (
        'email' => 'oschina账户',
        'pwd' => 'oschina密码',
        'goto_page' => '/my',
        'error_page' => '/login',
        'save_login' => '1',
        'submit' => '现在登录'
    );

    //登录地址
    $url = "http://m.oschina.net/action/user/login";
    //设置cookie保存路径
    $cookie = dirname(__FILE__) . '/cookie_oschina.txt';
    //登录后要获取信息的地址
    $url2 = "http://m.oschina.net/my";
    //模拟登录
    $this->login_post($url, $cookie, $post);
    //获取登录页的信息
    $content = $this->get_content($url2, $cookie);
    dump($content);
    //删除cookie文件
    @ unlink($cookie);
    //匹配页面信息
    $preg = "/<td class='portrait'>(.*)<\/td>/i";
    preg_match_all($preg, $content, $arr);
    $str = $arr[1][0];
    //输出内容
    echo $str;



    }

    /**
     * 将文本中的指定标签替换为带链接的a标签
     * 
     * @param string $text 原始文本
     * @param array $tags 需要替换的标签数组
     * @param string $urlPrefix 链接URL前缀（可选）
     * @param string $linkClass 链接CSS类名（可选）
     * @return string 处理后的文本
     */
    function replaceTagsWithLinks($text, $tags) {
        if (empty($text) || empty($tags)) {
            return $text;
        }
    
        // 按标签长度降序排序，确保长标签优先匹配
        usort($tags, function($a, $b) {
            return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
        });
    
        $processedTags = [];
        
        foreach ($tags as $tag) {
            // 跳过空标签或已处理的标签
            if (empty($tag) || in_array($tag, $processedTags)) {
                continue;
            }
            
            $processedTags[] = $tag;
            
            
            // 创建链接HTML
            $link = sprintf(
                '<a href="#"  onclick="popupRightMenu(event)">%s</a>',
                htmlspecialchars($tag, ENT_QUOTES, 'UTF-8')
            );
            
            // 使用正则表达式进行精确替换
            $pattern = '/\b' . preg_quote($tag, '/') . '\b/u';
            $text = preg_replace($pattern, $link, $text);
        }

        return $text;
    }
    public function index()
    {



        $portalCategoryModel = new PortalCategoryModel();
        $postService         = new PostService();
      
        $articleId  = $this->request->param('id',0, 'intval');
        $categoryId = $this->request->param('cid', null, 'intval');
        $this->assign('articleId', $articleId);
        $article    = $postService->publishedArticle($articleId, $categoryId);
        
        
        
        if (empty($article)) {
            abort(404, '文章不存在或者分类不存在或者相对应的文章分类关系不匹配!');
        }		

       // $prevArticle = $postService->publishedPrevArticle($articleId, $categoryId);
      //  $nextArticle = $postService->publishedNextArticle($articleId, $categoryId);

        

		
        if (empty($categoryId)) {
			/*
            $categories = $article['categories'];
            if (count($categories) > 0) {
                $this->assign('category', $categories[0]);
            } else {
                abort(404, '文章未指定分类!');
            }
			*/

			$categoryId = $article['post_category_id'];

            $category = $portalCategoryModel->where('id', $categoryId)->where('status', 1)->find();
            if (empty($category)) {
                abort(404, '文章分类不存在!');
            }
            $this->assign('category', $category->getData());

        } else {
            $category = $portalCategoryModel->where('id', $categoryId)->where('status', 1)->find();
            if (empty($category)) {
 				abort(404, '文章分类不存在!');
            }
            $this->assign('category', $category->getData());
        }
		


        Db::name('portal_post')->where('id', $articleId)->setInc('post_hits');
        hook('portal_before_assign_article', $article);
      
      	
      	$this->assign('description', strip_tags($article["post_content"]));
      	
      	
      	$portalColorModel = new PortalImageColorModel();
        $colors = $portalColorModel->where('post_id', $articleId)->select()->toArray();
        $this->assign('colors', $colors);
        //dump($colors);
  
        

        $portalTagModel = new PortalPostTagModel();
        $portalPostEntityModel = new PortalPostEntityModel();
        $tags = $portalTagModel->where('id', $articleId)->select();
        $postEntity = $portalPostEntityModel->field('entity')->where('id', $articleId)->find();
        $title_tags = array();
        $content_tags = array();
        $entity_tags = array();

        
        $tags = Db::table('cmf_portal_post_aitag')->field('tag')->where('post_id',$articleId)->order('importance_score DESC')->select(); 
        foreach ($tags as $tag){
                array_push($content_tags,$tag['tag']);

        }        
        //文章内容提炼出来的标签，得分在50以上
        $tags = $portalTagModel->distinct(true)->field('tag')->where('id', $articleId)->where('type', 1)->where('score', 'gt', 50)->order('score DESC')->select();
        foreach ($tags as $tag){
            if (mb_strlen($tag['tag'], 'UTF-8') >= 2){
                array_push($content_tags,$tag['tag']);
            }
        }
        $content_tags = array_values(array_unique($content_tags));
 
        $this->assign('processed_post_content', $this->replaceTagsWithLinks($article['post_content'],$content_tags));

  
        
        //文章标题提炼出来的标签，得分在50以上
        $tags = $portalTagModel->distinct(true)->field('tag')->where('id', $articleId)->where('type', 2)->where('score', 'gt', 50)->order('score DESC')->select();
        foreach ($tags as $tag){
             if (mb_strlen($tag['tag'], 'UTF-8') >= 2){
                array_push($title_tags,$tag);
            }
        }     
        
        
        
        
        
        

      $entitys = array();
        if(!empty($postEntity)){
            $postEntity = $postEntity->getData();
            $postEntity = json_decode($postEntity['entity']);
            
            if(!empty($postEntity)&&empty($postEntity->error_code))
            {//如果有errorcode,说明之前抓取百度实体的时候出错。则不没有百度实体
                $postEntity = json_decode(json_encode($postEntity->entity_annotation),true);  
                foreach ($postEntity as $entity){
                    $result = Db::table('cmf_portal_entity')->where('mention',$entity['mention'])->where('baikevisible',1)->find();
                    if($result != null){
                        array_push($entitys,$entity);
                    }
                }
            }
            $this->assign('entitys', $entitys);
        }

        $this->assign('title_tags', $title_tags);
        $this->assign('content_tags', $content_tags);
        

 

        
        
      
      	$userId = cmf_get_current_user_id();
        $this->assign('userId', $userId);

/*        $this->assign('style_tags', $style_tags);
        $this->assign('genre_tags', $genre_tags);
        $this->assign('media_tags', $media_tags);*/
      
		if(strpos($article['more']['thumbnail'],"/博物馆藏/")>0){
			$source="来源：zq";
		}else{
			$source="来源：tb";
		}
		$this->assign('source', $source);
		$qrcode="http://qr.hongmengyishu.com/".strval($articleId).".jpg";
		$this->assign('qrcode', $qrcode);
		$this->assign('isMobile',cmf_is_mobile());
        $this->assign('article', $article);
        $this->getRecommendArticles($articleId,$article['post_author'],$article['post_age'],$article['post_category_id']);
        $tplName = 'article';
        return $this->fetch("/$tplName");
    }
    #只取100条记录，否则会因为佚名太多，造成记录太多而资源耗尽
    public function getRecommendArticles($article_id,$author,$age,$category_id){
		$recommend_articles = array();
		$portalPostModel = new PortalPostModel();
		if ($author!='佚名' && $author!=null){
			//推一部分相同作者
			$where['post_author'] = $author;
			//$where['post_category_id'] = $category_id;
			$same_author_posts            = $portalPostModel->where($where)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(9)->select();
			foreach ($same_author_posts as $post){
				array_push($recommend_articles,$post);
			}

			//推一部分相同类别，相同年代的
			$where = array();
			$where['post_age'] = $age;
			$where['post_category_id'] = $category_id;
			$same_category_posts = $portalPostModel->where($where)->where('post_author', '<>', $author)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(100)->select();

		}else{

			//如果作者是佚名，则直接不管作者推相同类别和年代的
			$where = array();
			$where['post_age'] = $age;
			$where['post_category_id'] = $category_id;
			$same_category_posts            = $portalPostModel->where($where)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(100)->select();
		}

		
			//随机抽一部分
			$rand_num = count($same_category_posts)>18 ? 18-count($recommend_articles):count($same_category_posts);
			$rand_index_arr = unique_rand(0, count($same_category_posts)-1, $rand_num);
			foreach ($rand_index_arr as $index){
				array_push($recommend_articles,$same_category_posts[$index]);
			}

        $this->assign('recommend_articles', $recommend_articles);

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
