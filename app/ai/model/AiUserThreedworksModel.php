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
namespace app\ai\model;

use think\Model;
use api\user\model\UserModel;

class AiUserThreedworksModel extends Model
{


    public function addUser3dWork($userid,$work2dId,$task3dId,$imageUrl,$glb_url)
    {
        
          // 检查task3dId是否已存在
            $exists = $this->where('task_3d_id', $task3dId)->find();
            if ($exists) {
                // 已存在，不重复添加
                return true;
            }
            $update['uid'] = $userid;
            $update['2d_id'] = $work2dId;
            $update['task_3d_id'] = $task3dId;
            $update['3d_url'] = $glb_url;
            $update['image_url'] = $imageUrl;
            $update['create_time'] = time();
            $result = $this->save($update);
            return $result;

            
    }
    
    public function getUserWorks($userid)
    {
        
            $records = $this->where('uid', $userid)->where('picture_url', '<>', '')->order('create_timestamp', 'DESC')->limit(1000)->select()->toArray(); 
            return $records;            
  
    }
}