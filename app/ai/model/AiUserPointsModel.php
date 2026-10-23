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


class AiUserPointsModel extends Model
{



    public function getUserPoints($userid)
    {
         $currentPoints = $this->field('points')->where('userid', $userid)->find();
        if(!$currentPoints){
            //没有查询到
            return null;
        }else{
            return $currentPoints['points'];
        }  
            
    }
    public function updatePoints($userid,$points)
    {
            $update['points'] = $points;
            $result = $this->where('userid', $userid)->update($update);
            
    }
    public function addPoints($userid,$addPoints)
    {
            $currentPoints = $this->getUserPoints($userid);
            if($currentPoints!=null){
                $currentPoints += $addPoints;
                $update['points'] = $currentPoints;
                $result = $this->where('userid', $userid)->update($update);
                return $result;
            }
            return null;

            
    }
}