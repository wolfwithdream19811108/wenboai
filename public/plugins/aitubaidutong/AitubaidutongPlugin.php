<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\aitubaidutong;
use cmf\lib\Plugin;

class AitubaidutongPlugin extends Plugin
{

    public $info = [
        'name'        => 'Aitubaidutong',
        'title'       => '百度统计数据',
        'description' => '百度统计数据',
        'status'      => 1,
        'author'      => '爱途平台',
        'version'     => '1.0',
        'demo_url'    => 'http://www.aitu666.cn',
        'author_url'  => 'http://www.aitu666.cn'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $aitubaidutong = cmf_get_option('admin_dashboard_widgets');
        if (empty($aitubaidutong)) {
            $aitubaidutong = [];
        }

        $aitubaidutong[] = ['name' => 'Aitubaidutong','is_system' => 0];
        cmf_set_option('admin_dashboard_widgets', $aitubaidutong);
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        $aitubaidutong = cmf_get_option('admin_dashboard_widgets');
        if (empty($aitubaidutong)) {
            $aitubaidutong = [];
        }
        foreach ($aitubaidutong as &$v){
            if($v['name'] == 'Aitubaidutong'){
                unset($v);
            }
        }
        cmf_set_option('admin_dashboard_widgets', $aitubaidutong);
        return true;//卸载成功返回true，失败false
    }
    
        //admin_dashboard 钩子方法
    public function adminDashboard($param)
    {
        $config = $this->getConfig();
		$time = date("Y-m-d");
		   $parameterJSON = array(
            "header"=>array(
                "account_type"=> 1,
                "password"=>$config['password'],
                "token"=>$config['token'],
                "username"=>$config['username']
            ),
            "body"=>array(
                "siteId"=>$config['siteid'],
                "method"=>"visit/district/a",
                "start_date"=>$time,
                "end_date"=>$time,
                "metrics"=>"pv_count,visitor_count,ip_count,bounce_ratio,avg_visit_time",
            )
        );
		if(!$config||!$config['password']||!$config['token']||!$config['username']||!$config['siteid']){
			$dat = '请在插件设置中填写数据';
			$tongji = [0=>$dat,1=>$dat,2=>$dat,3=>$dat,4=>$dat];
			$this->assign('fangwen',$tongji);
        	return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'Aitubaidutong'
        	];
			
		} else{
			if($config['baiduuser']=1){
				$baiduurl = 'https://api.baidu.com/json/tongji/v1/ReportService/getData';
			} else{
				$baiduurl = 'https://openapi.baidu.com/rest/2.0/tongji/report/getData';
			}
			$curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $baiduurl);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,(String)json_encode($parameterJSON));
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示需要的数据
        $need=json_decode($data);
		$tongji = $need->body->data[0]->result->sum[0];
		$this->assign('fangwen',$tongji);
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'Aitubaidutong'
        ];
        
		}

    }

}