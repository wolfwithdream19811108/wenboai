<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.aitu666.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <673011635@qq.com>
// +----------------------------------------------------------------------

namespace plugins\aitubaidutong\controller; //Demo插件英文名，改成你的插件英文就行了

use think\Db;
use cmf\controller\PluginBaseController;

class AdminIndexController extends PluginBaseController
{

    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            //TODO no login
        }
    }

    function index()
    {
    	$config = $this->getPlugin()->getConfig();//获取插件配置
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
			return $this->fetch('/admin_index');
		} else{
			if($config['baiduuser']=1){
				$baiduurl = 'https://api.baidu.com/json/tongji/v1/ReportService/getData';
			} else{
				$baiduurl = 'https://openapi.baidu.com/rest/2.0/tongji/report/getData';
			}
			//获取全部来源信息
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
		$qushi = $this->qushi($baiduurl,$config);
		$qushit = json_decode($qushi);
		$this->assign('qushi',json_encode($qushit->qushi));
		$this->assign('qushitime',json_encode($qushit->qushitime));
		$laiyuan = $this->laiyuan($baiduurl,$config);
		$laiyuans = json_decode($laiyuan);
		$this->assign('landing',$laiyuans->landing);
		$this->assign('source',$laiyuans->source);
		$this->assign('visit',$laiyuans->visit);
		$this->assign('word',$laiyuans->word);
		
        return $this->fetch('/admin_index');
		}
        
    }
    
    //获取趋势分析信息
    public function qushi($baiduurl,$config){
		$time = date("Y-m-d");
		   $parameterJSON = array(
            "header"=>array(
                "account_type"=> 1,
                "password"=>$config['password'],
                "token"=>$config['token'],
                "username"=>$config['username']
            ),
            "body"=>array(
                "site_id"=>$config['siteid'],
                "method"=>"overview/getTimeTrendRpt",
                "start_date"=>$time,
                "end_date"=>$time,
                "metrics"=>"pv_count",
            )
        );    	
			//获取趋势分析信息
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
         $qushitime = array();
         $qushis = array();
		$qushi = $need->body->data[0]->result->items;    	
		foreach($qushi[0] as $k=>$val){ 
			// print_r($val);
			foreach($val as $k=>$val){ 
				// print_r($val);
				if($val=='--'){
					$val = 0;
				}
				$qushitime[] = $val;
		}
		}  
		foreach($qushi[1] as $k=>$val){ 
			// print_r($val);
			foreach($val as $k=>$val){ 
				// print_r($val);
				if($val=='--'){
					$val = 0;
				}
				$qushis[] = $val;
		}
		}
		$qushiup = ['qushitime'=>$qushitime,'qushi'=>$qushis];
    	return(json_encode($qushiup));
    }
    
    public function laiyuan($baiduurl,$config){
 		$time = date("Y-m-d");
		   $parameterJSON = array(
            "header"=>array(
                "account_type"=> 1,
                "password"=>$config['password'],
                "token"=>$config['token'],
                "username"=>$config['username']
            ),
            "body"=>array(
                "site_id"=>$config['siteid'],
                "method"=>"overview/getCommonTrackRpt",
                "start_date"=>$time,
                "end_date"=>$time,
            )
        );   	
	//获取来源信息
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
        $landing = array(); 
        $source = array(); 
        $visit = array(); 
        $word = array(); 
        $need=json_decode($data);
		$laiyuan = $need->body->data[0]->result;
		foreach($laiyuan->landingPage->items as $k=>$val){ 
			// print_r($val);
		// 	foreach($val as $k=>$val){ 
		// 		// print_r($val);
		// 		if($val=='--'){
		// 			$val = 0;
		// 		}
		// 		$landing[] = $val;
		// }
		$landing[] = $val;
		}
		foreach($laiyuan->sourceSite->items as $k=>$val){ 
			// print_r($val);
		// 	foreach($val as $k=>$val){ 
		// 		// print_r($val);
		// 		if($val=='--'){
		// 			$val = 0;
		// 		}
		// 		$source[] = $val;
		// }
		$source[] = $val;
		}
		foreach($laiyuan->visitPage->items as $k=>$val){ 
			// print_r($val);
		// 	foreach($val as $k=>$val){ 
		// 		// print_r($val);
		// 		if($val=='--'){
		// 			$val = 0;
		// 		}
		// 		$source[] = $val;
		// }
		$visit[] = $val;
		}
		foreach($laiyuan->word->items as $k=>$val){ 
			// print_r($val);
		// 	foreach($val as $k=>$val){ 
		// 		// print_r($val);
		// 		if($val=='--'){
		// 			$val = 0;
		// 		}
		// 		$source[] = $val;
		// }
		$word[] = $val;
		}
		$laiyuanup = ['landing'=>$landing,'source'=>$source,'visit'=>$visit,'word'=>$word];
		return(json_encode($laiyuanup));
    }
    
    
}
