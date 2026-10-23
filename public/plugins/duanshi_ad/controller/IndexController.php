<?php
// +----------------------------------------------------------------------
// | DuanshiAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: duanshi <352534101@qq.com>
// +----------------------------------------------------------------------
namespace plugins\duanshi_ad\controller;

use think\Db;
use cmf\controller\PluginBaseController;

class IndexController extends PluginBaseController
{
    public function index()
    {
        $id = $this->request->param('id');
        $userQuery = Db::name("plugin_duanshi_ad");
        $user = $userQuery->where('id',$id)->find();
        $content="";
        if($user['status']==0) return $content;
        if($user['start_time']>time()) return $content;
        if($user['end_time']) if($user['end_time']<time()) return $content;
        $content=htmlspecialchars_decode($user['content']);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $img_url = $http_type . $_SERVER['HTTP_HOST'].'/upload/'.$user['img'];
        $img = @getimagesize($img_url);
        if ($img)
        {
            $content = '<a href="'.$user['url'].'" title="'.$user['name'].'" target="'.$user['target'].'"><img src="'.$img_url.'" '.$img[3].' /></a>';
            if ($user['display'] == 0)
            {
                return "document.writeln('".$content."');";
            }
            $offset = '';
            if ($user['offset'] != '0') 
            {
                $offset = "offset: '".$user['offset']."',";
            }
            $layer ="
function layOpen".$user['id']."()
{
    layer.open({
      type: 1,
      shade: false,
      title: false, //不显示标题
      time: ".(intval($user['duration_time'])*1000).", //多少秒后自动关闭
      area: ['".$img[0]."px', '".$img[1]."px'], //宽高
      ".$offset." //弹出位置
      anim: ".$user['anim'].", //0-6的动画形式，-1不开启
      content: '$content'
    }); 
}
setTimeout('layOpen".$user['id']."()','".(intval($user['show_time'])*1000)."');
document.writeln('<style>.layui-layer-setwin .layui-layer-close2{right: -15px;top: -15px;}</style>');";
            return $layer;
        }
        else
        {
            return '';
        }
    }
}