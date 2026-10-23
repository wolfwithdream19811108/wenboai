<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\qrlogin\controller;
use cmf\controller\PluginBaseController;
use think\Db;
use think\Cache;
use app\user\model\UserModel;

class IndexController extends PluginBaseController
{
	function index()
	{
		$login_referer = $this->request->server('HTTP_REFERER');
		session('login_http_referer', $login_referer);
		if(cmf_is_wechat()){
			$this->UserOauthLogin('0');
		}else{
			return $this->fetch("/index");
		}
	}
	//微信用户登录
    function UserOauthLogin($userinfo)
    {
		if(empty($userinfo['openid'])){
			$this->WxBack();
		}
		$guid 					= $this->GetUuid();
		$userinfo['login']		= "wx_".$guid;
		$userinfo['user_pass'] 	= $guid;
		$qruuid 				= cookie("qruuid");
		$log 					= $this->ThirdUserLogin($userinfo);
		switch ($log) {
		    case 0:
				if(!empty($qruuid)){
					$token = cmf_generate_user_token(cmf_get_current_user_id(),'web');
					cache($qruuid,$token,30);
					//扫码登录成功！;
					$success_data= [
						'url' 	=> '/',
						'time'	=> '6',
					];
				}else{
					// 'url' 	=> 'javascript:history.back(-1);',
					$success_data= [
						'url' 	=> session('login_http_referer'),
						'time'	=> '3',
					];
				}
				$this->assign('success_data', $success_data);
				return $this->fetch("/success_login");
				break;
		    case 1:
		        $this->error('密码错误！');
				break;
		    case 3:
				if(!empty($qruuid)){
					cache($qruuid,NULL);
				}
		        $this->error('账号被禁止访问系统');
				break;
            default :
                $this->error('未受理的请求');
				break;
		}
    }
	//微信登录后台验证
	function AdminOauthLogin($userinfo){
		if(empty($userinfo['openid'])){
			$this->WxBack();
		}
		$userResult = Db::name("third_party_user")->where(['openid'=>$userinfo['openid'],'union_id'=>'WXadmin'])->find();
		if (empty($userResult)) {
			$this->error('未绑定账户');
		}else{
			$result   = Db::name("user")->where('id', $userResult['user_id'])->find();
			if (!empty($result) && $result['user_type'] == 1) {

					$groups = Db::name('RoleUser')
						->alias("a")
						->join('__ROLE__ b', 'a.role_id =b.id')
						->where(["user_id" => $result["id"], "status" => 1])
						->value("role_id");
					if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
						$this->error('该账户已被锁定！');
					}
					//登入成功页面跳转
					$userdata   = [
						'last_login_time'  	=> time(),
						'login_times'      	=> $userResult['login_times'] + 1,
						'last_login_ip'    	=> get_client_ip(0, true),
						'nickname'   		=> $userinfo['nickname'],
						'more'          	=> $userinfo['headimgurl']
					];
					Db::name("third_party_user")->where(['openid'=>$userinfo['openid'],'union_id'=>'WXadmin'])->update($userdata);
					//设置扫码登录成功状态token
					$token    		= cmf_generate_user_token($result["id"], 'web');
					$qruuid 		= cookie("qruuid");
					cache($qruuid,$token,30);
					$this->success('登录成功！');

			} else {
				$this->error('用户名不存在');
			}
		}
		
	}
	//管理员微信绑定
	function BindOauthLogin($userinfo){
		if(empty($userinfo['openid'])){
			$this->WxBack();
		}
		$Result = Db::name("third_party_user")->where(['openid'=>$userinfo['openid'],'union_id'=>'WXadmin'])->find();
		if (empty($Result)) {
				$userdata   = [
					'openid'      		=> $userinfo['openid'],
					'third_party'     	=> "管理员微信",
					'union_id'      	=> "WXadmin",
					'create_time'      	=> time(),
					'last_login_time'   => time(),
					'login_times'      	=> 1,
					'last_login_ip'    	=> get_client_ip(0, true),
					'nickname'   		=> $userinfo['nickname'],
					'more'          	=> $userinfo['headimgurl']
				];
				Db::name("third_party_user")->insertGetId($userdata);
				$qruuid 		= cookie("qruuid");
				cache($qruuid,$userinfo['openid'],30);
				$this->success('绑定成功');
		}else{
			if(empty($Result['user_id'])){
				$qruuid 		= cookie("qruuid");
				cache($qruuid,$userinfo['openid'],30);
				$this->success('绑定成功');
			}else{
				$this->error('绑定失败！管理员账户已绑定。');
			}
		}
	}
	//第三方用户登录注册
	function ThirdUserLogin($user){
			$openid = $user['openid'];
			$result = Db::name("third_party_user")->where(['openid'=>$openid,'union_id'=>'WX'])->find();
			if (empty($result)) {
				$data   = [
					'user_login'      => $user['login'],
					'user_email'      => '',
					'mobile'          => '',
					'user_nickname'   => $user['nickname'],
					'avatar'          => $user['headimgurl'],
					'user_pass'       => cmf_password($user['user_pass']),
					'last_login_ip'   => get_client_ip(0, true),
					'create_time'     => time(),
					'last_login_time' => time(),
					'user_status'     => 1,
					'user_type'       => 2,//会员
				];
				$userId = Db::name("user")->insertGetId($data);
				$data   = Db::name("user")->where('id', $userId)->find();
				$userdata   = [
					'user_id'      		=> $userId,
					'openid'      		=> $openid,
					'nickname'   		=> $user['nickname'],
					'third_party'     	=> "微信",
					'union_id'      	=> "WX",
					'create_time'      	=> time(),
					'last_login_time'   => time(),
					'login_times'      	=> 1,
					'last_login_ip'    	=> get_client_ip(0, true),
					'more'          	=> $user['headimgurl']
				];
				$partyuserId = Db::name("third_party_user")->insertGetId($userdata);
				cmf_update_current_user($data);
				return 0;
			}else{
				$userdata   = [
					'nickname'   		=> $user['nickname'],
					'last_login_time'  	=> time(),
					'login_times'      	=> $result['login_times'] + 1,
					'last_login_ip'    	=> get_client_ip(0, true),
					'more'          	=> $user['headimgurl']
				];
				Db::name("third_party_user")->where(['openid'=>$openid,'union_id'=>'WX'])->update($userdata);
				$data   = Db::name("user")->where('id', $result['user_id'])->find();
				if($data['user_status']==0){
					return 3;
				}
				cmf_update_current_user($data);
				return 0;
			}
			return 1;
	}
	//微信授权回调
	function WxBack(){
        $this->config = $this->getPlugin('Qrlogin')->getConfig();
        $appid = $this->config['appid'];
        $appsecret = $this->config['appsecret'];
        $redirect_uri = cmf_plugin_url('Qrlogin://Index/WxBack',array(),true);//回调地址
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $dayBegin = mktime(0, 0, 0, $month, $day, $year);
        if (!isset($_GET['code'])) {
            $state = md5(uniqid(rand(), true));
            $callback = urlencode($redirect_uri);
            $wxurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$callback&response_type=code&scope=snsapi_userinfo&state=$state#wechat_redirect";
            $this->redirect($wxurl);
        }else{
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid .'&secret=' . $appsecret . '&code=' . $_GET['code'] .'&grant_type=authorization_code';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            $arr = json_decode($json, 1);
            $access_token = $arr['access_token'];
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $arr['access_token'] .
                '&openid=' . $arr['openid'] . '&lang=zh_CN';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            $userinfo = json_decode($json, 1);
            $openid = $userinfo['openid'];

            if($openid==""){
                echo "登录失败";
                die();
            }
			$qrType 				= cookie("qrType");
			switch ($qrType) {
				case 'web':
					return $this->UserOauthLogin($userinfo);
					break;
				case 'admin':
					//后台登录
					return $this->AdminOauthLogin($userinfo);
					break;
				case 'bind':
					//后台登录
					return $this->BindOauthLogin($userinfo);
					break;
				default :
					return $this->UserOauthLogin($userinfo);
					break;
			} 
        }
	}
	//用户UUID
	function GetUuid() {
	    $Uuid = base_convert(date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(),7,13),1))),0,8),10,36);
	    return $Uuid;
	}
	//微信扫码显示的登录界面
	function QrLogin() {
		if(cmf_is_wechat()){
			$_POST = $this->request->param();
			$data = cache($_POST['q']);
			if($data=='waiting'){
				cache($_POST['q'],'login',60);//设置扫码成功;
				cookie("qruuid", $_POST['q'], 60);
				cookie("qrType", $_POST['t'], 60);
				switch ($_POST['t']) {
					case 'web':
						$dataPage =[
							'url' 		=> cmf_plugin_url('Qrlogin://Index/WxLogin',['t'=>$_POST['t'],'q'=>$_POST['q']]),
							'title'  	=> '确定登录',
							'msg'	 	=> '确定登录本网站'
						];
						break;
					case 'admin':
						//后台登录
						$dataPage =[
							'url' 		=> cmf_plugin_url('Qrlogin://Index/WxLogin',['t'=>$_POST['t'],'q'=>$_POST['q']]),
							'title'  	=> '管理员登录',
							'msg'	 	=> '确定登录本网站后台'
						];
						break;
					case 'bind':
						$dataPage =[
							'url' 		=> cmf_plugin_url('Qrlogin://Index/WxLogin',['t'=>$_POST['t'],'q'=>$_POST['q']]),
							'title'  	=> '绑定管理员',
							'msg'	 	=> '确定绑定管理员微信'
						];
						break;
					default :
						$this->error('请求类型错误[QrLogin]');
				}
				$this->assign("data", $dataPage);
				return $this->fetch("/login");
			}else{
				$this->error('二维码已失效');
			}
		}else{
			$this->error('请使用手机微信扫码');
		}
	}
	//确定登录
	function WxLogin() {
		if(cmf_is_wechat()){
			$_POST = $this->request->param();
			$data = cache($_POST['q']);
			if($data=='login'){
				//扫码成功;
			switch ($_POST['t']) {
				case 'web':
					$this->UserOauthLogin('0');
					break;
				case 'admin':
					//后台登录
					$this->AdminOauthLogin('0');
					break;
				case 'bind':
					//后台登录
					$this->BindOauthLogin('0');
					break;
				default :
					$this->error('请求类型错误[WxLogin]');
					break;
			}
				
			}else{
				$this->error('二维码已失效');
			}
		}else{
			$this->error('请使用手机微信访问');
		}
	}
	//扫码UUID
	function QrUuid() {
	    $qruuid = base_convert(str_replace('.', '', trim(uniqid('uuid', true), 'uuid')),16,36);
		cache($qruuid, 'waiting',180);
	    $this->success($qruuid);
	}
	//轮询
	function checkQrUuid() {
		$_POST = $this->request->param();
		$qruuid = $_POST['q'];
		$qrType = $_POST['t'];
		$data = cache($qruuid);
		if(empty($data)){
			$this->error('二维码无效');
		}
		if($data=='login'){
			$this->success('2');
		}
		if(preg_match("/^[a-z0-9]{64}$/",$data)){
			switch ($qrType) {
				case 'web':
					$TokenData   = Db::name("user_token")->where('token', $data)->find();
					if(!empty($TokenData)){
						$result    = Db::name("user")->where('id', $TokenData['user_id'])->find();
						cmf_update_current_user($result);
						$this->success('3',session('login_http_referer'));
					}else{
						$this->error('无效Token');
					}
					
					break;
				case 'admin':
					//后台登录
					$TokenData   = Db::name("user_token")->where('token', $data)->find();
					if(!empty($TokenData)){
						$result    = Db::name("user")->where('id', $TokenData['user_id'])->find();
					//登入成功页面跳转
						session('ADMIN_ID', $result["id"]);
						session('name', $result["user_login"]);
						$result['last_login_ip']   = get_client_ip(0, true);
						$result['last_login_time'] = time();
						$token                     = cmf_generate_user_token($result["id"], 'web');
						if (!empty($token)) {
							session('token', $token);
						}
						Db::name('user')->update($result);
						cookie("admin_username", $result["user_login"], 3600 * 24 * 30);
						session("__LOGIN_BY_CMF_ADMIN_PW__", null);
						$this->success('3');
					}else{
						$this->error('无效Token');
					}
					break;
				default :
					$this->error('请求类型错误[checkQrUuid]');
					break;
			}
		}
		$this->success('0');
	}

}
