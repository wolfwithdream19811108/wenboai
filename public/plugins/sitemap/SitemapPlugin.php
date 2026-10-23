<?php
// +----------------------------------------------------------------------
// | Sitemap [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 HUANGCHENG All rights reserved.
// +----------------------------------------------------------------------
// | Author: HUANGCHENG <271301671@qq.com>
// +----------------------------------------------------------------------
namespace plugins\sitemap;
use cmf\lib\Plugin;

class SitemapPlugin extends Plugin
{

    public $info = [
        'name'        => 'Sitemap',
        'title'       => '生成XML地图推送百度',
        'description' => '生成XML地图推送百度',
        'status'      => 1,
        'author'      => 'HuangCheng',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => ''
    ];

    public $hasAdmin = 1;

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function portalAdminAfterSaveArticle($data)
    {
        $config = $this->getConfig();
        $articlestate = $data["article"]["post_status"];
        if ($config["ispush"] && $articlestate){
            $articleid = $data["article"]["id"];
            $categories = $data["categories"];
            $categories = explode(',',$categories);
            $filename = CMF_ROOT . 'data/pushbaidu/';
            !is_dir($filename) && mkdir($filename, 0755, true);
            $file_hwnd=fopen($filename.date('Y-m-d').".log","a+");

            if(empty($config["token"])){
                fwrite($file_hwnd,json_encode(["success"=>0,"msg"=>'请检查插件是否配置正确'])."\r\n");
                fclose($file_hwnd);
                return false;
            }
            $urls = [];
            foreach ($categories as $categoryid){
                $urls[] = cmf_url('portal/Article/index',array('id'=>$articleid,'cid'=>$categoryid),true,true);
            }
            $site = $config["site"]?$config["site"]:cmf_get_domain();
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
            if($result){
                $result = json_decode($result,true);
                if(isset($result["error"])){
                    fwrite($file_hwnd,json_encode(["success"=>0,"msg"=>isset($result["msg"])?$result["msg"]:$result["message"]])."\r\n");
                }
                fwrite($file_hwnd,json_encode(["success"=>1,"msg"=>"成功推送：".$result["success"]."条,剩余数量：".$result["remain"]])."\r\n");
            }else{
                fwrite($file_hwnd,json_encode(["success"=>0,"msg"=>'请检查插件是否配置正确'])."\r\n");
            }
            fclose($file_hwnd);

        }

    }

}