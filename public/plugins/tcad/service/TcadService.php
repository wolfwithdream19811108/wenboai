<?php
// +----------------------------------------------------------------------
// | TcAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\tcad\service;

use think\Db;

class TcadService
{
    public static function article($id)
    {
        $userQuery = Db::name("PluginTcad");
        $user = $userQuery->where('id',$id)->find();
        $content="";
        if($user['status']==0) return $content;
        if($user['start_time']>time()) return $content;
        if($user['end_time']) if($user['end_time']<time()) return $content;
        $content=htmlspecialchars_decode($user['content']);
        return $content;
    }
}