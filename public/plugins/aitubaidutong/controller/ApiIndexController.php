<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.aitu666.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <673011635@qq.com>
// +----------------------------------------------------------------------
namespace plugins\aitubaidutong\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginRestBaseController;
use think\Db;

class ApiIndexController extends PluginRestBaseController
{

    public function index()
    {
        $this->success('success', ['hello' => 'hello ThinkCMF!']);
    }
    
 	//当pv达到100以后获取token，使用百度统计获取信息
	public function baidutong(){
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
		$baiduurl = '';
			if($config['baiduuser']=1){
				$baiduurl = 'https://api.baidu.com/json/tongji/v1/ReportService/getData';
			} else{
				$baiduurl = 'https://openapi.baidu.com/rest/2.0/tongji/report/getData';
			}		
		 $this->curlPost($baiduurl,$parameterJSON);
		
	}
	
	 protected function curlPost($parameterJSON)
    {
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
        //解析要获得的数据
        // print_r($need->body->data[0]->result->sum[0][0]);
        print_r($need->body->data[0]->result->sum[0]);

    }
    
    public function qushi(){
		$config = $this->getPlugin()->getConfig();//获取插件配置
		$time = date("Y-m-d");
		$baiduurl = '';
			if($config['baiduuser']=1){
				$baiduurl = 'https://api.baidu.com/json/tongji/v1/ReportService/getData';
			} else{
				$baiduurl = 'https://openapi.baidu.com/rest/2.0/tongji/report/getData';
			}		
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
        $qushitime = array(); 
        $qushis = array(); 
        $need=json_decode($data);
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
		$dat = json_encode($qushiup);
		$datt = json_decode($dat);
		print_r($datt->qushitime);
		// print_r($qushi[0]);
    }
    
    
        public function laiyuan(){
		$config = $this->getPlugin()->getConfig();//获取插件配置
		$time = date("Y-m-d");
		$baiduurl = '';
			if($config['baiduuser']=1){
				$baiduurl = 'https://api.baidu.com/json/tongji/v1/ReportService/getData';
			} else{
				$baiduurl = 'https://openapi.baidu.com/rest/2.0/tongji/report/getData';
			}		
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
		$dat = json_encode($laiyuanup);
		$datt = json_decode($dat);
		print_r($datt);
		// print_r($qushi[0]);
    }
    

}
