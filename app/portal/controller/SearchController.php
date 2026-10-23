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
use app\portal\model\PortalPostModel;
use elasticHandler\ElasticHandler;
use think\Db;
use think\paginator\driver\Bootstrap;
class SearchController extends HomeBaseController
{
    /**
     * 搜索
     * @return mixed
     */
     

   public function index()
    {
        
        $keyword = $this->request->param('keyword');
        $page = $this->request->param('page', 1, 'intval');    // 获取页码
        $limit = $this->request->param('limit', 60, 'intval'); // 每页数量
    
        if (empty($keyword)) {
            $this->error("关键词不能为空！请重新输入！");
        }
    
        $portalPostModel = new PortalPostModel();
        $cacheKey = 'search_articles:' . md5($keyword) . '_' . $page . '_' . $limit; // 缓存键包含分页参数
    
        // 1. 优先从缓存获取，es足够快，不从缓存取了
/*        if ($articles = cache($cacheKey)) {
            $this->assignResults($articles, $keyword);
            return $this->fetch('/search');
        }
    */
        // 2. 尝试Elasticsearch查询
        $esHandler = new ElasticHandler('ind_ysyk_cmf_portal_post');
        $from = ($page - 1) * $limit; // ES分页起始位置
        $list = $esHandler->search_es($keyword, $from, $limit);
    
        // 处理ES结果
        if (!empty($list['hits']['hits'])) {
            $total = $list['hits']['total']['value'] ?? 0; // 获取总条数
            $total = ($total > 3000) ? 3000 : $total; // 如果大于 3000，则设为 3000；否则保持原值
            $idArray = array_column(array_column($list['hits']['hits'], '_source'), 'id');
            
            $articles = [];
            // 按ES的ID顺序查询数据库（关键！）
            if (!empty($idArray)) {
                $articles = $portalPostModel->whereIn('id', $idArray)
                    ->orderRaw("FIELD(id, " . implode(',', $idArray) . ")") // 按ID顺序排序
                    ->select()
                    ->toArray();
            }

    
            // 生成分页HTML
            // 初始化分页器（即使无数据）
            $paginator = Bootstrap::make(
                $articles, // 必须传递实际数据集合
                $limit,
                $page,
                $total, 
                false,
                [
                    'path' => url('/portal/Search/index'), 
                    'query' => ['keyword' => $keyword]
                ]
            );
            $pageHtml = $paginator->render();
    
            //cache($cacheKey, $articles);
            $this->assignResults($articles, $keyword, $pageHtml);
            return $this->fetch('/search');
        }
    
    
    
        // 3. 数据库查询回退

   	    $where['post.post_title|post.post_author|post.post_age|post.post_museum']    = ['like', "%$keyword%"];
 	    $where['post.delete_time'] = 0;            

    
        // 使用分页查询
        $paginate = $portalPostModel->alias('post')
            ->where($where)
            ->group('post.id')
            ->order('post.id', 'ASC')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page,
                'path' => url('/portal/Search/index'),
                'query' => ['keyword' => $keyword]
            ]);
    
        $articles = $paginate->items();
        $pageHtml = $paginate->render();
    
       // cache($cacheKey, $articles);
        $this->assignResults($articles, $keyword, $pageHtml);

        return $this->fetch('/search');
     
    
              
    
    }   
     /**
     * 统一分配模板变量
     */
/*    private function assignResults($articles, $keyword)
    {
        $this->assign([
            'articles' => $articles,
            'keyword'  => htmlspecialchars($keyword) // 防止 XSS
        ]);
    }*/
    private function assignResults($articles, $keyword, $pageHtml = '') {
        $this->assign([
            'articles' => $articles,
            'keyword'  => $keyword,
            'page'     => $pageHtml // 传递分页HTML到模板
        ]);
    }
     
}
