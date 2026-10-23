<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:kane < chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\portal\model;

use think\Model;

class PortalCurrentArtistsModel extends Model
{
/**
     * 后台管理编辑人才
     * @param array        $data       人才数据
     * @return $this
     * @throws \think\Exception
     */
    public function adminEditCurrentArtist($data)
    {

   

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;

    }
    
    /**
     * 后台管理添加文章
     * @param array        $data       文章数据
     * @param array|string $categories 文章分类 id
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function adminAddArtist($data)
    {


        $this->data($data, true)->isUpdate(false)->save();
        return $this;

    }
}