<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\message_board\controller;
use cmf\controller\PluginBaseController;
use plugins\message_board\model\PluginMessageBoardModel;
class IndexController extends PluginBaseController
{
    public function index()
    {
		$config = $this->getPlugin()->getConfig();//插件配置
		$this->assign("config", $config);
		return $this->fetch('/index');
    }
    public function free()
    {
		$config = $this->getPlugin()->getConfig();//插件配置
		$this->assign("config", $config);
		return $this->fetch('/free');
    }
    public function contact()
    {
		$DataModel = new PluginMessageBoardModel();
		$datalist = $DataModel->where(["cid" => "999"])->order("id ASC")->select();
		$this->assign("datalist", $datalist);
		return $this->fetch('/widget_contact');
    }
    /** 提交留言*/
    public function addPost()
    {
        $data = $this->request->param();
       // 数据验证
        $result = $this->validate($data, 'MessageBoard');
        if ($result !== true) {
            $this->error($result);
        }
		$config = $this->getPlugin()->getConfig();//插件配置
		// 更新
		$DataModel = new PluginMessageBoardModel();
		$DataModel->allowField(true)->data($data, true)->save();
		//返回联系方式
		$area = explode(" ",$data['area']);
		$finddate = $DataModel->where(["cid" => "999"])->where('area','like','%'.$area[1].'%')->find();
		if(!empty($finddate['name'])){
			$msgdate = $finddate['area']." - ".$finddate['name']." - ".$finddate['mobile'];
		}else{
			$finddate = $DataModel->where(["cid" => "999"])->where('area','like','%'.$area[0].'%')->find();
			if(!empty($finddate['name'])){
				$msgdate = $finddate['company']." - ".$finddate['name']." - ".$finddate['mobile'];
			}else{
				$msgdate = $config['config_TEL'];
			}
		}
        /*-----------------------机器人-----------------------*/
		if($data['cid']=='0'){
			$title ="【售后】";
		}else{
			$title="【留言】";
		}
		//留言机器人
		$webhook = $config['config_webrobot'];
        if (!empty($webhook)) {
		$message = [
			"title"=> $title.$data['area'],
			"text" => "#### ".$title.$data['area']."\n".
					   "##### 姓名：".$data['name']."\n".
					   "##### 电话：".$data['mobile']."\n".
					   "##### 单位：".$data['company']."\n".
					   "> ".$data['message']."\n",
		];
		$data = array ('msgtype' => 'markdown','markdown' => $message);
		$data_string = json_encode($data);
		$result = request_by_curl($webhook, $data_string); 
		}
        /*-----------------------机器人end-----------------------*/  
        $this->success($msgdate);
    }
}

function request_by_curl($remote_server, $post_string) {  
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    curl_close($ch);                
    return $data;  
}  