<?php
// +----------------------------------------------------------------------
// | Sitemap [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 HUANGCHENG All rights reserved.
// +----------------------------------------------------------------------
// | Author: HUANGCHENG <271301671@qq.com>
// +----------------------------------------------------------------------
namespace plugins\sitemap\controller;

use cmf\controller\PluginAdminBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalPostModel;

/**
 * Class AdminIndexController.
 *
 * @adminMenuRoot(
 *     'name'   =>'sitemap',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'centos-line',
 *     'remark' =>'Sitemap'
 * )
 */
class AdminIndexController extends PluginAdminBaseController
{

    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }

    /**
     * 生成sitemap
     * @adminMenu(
     *     'name'   => '生成sitemap',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '生成sitemap',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $request = $this->request;
        $host=$request->domain();
        $this->assign("host", $host);
        return $this->fetch('/admin_index');
    }

    /**
     * 推送日志
     * @adminMenu(
     *     'name'   => '推送日志',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '推送日志',
     *     'param'  => ''
     * )
     */
    public function pushlog(){
        $data      = $this->request->param();
        $date=isset($data['time']) ? $data['time'] : date('Y-m-d');
        $filename = CMF_ROOT . 'data/pushbaidu/'.$date.'.log';
        $logs = [];
        if(file_exists_case($filename)){
            fopen($filename,"r");
            $num = count(file($filename));
            $file_hwnd=fopen($filename,"r");
            $content = explode("\r\n",fread($file_hwnd, filesize($filename)));  // 读去文件全部内容
            fclose($file_hwnd);
            foreach ($content as $k=>$v){
                if($v){
                    $logs[$k] =json_decode( $v,true);
                }
            }
        }else{
            $num = 0;
        }

        $this->assign("content",array_reverse($logs,true));
        $this->assign('time', $date);
        $this->assign("num",$num);
        return $this->fetch('/admin_pushlog');
    }

    public function addxml()
    {
        $site = $this->siteinfo();
        $html = '<a href="'.$site.'/">首页</a>';
        $lastmod=date('Y-m-d H:i:s',time());
        $txt = $site.'/'."\r\n";
        $xml = '<?xml version="1.0" encoding="utf-8"?><?xml-stylesheet type="text/xsl" href="'.$site.'/plugins/sitemap/sitemap.xsl"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"><url><loc>'.$site.'/</loc><changefreq>daily</changefreq><priority>1</priority><lastmod>'.$lastmod.'</lastmod></url>';
        $portalCategoryModel = new PortalCategoryModel();
        $category = $portalCategoryModel->where('status', 1)->where('delete_time', 0)->select();
        foreach($category as $val)
        {
            $lastmod=date('Y-m-d H:i:s',time());
            $geturl= cmf_url('portal/List/index',["id"=>$val['id']],true,true);
            $txt .= $geturl."\r\n";
            $html .= '<a href="'.$geturl.'">'.$val['name'].'</a>'."<br>";
            $xml .= '<url><loc>'.$geturl.'</loc><changefreq>daily</changefreq><priority>0.8</priority><lastmod>'.$lastmod.'</lastmod></url>';
        }
        $PortalPostModel = new PortalPostModel();
        $where['post_status'] = '1';
        $where['post_type'] = '2';
        $article = $PortalPostModel->where($where)->where('delete_time', 0)->select();
        foreach($article as $value)
        {
            $lastmod=date('Y-m-d H:i:s',time());
            $geturl=cmf_url('portal/Page/index','id='.$value['id'],'html',true);
            $txt .= $geturl."\r\n";
            $html .=  '<a href="'.$geturl.'">'.$value['post_title'].'</a>'."<br>";
            $xml .= '<url><loc>'.$geturl.'</loc><changefreq>monthly</changefreq><priority>0.5</priority><lastmod>'.$lastmod.'</lastmod></url>';
        }
        $where['post_status'] = '1';
        $where['post_type'] = '1';
        $article = $PortalPostModel->where($where)->where('delete_time', 0)->select();
        foreach($article as $value)
        {
            $lastmod=date('Y-m-d H:i:s',$value['create_time']);
            $postCategories = $value->categories()->alias('portal_category')->column('portal_category.name', 'portal_category.id');
            $categories = key($postCategories);
            $geturl=cmf_url('portal/Article/index',["cid"=>$categories,"id"=>$value['id']],'html',true);
            $txt .= $geturl."\r\n";
            $html .=  '<a href="'.$geturl.'">'.$value['post_title'].'</a>'."<br>";
            $xml .= '<url><loc>'.$geturl.'</loc><changefreq>monthly</changefreq><priority>0.5</priority><lastmod>'.$lastmod.'</lastmod></url>';
        }
        $xml .= '</urlset>';
        $this->updatexml($xml);
        $this->updatetxt($txt);

        $this->success("生成成功！！");
    }


    private function siteinfo()
    {
        $request = $this->request;
        return $request->domain();
    }
    private function updatetxt($data)
    {
        $this->writefile('txt',$data);
    }
    private function updatexml($data)
    {
        $this->writefile('xml',$data);
    }

    public function pushBaidu(){
        $urls = [];
        $site = $this->siteinfo();
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
        if(empty($token)){
           $this->error("请检查配置");
        }
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
        if(empty($result)){
            $this->error("请检查配置");
        }
        $filename = CMF_ROOT . 'data/pushbaidu/';
        !is_dir($filename) && mkdir($filename, 0755, true);
        $file_hwnd=fopen($filename.date('Y-m-d').".log","a+");
        if(isset($result["error"])){
            fwrite($file_hwnd,json_encode(["success"=>0,"msg"=>isset($result["msg"])?$result["msg"]:$result["message"]])."\r\n");
            fclose($file_hwnd);
            $this->error(isset($result["msg"])?$result["msg"]:$result["message"]);
        }
        fwrite($file_hwnd,json_encode(["success"=>1,"msg"=>"成功推送：".$result["success"]."条,剩余数量：".$result["remain"]])."\r\n");
        fclose($file_hwnd);
        $this->success("成功推送：".$result["success"]."条,剩余数量：".$result["remain"]);
    }

    private function writefile($type,$data)
    {
        $myfile = fopen($_SERVER['DOCUMENT_ROOT']."/sitemap.".$type, "w") or die("Unable to open file!");
        fwrite($myfile, $data);
        fclose($myfile);
    }

}
