<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\portal\service;

use app\portal\model\PortalArtistModel;
use think\db\Query;

class ArtistService
{


    /**
     * 艺术家查询
     * @param  int $ArtistId     艺术家id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Artist($ArtistId)
    {
        $portalArtistModel = new PortalArtistModel();
        $artist            = $portalArtistModel->where('id', $ArtistId)->find()->getData();
        return $artist;
    }



}