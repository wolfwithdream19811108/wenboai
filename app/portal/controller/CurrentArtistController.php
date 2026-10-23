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
use app\portal\model\PortalCurrentArtistsModel;
use app\portal\model\PortalCurrentWorksModel;
use think\Db;

class CurrentArtistController extends HomeBaseController
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
        $artistId  = $this->request->param('artistid', 0, 'intval');
         if(empty($artistId)){
             $this->error("参数错误!");
         }
               
        
        $portalCurrentArtistsModel = new PortalCurrentArtistsModel();
        $artist = $portalCurrentArtistsModel->where('artistid', $artistId)->find();
        if(empty($artist)){
            $this->error("艺术家id不存在!");
        }else{
            $artist['avatar'] = json_decode($artist['avatar'],true);
            $this->assign('artist', $artist->getData());
            
         $portalCurrentWorktsModel = new PortalCurrentWorksModel();
         $artworks = $portalCurrentWorktsModel->where('artistid', $artistId)->select(); 
         foreach ($artworks as $artwork){
             $artwork['picture'] = json_decode($artwork['picture'],true);
         }
         
          $this->assign('artworks', $artworks);

            
            $tplName = 'current_artist'; 
            
            
            
        }
        
        return $this->fetch('/' . $tplName);        
    
   


    }



}
