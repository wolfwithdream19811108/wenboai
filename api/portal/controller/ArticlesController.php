<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\portal\controller;

use api\user\model\UserFavoriteModel;
use api\user\model\UserLikeModel;
use cmf\controller\RestBaseController;
use api\portal\model\PortalPostModel;
use api\portal\model\UserArticleLogModel;
use elasticHandler\ElasticHandler;
use think\Db;

class ArticlesController extends RestBaseController
{
    protected $postModel;

    public function __construct(PortalPostModel $postModel)
    {
        parent::__construct();
        $this->postModel = $postModel;
    }

    /**
     * 文章列表
     */
   public function index()
    {
        $params                       = $this->request->get();
        $params['where']['post_type'] = 1;
        $data                         = $this->postModel->getDatas($params);
        

        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            
            $response = $data;
        } else {
            $response = ['list' => $data,];
        }
        $this->success('请求成功!', $response);
    }

    /**
     * 获取指定的文章
     * @param int $id
     */
    public function read($id)
    {
        if (intval($id) === 0) {
			$array = debug_backtrace();
			
			foreach ($array as $row) {
				var_dump($row);
			  //  var_dump($row['file'] . ':' . $row['line'] . '行,调用方法:' . $row['function']);
			}
            $this->error('无效的文章id3！');
        } else {
        	
            $params                       = $this->request->get();
            $params['where']['post_type'] = 1;
            $params['id']                 = $id;
            
     
            $cacheflag = true;
	       // $data = cache('api_article_'.json_encode($params));
	        if(empty($data)){
				$data = $this->postModel->getDatas($params);
				cache('api_article_'.json_encode($params), $data);
				$cacheflag=false;
	        }        
	
            
            if (empty($data)) {
                $this->error('文章不存在！');
            } else {
                $this->postModel->where('id', $id)->setInc('post_hits');
                
               /* $url = cmf_url('portal/Article/index', ['id' => $id, 'cid' => $data['categories'][0]['id']], true, true);*/

                $data        = $data->toArray();
                $data['more']['thumbnail'] = cmf_get_image_url_with_token($data['more']['thumbnail']);
                foreach ($data['more']['photos'] as $key => $value){
                    $data['more']['photos'][$key]['w900url'] = cmf_get_image_url_with_token($value['url'],"!w900");
                    $data['more']['photos'][$key]['w300url'] = cmf_get_image_url_with_token($value['url'],"!w300");
                    $data['more']['photos'][$key]['longurl'] = cmf_get_image_url_with_token($value['url'],"!longimg");
                    $data['more']['photos'][$key]['tokenurl'] = cmf_get_image_url_with_token($value['url']);
                    $data['more']['photos'][$key]['originurl'] = cmf_get_image_url($value['url']);
                }
                $recommendArticle = $this->getRecommendArticles($id,$data['post_author'],$data['post_age'],$data['post_category_id']);
                $data['recommend'] = $recommendArticle;
                $data['cacheflag']=$cacheflag;
                $this->success('请求成功!', $data);
            }

        }
    }

    public function getRecommendArticles($article_id,$author,$age,$category_id)
    {
    	$recommend_articles = array();
		$portalPostModel = new PortalPostModel();
		if ($author!='佚名' && $author!=null){
			//推一部分相同作者
			$where['post_author'] = $author;

			//$where['post_category_id'] = $category_id;
			$same_author_posts            = $portalPostModel->where($where)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(9)->select()->toArray();
			foreach ($same_author_posts as $post){
				array_push($recommend_articles,$post);
			}

			//推一部分相同类别，相同年代的
			$where = array();
			$where['post_age'] = $age;
			$where['post_category_id'] = $category_id;
			$same_category_posts = $portalPostModel->where($where)->where('post_author', '<>', $author)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(100)->select()->toArray();

		}else{

			//如果作者是佚名，则直接不管作者推相同类别和年代的
			$where = array();
			$where['post_age'] = $age;
			$where['post_category_id'] = $category_id;
			$same_category_posts  = $portalPostModel->where($where)->where('id', '<>', $article_id)->where('delete_time', '=', '0')->limit(100)->select()->toArray();
		}

		
			//随机抽一部分
			$rand_num = count($same_category_posts)>18 ? 18-count($recommend_articles):count($same_category_posts);
			$rand_index_arr = unique_rand(0, count($same_category_posts)-1, $rand_num);
			foreach ($rand_index_arr as $index){
				array_push($recommend_articles,$same_category_posts[$index]);
			}
/*			for($i=0;$i<count($recommend_articles);$i++){
			    $recommend_articles[$i]['more']['thumbnail'] = "www.";
			}*/
			foreach ($recommend_articles as $key => $article){
			    $recommend_articles[$key]['more']['thumbnail'] = cmf_get_image_url_with_token($article['more']['thumbnail'],"!300X");
		
			}
			return  $recommend_articles;

	
    }

    /**
     * 我的文章列表
     */
    public function my()
    {
        $params = $this->request->get();
        $userId = $this->getUserId();
        $data   = $this->postModel->getUserArticles($userId, $params);

        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = [$data];
        } else {
            $response = ['list' => $data];
        }

        $this->success('请求成功!', $response);
    }
    
 

    /**
     * 添加文章
     */
    public function save()
    {
        $data            = $this->request->post();
        $data['user_id'] = $this->getUserId();
        $result          = $this->validate($data, 'Articles.article');
        if ($result !== true) {
            $this->error($result);
        }

        if (empty($data['published_time'])) {
            $data['published_time'] = time();
        }

        $this->postModel->addArticle($data);
        $this->success('添加成功！');
    }

    /**
     * 更新文章
     * @param  int $id
     */
    public function update($id)
    {
        $data   = $this->request->put();
        $result = $this->validate($data, 'Articles.article');
        if ($result !== true) {
            $this->error($result);
        }
        if (empty($id)) {
            $this->error('无效的文章id2');
        }
        $result = $this->postModel->editArticle($data, $id, $this->getUserId());
        if ($result === false) {
            $this->error('编辑失败！');
        } else {
            $this->success('编辑成功！');
        }
    }

    /**
     * 删除文章
     * @param  int $id
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error('无效的文章id1');
        }
        $result = $this->postModel->deleteArticle($id, $this->getUserId());
        if ($result == -1) {
            $this->error('文章已删除');
        }
        if ($result) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 批量删除文章
     */
    public function deletes()
    {
        $ids = $this->request->post('ids/a');
        if (empty($ids)) {
            $this->error('文章id不能为空');
        }
        $result = $this->postModel->deleteArticle($ids, $this->getUserId());
        if ($result == -1) {
            $this->error('文章已删除');
        }
        if ($result) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
    
    
    public function getFilter($where){
        return null;
        
    	$portalPostModel = new PortalPostModel();
        $museums = $portalPostModel->Distinct(true)->field('post_museum_id')->where($where)->select()->toArray();
        $ages = $portalPostModel->Distinct(true)->field('post_age')->where($where)->select()->toArray();
      
        $categories = $portalPostModel->Distinct(true)->field('post_category_id')->where($where)->select()->toArray();


		$museumArray=array();
		foreach($museums as $val){
			if(!empty($val) && $val['post_museum_id']!=0){
				$museum = Db::name("portal_museum")->where('id',$val['post_museum_id'])->find();
				array_push($museumArray,$museum);
			}

		}
	
		
		$categoryArray=array();
		foreach($categories as $val){
			if(!empty($val) && $val['post_category_id']!=0){
				$category = Db::name("portal_category")->where('id',$val['post_category_id'])->find();
				array_push($categoryArray,$category);
			}

		}		
		
		$ageArray=array();
		foreach($ages as $val){
			$age = Db::name("portal_age")->where('name',$val['post_age'])->find();
			$ageArray[$age['id']]=$age['name'];
			//array_push($ageArray,$age);

		}
		ksort($ageArray);
	
		$ages = array();
		$index = 0;
		foreach($ageArray as $val){
			$ages[$index] = $val;
			$index = $index + 1;
			//array_push($ageArray,$age);

		}	
	
		$filter['museums'] = $museumArray;
		$filter['ages'] = $ages;
		$filter['categories'] = $categoryArray;

		

    } 
    
/*
    public function search()
    {
  
        $categoryId = $this->request->param('category_id', 0, 'intval');
        $bigimg = $this->request->param('bigimg');
        $museum = $this->request->param('museum');
        $age = $this->request->param('age');	
    	
    	
    	
        $params = $this->request->get();
        if (!empty($params['keyword'])) {
            $params['where'] = [
                'post_title|post_author|post_museum' => ['like', '%' . $params['keyword'] . '%']
            ];
            
           

            
	   		if($museum>0){
				$params['where']['post_museum_id']=$museum;
			}
			if($bigimg==1){
				$params['where']['bigimg']=1;
			}
	
			if($age!=''){
				$params['where']['post_age']=$age;
			}
	
			if($categoryId!=''){
				$params['where']['post_category_id']=$categoryId;
			}        
		
            $params['order']='-quality';
            $params['where']['delete_time']=0;
    
          	$response = cache('api_article_search'.json_encode($params));
	        if(empty($response)){
	            $data            = $this->postModel->getDatas($params);

		      $filter =$this->getFilter($params['where']);  

	            $response['list'] = $data;
	            $response['filter'] = $filter;
	            cache('api_article_search'.json_encode($params), $response);
				$cacheflag=false;
	        }else{
	        	$cacheflag=true;
	        }
            $response['cacheflag']=$cacheflag; 
            

            $this->success('请求成功!', $response);
        } else {
            $this->error('搜索关键词不能为空！');
        }

    }*/
   
   /*
    public function search()
    {
  
        $portalPostModel = new PortalPostModel();
        $params = $this->request->get();
        $limit = 500;
    
        // 生成唯一缓存键（包含关键词和分页参数）
        $cacheKey = 'api_article_search'.json_encode($params).$limit;
        $keyword = $params['keyword'];
    
        if (empty($keyword)) {
            $this->error('搜索关键词不能为空！');
        }else{
        
            // 1. 优先从缓存获取
            $articles = cache($cacheKey);
            if (empty($articles)) {
                // 2. 缓存未命中，尝试 Elasticsearch
        
                $esHandler = new ElasticHandler('ind_ysyk_cmf_portal_post');
                $list = $esHandler->search_es($keyword, $limit);
        
                $idArray = [];
                if (!empty($list['hits']['hits'])) {
                    foreach ($list['hits']['hits'] as $val) {
                        $id = $val['_source']['id'] ?? 0;
                        if ($id) {
                            $idArray[] = $id;
                            // 可选：缓存单个文章详情
                            // cache('article_detail_' . $id, $val['_source'], 3600);
                        }
                    }
        
                    // 根据 ES 中的 ID 查询数据库
                    if (!empty($idArray)) {
                        $articles = $portalPostModel->whereIn('id', $idArray)
                                                    ->limit($limit)
                                                    ->select()
                                                    ->toArray();
                        // 缓存 ES 查询结果
                        $result = cache($cacheKey, $articles); 
                    }
                }
                
            
                // 3. ES 无结果或失败，查询数据库并缓存
        
          	    $where['post.post_title|post.post_author|post.post_age|post.post_museum']    = ['like', "%$keyword%"];
         	    $where['post.delete_time'] = 0;            
                $articles = $portalPostModel->alias('post')->where($where)->order('post.id ASC')->group('post.id');
                $articles = $articles->limit($limit)->select();        
                
                
                $articles = $portalPostModel->alias('post')
                                           ->where($where)
                                           ->group('post.id')
                                           ->order('post.id ASC')
                                           ->limit($limit)
                                           ->select()
                                           ->toArray();
            
                // 缓存数据库查询结果（较短时间，避免脏数据）
                cache($cacheKey, $articles); 
            }
            $response['list'] = $articles;
            $this->success('请求成功!', $response);
        }

    }*/
    public function search()
{
    $portalPostModel = new PortalPostModel();
    $params = $this->request->get();
    
    // 获取分页参数并验证
    $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
    $pageSize = isset($params['page_size']) ? max(1, min(100, intval($params['page_size']))) : 10;
    
    // 生成包含分页参数的缓存键
    $cacheParams = array_merge($params, ['page' => $page, 'page_size' => $pageSize]);
    $cacheKey = 'api_article_search_' . md5(json_encode($cacheParams));
    $keyword = $params['keyword'] ?? '';

    if (empty($keyword)) {
        $this->error('搜索关键词不能为空！');
    }

    // 尝试从缓存获取
    $cachedData = cache($cacheKey);
    if ($cachedData) {
        $response = $cachedData;
        $this->success('请求成功!', $response);
        return;
    }

    $esHandler = new ElasticHandler('ind_ysyk_cmf_portal_post');
    $from = ($page - 1) * $pageSize;
    $total = 0;
    $articles = [];

    // 尝试Elasticsearch查询
    try {
        $esResult = $esHandler->search_es($keyword, $from, $pageSize);
        
        if (!empty($esResult['hits']['hits'])) {
            // 处理ES结果
            $total = $esResult['hits']['total']['value'] ?? 0;
            $idArray = array_column(array_column($esResult['hits']['hits'], '_source'), 'id');
            
            if (!empty($idArray)) {
                // 保持ES结果顺序
                $orderIds = implode(',', $idArray);
                $articles = $portalPostModel
                    ->whereIn('id', $idArray)
                    ->orderRaw("FIELD(id, $orderIds)")
                    ->select()
                    ->toArray();
            }
        }
    } catch (Exception $e) {
        // ES查询失败，降级到数据库查询
    }

    // 如果ES无结果则查询数据库
    if (empty($articles)) {
        $where = [
            ['post.post_title|post.post_author|post.post_age|post.post_museum', 'like', "%$keyword%"],
            ['post.delete_time', '=', 0]
        ];

        // 查询总数
        $total = $portalPostModel
            ->alias('post')
            ->where($where)
            ->group('post.id')
            ->count();

        // 查询分页数据
        $articles = $portalPostModel
            ->alias('post')
            ->where($where)
            ->group('post.id')
            ->order('post.id', 'DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
    }

    // 构造分页响应
    $totalPage = ceil($total / $pageSize);
    $response = [
        'list' => $articles,
        'pagination' => [
            'current_page' => $page,
            'page_size'    => $pageSize,
            'total'        => $total,
            'total_page'   => $totalPage
        ]
    ];

    // 缓存结果（5分钟）
    cache($cacheKey, $response);
    $this->success('请求成功!', $response);
}

 
    public function gptsearch()
    {
  

    	
    	$keyword = $this->request->param('keyword', 0, '');
        if (!empty($keyword)) {

       	    $where['post_title|post_author|post_age|post_museum']    = ['like', "%$keyword%"];
     	    $where['delete_time'] = 0;            
          	$response = cache('api_gpt_search'.json_encode($where));
	        if(empty($response)){
	            
                $portalPostModel = new PortalPostModel();
                $limit       = 10;
                $articles = $portalPostModel->field('id, post_title,post_author,post_museum,post_age')->where($where)->order('quality desc')->limit($limit)->select()->toArray();
                $list = array();
                foreach ($articles as $article){
                    $article['url']="https://www.hongmengyishu.com/portal/article/index/id/".$article['id'].".html";
                    array_push($list,$article);
                }
	            $response['data'] = json_encode($list);
	            cache('api_gpt_search'.json_encode($where), $response);
				$cacheflag=false;
	        }else{
	        	$cacheflag=true;
	        }
            $response['cacheflag']=$cacheflag; 
            return $response;
        } else {
            $this->error('搜索关键词不能为空！');
        }

    }   

    public function doLike()
    {
        $userId = $this->getUserId();

        $articleId = $this->request->param('id', 0, 'intval');

        $userLikeModel = new UserLikeModel();

        $findLikeCount = $userLikeModel->where([
            'user_id'   => $userId,
            'object_id' => $articleId
        ])->where('table_name', 'portal_post')->count();

        if (empty($findLikeCount)) {
            $article = $this->postModel->where(['id' => $articleId])->field('id,post_title,post_excerpt,more')->find();
            if (empty($article)) {
                $this->error('文章不存在！');
            }

            Db::startTrans();
            try {
                $this->postModel->where(['id' => $articleId])->setInc('post_like');
                $thumbnail = empty($article['more']['thumbnail']) ? '' : $article['more']['thumbnail'];
                $userLikeModel->insert([
                    'user_id'     => $userId,
                    'object_id'   => $articleId,
                    'table_name'  => 'portal_post',
                    'title'       => $article['post_title'],
                    'thumbnail'   => $thumbnail,
                    'description' => $article['post_excerpt'],
                    'url'         => json_encode(['action' => 'portal/Article/index', 'param' => ['id' => $articleId, 'cid' => $article['categories'][0]['id']]]),
                    'create_time' => time()
                ]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();

                $this->error('点赞失败！');
            }

            $likeCount = $this->postModel->where('id', $articleId)->value('post_like');
            $this->success("赞好啦！", ['post_like' => $likeCount]);
        } else {
            $this->error("您已赞过啦！");
        }
    }

    /**
     * 取消文章点赞
     */
    public function cancelLike()
    {
        $userId = $this->getUserId();

        $articleId = $this->request->param('id', 0, 'intval');

        $userLikeModel = new UserLikeModel();

        $findLikeCount = $userLikeModel->where([
            'user_id'   => $userId,
            'object_id' => $articleId
        ])->where('table_name', 'portal_post')->count();

        if (!empty($findLikeCount)) {

            Db::startTrans();
            try {
                $this->postModel->where(['id' => $articleId])->setDec('post_like');
                $userLikeModel->where([
                    'user_id'   => $userId,
                    'object_id' => $articleId
                ])->where('table_name', 'portal_post')->delete();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('取消点赞失败！');
            }

            $likeCount = $this->postModel->where('id', $articleId)->value('post_like');
            $this->success("取消点赞成功！", ['post_like' => $likeCount]);
        } else {
            $this->error("您还没赞过！");
        }
    }

    /**
     * 文章收藏
     * @throws \think\Exception
     */
    public function doFavorite()
    {
    
        $userId = $this->getUserId();

        $articleId = $this->request->param('id', 0, 'intval');
        
       

        $userFavoriteModel = new UserFavoriteModel();

        $findFavoriteCount = $userFavoriteModel->where([
            'user_id'   => $userId,
            'object_id' => $articleId
        ])->where('table_name', 'portal_post')->count();

        if (empty($findFavoriteCount)) {
            $article = $this->postModel->where(['id' => $articleId])->field('id,post_title,post_excerpt,more')->find();
            if (empty($article)) {
                $this->error('文章不存在！');
            }

            Db::startTrans();
            try {
                $this->postModel->where(['id' => $articleId])->setInc('post_favorites');
                $thumbnail = empty($article['more']['thumbnail']) ? '' : $article['more']['thumbnail'];
                $userFavoriteModel->insert([
                    'user_id'     => $userId,
                    'object_id'   => $articleId,
                    'table_name'  => 'portal_post',
                    'thumbnail'   => $thumbnail,
                    'title'       => $article['post_title'],
                    'description' => $article['post_excerpt'],
                    'url'         => json_encode(['action' => 'portal/Article/index', 'param' => ['id' => $articleId, 'cid' => $article['categories'][0]['id']]]),
                    'create_time' => time()
                ]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();

                $this->error($userId);
            }

            $favoriteCount = $this->postModel->where('id', $articleId)->value('post_favorites');
            $this->success("收藏好啦！", ['post_favorites' => $favoriteCount]);
        } else {
            $this->error("您已收藏过啦！");
        }
    }

    /**
     * 取消文章收藏
     */
    public function cancelFavorite()
    {
        $userId = $this->getUserId();

        $articleId = $this->request->param('id', 0, 'intval');

        $userFavoriteModel = new UserFavoriteModel();

        $findFavoriteCount = $userFavoriteModel->where([
            'user_id'   => $userId,
            'object_id' => $articleId
        ])->where('table_name', 'portal_post')->count();

        if (!empty($findFavoriteCount)) {

            Db::startTrans();
            try {
                $this->postModel->where(['id' => $articleId])->setDec('post_favorites');
                $userFavoriteModel->where([
                    'user_id'   => $userId,
                    'object_id' => $articleId
                ])->where('table_name', 'portal_post')->delete();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('取消失败！');
            }

            $favoriteCount = $this->postModel->where('id', $articleId)->value('post_favorites');
            $this->success("取消成功！", ['post_favorites' => $favoriteCount]);
        } else {
            $this->error("您还没收藏过！");
        }
    }


    /**
     * 相关文章列表
     */
    public function relatedArticles()
    {
        $articleId  = $this->request->param('id', 0, 'intval');
        $categoryId = Db::name('portal_category_post')->where('post_id', $articleId)->value('category_id');


        $articles = $this->postModel->alias('post')->join('__PORTAL_CATEGORY_POST__ category_post', 'post.id=category_post.post_id')
            ->where(['post.delete_time' => 0, 'post.post_status' => 1, 'category_post.category_id' => $categoryId])
            ->order(Db::raw('rand()'))
            ->limit(5)
            ->select();

        $this->success('success', ['list' => $articles]);
    }
    
    
 
	
	

}