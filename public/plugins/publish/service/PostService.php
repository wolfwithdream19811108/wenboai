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
namespace plugins\publish\service;

use plugins\publish\model\PortalPostModel;
use think\db\Query;

class PostService
{

    public function adminArticleList($filter)
    {
        return $this->adminPostList($filter);
    }


    public function adminPostList($filter, $isPage = false)
    {

        $where = [
            'a.user_id' => cmf_get_current_user_id()
        ];

        $join = [
            ['__USER__ u', 'a.user_id = u.id']
        ];

        $field = 'a.*,u.user_login,u.user_nickname,u.user_email';

        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id,u.user_login,u.user_nickname,u.user_email';
        }

        $portalPostModel = new PortalPostModel();
        $articles        = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.delete_time', 0)
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : intval($filter['category']);
                if (!empty($category)) {
                    $query->where('b.category_id', $category);
                }

                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime)) {
                    $query->where('a.published_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.published_time', '<=', $endTime);
                }

                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }

                if ($isPage) {
                    $query->where('a.post_type', 2);
                } else {
                    $query->where('a.post_type', 1);
                }
            })
            ->order('update_time', 'DESC')
            ->paginate(10);

        return $articles;

    }

}