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
namespace app\portal\service;

use app\portal\model\PortalPostModel;
use app\portal\model\PortalAuctionPostModel;
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalMuseumModel;
use think\Db;
use think\db\Query;

class ApiService
{
    /**
     * 功能:查询文章列表,支持分页;<br>
     * 注:此方法查询时关联两个表portal_category_post(category_post),portal_post(post);在指定排序(order),指定查询条件(where)最好指定一下表别名
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'category_ids'=>'',
     *                     'where'=>'',
     *                     'limit'=>'',
     *                     'order'=>'',
     *                     'page'=>'',
     *                     'relation'=>''
     *                     )
     *                     字段说明:
     *                     category_ids:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     *                     field:调用指定的字段@todo
     *                     如只调用posts表里的id和post_title字段可以是post.id,post.post_title; 默认全部,
     *                     此方法查询时关联两个表portal_category_post(category_post),portal_post(post);
     *                     所以最好指定一下表名,以防字段冲突
     *                     limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     *                     order:排序方式,如按posts表里的published_time字段倒序排列：post.published_time desc
     *                     where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突,查询条件(只支持数组),格式和thinkPHP的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     *                     </pre>
     * @return array 包括分页的文章列表<pre>
     *                     格式:
     *                     array(
     *                     "articles"=>array(),//文章列表,array
     *                     "page"=>"",//生成的分页html,不分页则没有此项
     *                     "total"=>100, //符合条件的文章总数,不分页则没有此项
     *                     "total_pages"=>5 // 总页数,不分页则没有此项
     *                     )</pre>
     */
    public static function articles($param)
    {



        $portalPostModel = new PortalPostModel();

        $where = [];
   

        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $limit       = empty($param['limit']) ? 10 : $param['limit'];
        $order       = empty($param['order']) ? 'id asc' : $param['order'];
        $page        = isset($param['page']) ? $param['page'] : false;
        $relation    = empty($param['relation']) ? '' : $param['relation'];
        $categoryIds = empty($param['category_ids']) ? '' : $param['category_ids'];
		$post_age = empty($param['age']) ? '' : $param['age'];
		$post_museum_id = empty($param['museum_id']) ? '' : $param['museum_id'];
		$author    = empty($param['author']) ? '' : $param['author'];


        $whereCategoryId = null;
        $currentDomain = $_SERVER['HTTP_HOST'];

        //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        if(empty($_GET['page'])){
            $currentPage = 1;
        }else{
            $currentPage = $_GET['page'];
        }
        $cacheKey = md5($currentDomain . 'article_list:' . http_build_query($param).$currentPage);
        $return = cache($cacheKey);   
        if(!empty($return)){
        //	dump("get cache data");
        	return $return;
        }
        
    
        

        if (!empty($categoryIds)) {

            $field = !empty($param['field']) ? $param['field'] : '';

            if (!is_array($categoryIds)) {
                $categoryIds = explode(',', $categoryIds);
            }

            if (count($categoryIds) == 1) {
                $whereCategoryId = function (Query $query) use ($categoryIds) {
                    $query->where('post_category_id', $categoryIds[0]);
                };
            } else {
                $whereCategoryId = function (Query $query) use ($categoryIds) {
                    $query->where('post_category_id', 'in', $categoryIds);
                };
            }
        } else {

            $field = !empty($param['field']) ? $param['field'] : '';
        }

        if (!empty($post_age)) {
            $where['post.post_age'] = $post_age;

        } 
        if (!empty($post_museum_id)) {
            $where['post.post_museum_id'] = $post_museum_id;
        } 
		if (!empty($author)) {
            $where['post.post_author'] = $author;
        } 
		$where['post.delete_time'] = 0;
		
		
		
		



        $return = [];

        if (empty($page)) {
            
            $articles = $portalPostModel->alias('post')->field($field)
                ->where($where)
                ->where($paramWhere)
                ->where($whereCategoryId)
                ->order($order)
                ->limit($limit)->select();
    
            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }
                
                $articles = $portalPostModel->alias('post')->field($field)
                    ->where($where)
                    ->where($paramWhere)
                    ->where($whereCategoryId)
                    ->order($order)
                    >paginate($page);
            } else {
                $articles = $portalPostModel->alias('post')->field($field)
                    ->where($where)
                    ->where($paramWhere)
                    ->where($whereCategoryId)
                    ->order($order)
                    ->paginate(intval($page));

            }
         

            $articles->appends(request()->get());
            $articles->appends(request()->post());
			
            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }
       
	
		cache($cacheKey, $return);
        return $return;

    }
        /**
     * 功能:查询文章列表,支持分页;<br>
     * 注:此方法查询时关联两个表portal_category_post(category_post),portal_post(post);在指定排序(order),指定查询条件(where)最好指定一下表别名
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'category_ids'=>'',
     *                     'where'=>'',
     *                     'limit'=>'',
     *                     'order'=>'',
     *                     'page'=>'',
     *                     'relation'=>''
     *                     )
     *                     字段说明:
     *                     category_ids:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     *                     field:调用指定的字段@todo
     *                     如只调用posts表里的id和post_title字段可以是post.id,post.post_title; 默认全部,
     *                     此方法查询时关联两个表portal_category_post(category_post),portal_post(post);
     *                     所以最好指定一下表名,以防字段冲突
     *                     limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     *                     order:排序方式,如按posts表里的published_time字段倒序排列：post.published_time desc
     *                     where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突,查询条件(只支持数组),格式和thinkPHP的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     *                     </pre>
     * @return array 包括分页的文章列表<pre>
     *                     格式:
     *                     array(
     *                     "articles"=>array(),//文章列表,array
     *                     "page"=>"",//生成的分页html,不分页则没有此项
     *                     "total"=>100, //符合条件的文章总数,不分页则没有此项
     *                     "total_pages"=>5 // 总页数,不分页则没有此项
     *                     )</pre>
     */
    public static function auction_articles($param)
    {



        $portalAuctionPostModel = new PortalAuctionPostModel();

        $where = [];
 

        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $limit       = empty($param['limit']) ? 10 : $param['limit'];
        $order       = empty($param['order']) ? '' : $param['order'];
        $page        = isset($param['page']) ? $param['page'] : false;
        $relation    = empty($param['relation']) ? '' : $param['relation'];
		$auction_year = empty($param['auction_year']) ? '' : $param['auction_year'];
		$auction_company = empty($param['auction_company']) ? '' : $param['auction_company'];
		$author    = empty($param['author']) ? '' : $param['author'];



        //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $return = cache('auction_post_list_'.md5(json_encode($param)));
        if(!empty($return)){
        	return $return;
        }
        
       

        if (!empty($auction_year)) {
            $where['post.auction_year'] = $auction_year;

        } 
        if (!empty($auction_company)) {
            $where['post.auction_company'] = $auction_company;
        } 
		if (!empty($author)) {
            $where['post.author'] = $author;
        } 
		$where['post.delete_time'] = 0;
		
        $articles = $portalAuctionPostModel->alias('post')
            ->where($where)
           // ->where('post.published_time', ['> time', 0], ['<', time()], 'and')//加上这句降低查询速度
            ->order($order)
            ->group('post.id');

        $return = [];

        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }


            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());
			
            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }
       
		
		cache('articlelist_'.md5(json_encode($param)), $return);
        return $return;

    }
	   /**
     * 功能:查询用户推荐文章列表,支持分页;<br>
     * 注:此方法查询时关联两个表portal_category_post(category_post),portal_post(post);在指定排序(order),指定查询条件(where)最好指定一下表别名
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'category_ids'=>'',
     *                     'where'=>'',
     *                     'limit'=>'',
     *                     'order'=>'',
     *                     'page'=>'',
     *                     'relation'=>''
     *                     )
     *                     字段说明:
     *                     category_ids:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     *                     field:调用指定的字段@todo
     *                     如只调用posts表里的id和post_title字段可以是post.id,post.post_title; 默认全部,
     *                     此方法查询时关联两个表portal_category_post(category_post),portal_post(post);
     *                     所以最好指定一下表名,以防字段冲突
     *                     limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     *                     order:排序方式,如按posts表里的published_time字段倒序排列：post.published_time desc
     *                     where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突,查询条件(只支持数组),格式和thinkPHP的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     *                     </pre>
     * @return array 包括分页的文章列表<pre>
     *                     格式:
     *                     array(
     *                     "articles"=>array(),//文章列表,array
     *                     "page"=>"",//生成的分页html,不分页则没有此项
     *                     "total"=>100, //符合条件的文章总数,不分页则没有此项
     *                     "total_pages"=>5 // 总页数,不分页则没有此项
     *                     )</pre>
     */
    public static function recommendArticles($param)
    {


        $portalPostModel = new PortalPostModel();

        $where = [
           // 'post.post_status' => 1,
          //  'post.post_type'   => 1,
            'post.delete_time' => 0
        ];

        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $limit       = empty($param['limit']) ? 10 : $param['limit'];
        $order       = empty($param['order']) ? '' : $param['order'];
        $page        = isset($param['page']) ? $param['page'] : false;
        $relation    = empty($param['relation']) ? '' : $param['relation'];
        $categoryIds = empty($param['category_ids']) ? '' : $param['category_ids'];


        $whereCategoryId = null;

        if (!empty($categoryIds)) {

            $field = !empty($param['field']) ? $param['field'] : 'post.id, post.title, post.create_time';

            if (!is_array($categoryIds)) {
                $categoryIds = explode(',', $categoryIds);
            }
				
            if (count($categoryIds) == 1) {
                $whereCategoryId = function (Query $query) use ($categoryIds) {
                    $query->where('post_category_id', $categoryIds[0]);
                };
            } else {
                $whereCategoryId = function (Query $query) use ($categoryIds) {
                    $query->where('post_category_id', 'in', $categoryIds);
                };
            }
        } else {

            $field = !empty($param['field']) ? $param['field'] : 'post.id, post.title, post.create_time';
        }
		if(empty($param['rand']) || !$param['rand']){
	        $articles = $portalPostModel->alias('post')->field($field)
	            ->where($where)
	            ->where($paramWhere)
	            ->where($whereCategoryId);
	           // ->where('post.published_time', ['> time', 0], ['<', time()], 'and')//加上这句会降低查询速度
	          //  ->orderRaw('rand()');
		}else{
	        $articles = $portalPostModel->alias('post')->field($field)
	            ->where($where)
	            ->where($paramWhere)
	            ->where($whereCategoryId)
				->orderRaw('rand()');			
		}
		
		
        $return = [];

        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {
            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }
			

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());

            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }

        return $return;

    }
    /**
     * 功能:查询标签文章列表,支持分页;<br>
     * 注:此方法查询时关联两个表portal_tag_post(tag_post),portal_post(post);在指定排序(order),指定查询条件(where)最好指定一下表别名
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'tag_id'=>'',
     *                     'where'=>'',
     *                     'limit'=>'',
     *                     'order'=>'',
     *                     'page'=>'',
     *                     'relation'=>''
     *                     )
     *                     字段说明:
     *                     field:调用指定的字段@todo
     *                     如只调用posts表里的id和post_title字段可以是post.id,post.post_title; 默认全部,
     *                     此方法查询时关联两个表portal_tag_post(category_post),portal_post(post);
     *                     所以最好指定一下表名,以防字段冲突
     *                     limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     *                     order:排序方式,如按posts表里的published_time字段倒序排列：post.published_time desc
     *                     where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突,查询条件(只支持数组),格式和thinkPHP的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     *                     </pre>
     * @return array 包括分页的文章列表<pre>
     *                     格式:
     *                     array(
     *                     "articles"=>array(),//文章列表,array
     *                     "page"=>"",//生成的分页html,不分页则没有此项
     *                     "total"=>100, //符合条件的文章总数,不分页则没有此项
     *                     "total_pages"=>5 // 总页数,不分页则没有此项
     *                     )</pre>
     */
    public static function tagArticles($param)
    {
        $portalPostModel = new PortalPostModel();

        $where = [
            'post.post_status' => 1,
            'post.post_type'   => 1,
            'post.delete_time' => 0
        ];

        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $limit    = empty($param['limit']) ? 10 : $param['limit'];
        $order    = empty($param['order']) ? '' : $param['order'];
        $page     = isset($param['page']) ? $param['page'] : false;
        $relation = empty($param['relation']) ? '' : $param['relation'];
        $tagId    = empty($param['tag_id']) ? '' : $param['tag_id'];



        $join = [
            //['__USER__ user', 'post.user_id = user.id'],
        ];

        if (empty($tagId)) {
            return null;

        } else {
            $field = !empty($param['field']) ? $param['field'] : 'post.*';
            array_push($join, ['__PORTAL_TAG_POST__ tag_post', 'post.id = tag_post.post_id']);

            $where['tag_post.tag_id'] = $tagId;
        }

        $articles = $portalPostModel->alias('post')->field($field)
            ->join($join)
            ->where($where)
            ->where($paramWhere)
            ->where('post.published_time', ['> time', 0], ['<', time()], 'and')
            ->order($order);

        $return = [];

        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());

            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }

        return $return;
    }



	 public static function MuseumArticles($param)
    {
		
        $portalPostModel = new PortalPostModel();

        $where = [
            'cmf_portal_post.post_status' => 1,
            'cmf_portal_post.post_type'   => 1,
            'cmf_portal_post.delete_time' => 0
        ];
		$museum_id    = empty($param['museum_id']) ? '' : $param['museum_id'];

        $paramWhere = empty($param['where']) ? '' : $param['where'];
		
        $limit    = empty($param['limit']) ? 10 : $param['limit'];
        $order    = empty($param['order']) ? '' : $param['order'];
        $page     = isset($param['page']) ? $param['page'] : false;
        $relation = empty($param['relation']) ? '' : $param['relation'];


/*
        $join = [
            //['__USER__ user', 'post.user_id = user.id'],
        ];
*/
        if (empty($museum_id)) {
            return null;

        } else {
            //$field = !empty($param['field']) ? $param['field'] : 'post.*';
           // array_push($join, ['__PORTAL_TAG_POST__ tag_post', 'post.id = tag_post.post_id']);
			$where['cmf_portal_post.post_museum_id'] = $museum_id;
        }


        $articles = $portalPostModel->where($where)
            ->where($paramWhere)
            ->order($order);

        $return = [];

        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());

            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }

        return $return;
    }




	 public static function ArtistArticles($param)
    {
		
        $portalPostModel = new PortalPostModel();

        $where = [
            'cmf_portal_post.post_status' => 1,
            'cmf_portal_post.post_type'   => 1,
            'cmf_portal_post.delete_time' => 0
        ];
		$post_author    = empty($param['post_author']) ? '' : $param['post_author'];

        $paramWhere = empty($param['where']) ? '' : $param['where'];
		
        $limit    = empty($param['limit']) ? 10 : $param['limit'];
        $order    = empty($param['order']) ? '' : $param['order'];
        $page     = isset($param['page']) ? $param['page'] : false;
        $relation = empty($param['relation']) ? '' : $param['relation'];


/*
        $join = [
            //['__USER__ user', 'post.user_id = user.id'],
        ];
*/
        if (empty($post_author)) {
            return null;

        } else {
            //$field = !empty($param['field']) ? $param['field'] : 'post.*';
           // array_push($join, ['__PORTAL_TAG_POST__ tag_post', 'post.id = tag_post.post_id']);
			$where['cmf_portal_post.post_author'] = $post_author;
        }


        $articles = $portalPostModel->where($where)
            ->where($paramWhere)
            ->order($order);

        $return = [];

        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());

            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }

        return $return;
    }

	
	 public static function AgeArticles($param)
    {
		
        $portalPostModel = new PortalPostModel();

        $where = [
            'cmf_portal_post.post_status' => 1,
            'cmf_portal_post.post_type'   => 1,
            'cmf_portal_post.delete_time' => 0
        ];
		$post_age    = empty($param['post_age']) ? '' : $param['post_age'];

        $paramWhere = empty($param['where']) ? '' : $param['where'];
		
        $limit    = empty($param['limit']) ? 10 : $param['limit'];
        $order    = empty($param['order']) ? '' : $param['order'];
        $page     = isset($param['page']) ? $param['page'] : false;
        $relation = empty($param['relation']) ? '' : $param['relation'];


/*
        $join = [
            //['__USER__ user', 'post.user_id = user.id'],
        ];
*/
        if (empty($post_age)) {
            return null;

        } else {
            //$field = !empty($param['field']) ? $param['field'] : 'post.*';
           // array_push($join, ['__PORTAL_TAG_POST__ tag_post', 'post.id = tag_post.post_id']);
			$where['cmf_portal_post.post_age'] = $post_age;
        }


        $articles = $portalPostModel->where($where)
            ->where($paramWhere)
            ->order($order);

        $return = [];
		dump($page);
        if (empty($page)) {
            $articles = $articles->limit($limit)->select();

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $return['articles'] = $articles;
        } else {

            if (is_array($page)) {
                if (empty($page['list_rows'])) {
                    $page['list_rows'] = 10;
                }

                $articles = $articles->paginate($page);
            } else {
                $articles = $articles->paginate(intval($page));
            }

            if (!empty($relation) && !empty($articles['items'])) {
                $articles->load($relation);
            }

            $articles->appends(request()->get());
            $articles->appends(request()->post());

            $return['articles']    = $articles->items();
            $return['page']        = $articles->render();
            $return['total']       = $articles->total();
            $return['total_pages'] = $articles->lastPage();
        }

        return $return;
    }

    /**
     * 获取指定id的文章
     * @param int $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function article($id)
    {
        $portalPostModel = new PortalPostModel();

        $where = [
            'post_status' => 1,
            'post_type'   => 1,
            'id'          => $id,
            'delete_time' => 0
        ];

        return $portalPostModel->where($where)
            ->where('published_time', ['> time', 0], ['<', time()], 'and')
            ->find();
    }

    /**
     * 获取指定条件的页面列表
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'where'=>'',
     *                     'order'=>'',
     *                     )</pre>
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function pages($param)
    {
        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $order = empty($param['order']) ? '' : $param['order'];

        $portalPostModel = new PortalPostModel();

        $where = [
            'post_status' => 1,
            'post_type'   => 2, //页面
            'delete_time' => 0
        ];

        return $portalPostModel
            ->where($where)
            ->where($paramWhere)
            ->where('published_time', [['> time', 0], ['<', time()]], 'and')
            ->order($order)
            ->select();
    }

    /**
     * 获取指定id的页面
     * @param int $id 页面的id
     * @return array|false|\PDOStatement|string|\think\Model 返回符合条件的页面
     */
    public static function page($id)
    {
        $portalPostModel = new PortalPostModel();

        $where = [
            'post_status' => 1,
            'post_type'   => 2,
            'id'          => $id,
            'delete_time' => 0
        ];

        return $portalPostModel->where($where)
            ->where('published_time', ['> time', 0], ['<', time()], 'and')
            ->find();
    }

    /**
     * 返回指定分类
     * @param int $id 分类id
     * @return array 返回符合条件的分类
     */
    public static function category($id)
    {
        $portalCategoryModel = new PortalCategoryModel();

        $where = [
            'status'      => 1,
            'delete_time' => 0,
            'id'          => $id
        ];

        return $portalCategoryModel->where($where)->find();
    }

    /**
     * 返回指定分类下的子分类
     * @param int $categoryId 分类id
     * @param     $field      string  指定查询字段
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return false|\PDOStatement|string|\think\Collection 返回指定分类下的子分类
     */
    public static function subCategories($categoryId, $field = '*')
    {
        $portalCategoryModel = new PortalCategoryModel();

        $where = [
            'status'      => 1,
            'delete_time' => 0,
            'parent_id'   => $categoryId
        ];

		return $portalCategoryModel->field($field)->where($where)->order('list_order ASC')->select();
	}

    /**
     * 返回指定分类下的所有子分类
     * @param int $categoryId 分类id
     * @return array 返回指定分类下的所有子分类
     */
    public static function allSubCategories($categoryId)
    {
        $portalCategoryModel = new PortalCategoryModel();

        $categoryId = intval($categoryId);

        if ($categoryId !== 0) {
            $category = $portalCategoryModel->field('path')->where('id', $categoryId)->find();

            if (empty($category)) {
                return [];
            }

            $categoryPath = $category['path'];
        } else {
            $categoryPath = 0;
        }

        $where = [
            'status'      => 1,
            'delete_time' => 0,
            'path'        => ['like', "$categoryPath-%"]
        ];

        return $portalCategoryModel->where($where)->select();
    }

    /**
     * 返回符合条件的所有分类
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'where'=>'',
     *                     'order'=>'',
     *                     )</pre>
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function categories($param)
    {
        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $order = empty($param['order']) ? '' : $param['order'];

        $portalCategoryModel = new PortalCategoryModel();

        $where = [
            'status'      => 1,
            'delete_time' => 0,
        ];

        return $portalCategoryModel
            ->where($where)
            ->where($paramWhere)
            ->order($order)
            ->select();
    }

	/**
     * 返回符合条件的所有博物馆
     * @param array $param 查询参数<pre>
     *                     array(
     *                     'where'=>'',
     *                     'order'=>'',
     *                     )</pre>
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function museums($param)
    {
        $paramWhere = empty($param['where']) ? '' : $param['where'];

        $order = empty($param['order']) ? '' : $param['order'];

        $portalMuseumModel = new PortalMuseumModel();


        return $portalMuseumModel
            ->where($paramWhere)
            ->order($order)
            ->select();
    }

    /**
     * 获取面包屑数据
     * @param int     $categoryId  当前文章所在分类,或者当前分类的id
     * @param boolean $withCurrent 是否获取当前分类
     * @return array 面包屑数据
     */
    public static function breadcrumb($categoryId, $withCurrent = false)
    {
        $data                = [];
        $portalCategoryModel = new PortalCategoryModel();

        $path = $portalCategoryModel->where(['id' => $categoryId])->value('path');

        if (!empty($path)) {
            $parents = explode('-', $path);
            if (!$withCurrent) {
                array_pop($parents);
            }

            if (!empty($parents)) {
                $data = $portalCategoryModel->where('id', 'in', $parents)->order('path ASC')->select();
            }
        }

        return $data;
    }

}