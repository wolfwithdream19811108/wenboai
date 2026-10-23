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
use app\portal\model\PortalPostTagModel;
use app\portal\service\ApiService;


class TagpostlistController extends HomeBaseController
{
    /***
     * 文章列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
/*    public function index()
    {
        $tag                  = $this->request->param('tag');
		$posts = cache('portal_getTagPostLists_'.json_encode($tag));
		if(empty($posts)){
    		$portalTagModel = new PortalPostTagModel();
    		$posts = $portalTagModel->getTagPost($tag);
    		$result = cache('portal_getTagPostLists_'.json_encode($tag), $posts);
		}   	
	
        $this->assign('tag', $tag);
		$this->assign('articles', $posts);


        return $this->fetch('/' . 'tag_article_list');
    }*/
   public function index()
    {
        // 1. 获取参数
        $tag = $this->request->param('tag', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = 60; // 每页显示数量
    
        // 2. 获取文章总数（带缓存）
        $totalCacheKey = 'tag_posts_total:' . md5($tag);
        $total = cache($totalCacheKey);
        
        if (empty($total)) {
            $total = PortalPostTagModel::where('tag', $tag)->count();
            cache($totalCacheKey, $total, 3600); // 缓存1小时
        }
    
    
        // 3. 获取当前页数据（带缓存）
        $pageCacheKey = 'tag_posts:' . md5($tag) . ':page:' . $page;
        $articles = cache($pageCacheKey);
        
        if (empty($articles)) {
            // 直接获取标签表中的id（即文章ID）
            $postIds = PortalPostTagModel::where('tag', $tag)
                ->page($page, $pageSize)
                ->column('id'); // 这里使用id列
            
            // 获取文章详情
            $articles = PortalPostModel::where('id', 'in', $postIds)
                ->where('delete_time', 0)
                ->order('id', 'desc')
                ->select();
                
            cache($pageCacheKey, $articles, 300); // 缓存5分钟
        }

    
        // 4. 创建分页器
        $paginator = new \think\paginator\driver\Bootstrap(
            $articles,     // 当前页数据
            $pageSize,     // 每页数量
            $page,         // 当前页码
            $total,        // 总记录数
            false,         // 是否简洁模式
            [
                'path' => '/portal/tagpostlist/index', // 固定基础路径
                'query' => [
                    'tag' => $tag,
                    'page' => $page
                ],
                'var_page' => 'page' // 明确指定分页参数名
            ]
        );
    
        // 5. 模板赋值
        $this->assign([
            'tag' => $tag,
            'articles' => $articles,
            'page' => $paginator->render(),
            'total_pages' => $paginator->lastPage(),
            'paginator' => $paginator->render()
        ]);
    
        return $this->fetch('/tag_article_list');
    }
        
       

}
