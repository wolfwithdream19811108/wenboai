<?php
// +----------------------------------------------------------------------
// | Sitemap [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 HUANGCHENG All rights reserved.
// +----------------------------------------------------------------------
// | Author: HUANGCHENG <271301671@qq.com>
// +----------------------------------------------------------------------

namespace plugins\sitemap\controller;


use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalPostModel;
use cmf\controller\PluginBaseController;

class IndexController extends PluginBaseController
{
    public function index()
    {
        $urls = [];
        $site = cmf_get_domain();
        array_push($urls,$site);
        $portalCategoryModel = new PortalCategoryModel();
        $category = $portalCategoryModel->where('status', 1)->where('delete_time', 0)->select();
        foreach($category as $val)
        {
            $geturl= cmf_url('portal/List/index',["id"=>$val['id']],true,true);
            array_push($urls,$geturl);
        }
        $PortalPostModel = new PortalPostModel();
        $where['post_status'] = '1';
        $where['post_type'] = '2';
        $article = $PortalPostModel->where($where)->where('delete_time', 0)->select();
        foreach($article as $value)
        {
            $geturl=cmf_url('portal/Page/index','id='.$value['id'],'html',true);
            array_push($urls,$geturl);
        }
        $where['post_status'] = '1';
        $where['post_type'] = '1';
        $article = $PortalPostModel->where($where)->where('delete_time', 0)->select();
        foreach($article as $value)
        {
            $postCategories = $value->categories()->alias('portal_category')->column('portal_category.name', 'portal_category.id');
            $categories = key($postCategories);
            $geturl=cmf_url('portal/Article/index',["cid"=>$categories,"id"=>$value['id']],'html',true);
            array_push($urls,$geturl);
        }
        $config = $this->getPlugin()->getConfig();
        $site = cmf_get_domain();
        $token = $config["token"];
        $api = "http://data.zz.baidu.com/urls?site=$site&token=$token";
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $result = json_decode($result,true);
        if(isset($result["error"])){
            return json_encode($result);
        }
        return "成功推送：".$result["success"]."条,剩余数量：".$result["remain"];
    }
}
