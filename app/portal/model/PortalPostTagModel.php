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
use app\portal\model\PortalPostModel;

class PortalPostTagModel extends Model
{


    /**
     * 关联 post表
     * @return \think\model\relation\BelongsTo
     */
    public function getTagPost($tag)
    {
        $postModel = new PortalPostModel();
  /*      return $postModel->alias('post')->join('__PORTAL_POST_TAG__ post_tag', 'post.id = post_tag.id')->where(['post_tag.tag' => $tag])->select()->toArray();*/
        

       // return $this->alias('post_tag')->join('PortalPost post', 'post_tag.id = post.id','left')->where(['post_tag.tag' => $tag])->select()->toArray();
   
       
  		if(empty($post_id_array)){
			$post_id_array = $this->field('id')->where(['tag' => $tag])->limit(1000)->select()->toArray();
			cache('getTagPost'.json_encode($tag), $post_id_array);
		}        
        
        
       
        $postIds = "";
        foreach ($post_id_array as $id){
            $postIds = $postIds.$id ['id'].',';
        }
        $where['id'] = array('in',$postIds);
        $where['delete_time'] = 0;
        $postModel = new PortalPostModel();
        return $postModel->where($where)->select()->toArray();
        
        //return $this->hasOne('Portal_Post_Model', 'id', 'id')->where(['tag' => $tag])->select();
            
    }
        public function getTagPostLiteForApi($tag,$page)
    {
        $postModel = new PortalPostModel();

        $post_id_array = $this->field('id')->where(['tag' => $tag])->paginate(100,false,['page' => $page])->toArray();
        
        $postIds = "";
        foreach ($post_id_array["data"] as $id){
            $postIds = $postIds.$id ['id'].',';
        }
        $where['id'] = array('in',$postIds);
        $where['delete_time'] = 0;
        $postModel = new PortalPostModel();
        return $postModel->where($where)->field('id,post_title,more')->select()->toArray();
        
        //return $this->hasOne('Portal_Post_Model', 'id', 'id')->where(['tag' => $tag])->select();
            
    }
}