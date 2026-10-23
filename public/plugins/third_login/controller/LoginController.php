<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-04 22:46:35
// +----------------------------------------------
namespace plugins\third_login\controller;

use cmf\controller\PluginBaseController;
use plugins\third_login\lib\thirdlogin\ThirdLogin;
use plugins\third_login\model\ConfigModel;
use plugins\third_login\logic\LoginLogic;

class LoginController extends PluginBaseController{
	/**
	 * 第三方登录入口
	 */
	public function auth(){
		$type 			= input('param.type', '');
		$third_login	= ['qq', 'sina','wechat'];

		if(empty($type) || !in_array($type, $third_login)) {
			$this->error('登录出错！');
		}

		if(session('?user')) {
			$this->error('已经登录过了');
		}

		$config = (new ConfigModel())->findOne(['type' => $type]);

		session('login_http_referer', $this->request->server('HTTP_REFERER'));

		$ThirdLogin = new ThirdLogin($type, $config['app_id'], $config['app_secret'], sprintf("%s/plugin/third_login/login/%s.html", request()->domain(), $type));
		$url = $ThirdLogin->getAuthUrl();

		session('third_login_state', $ThirdLogin->getState());

		header('Location:'. $url);
	}

	/**
	 * qq登录回调处理
	 */
	public function qq(){
		$config = (new ConfigModel())->findOne(['type' => 'qq']);

		$ThirdLogin = new ThirdLogin('qq', $config['app_id'], $config['app_secret'], sprintf("%s/plugin/third_login/login/qq.html", request()->domain()));

		try
		{
			$access_token = $ThirdLogin->getAccessToken(session('third_login_state'));
			$openid 	  = $ThirdLogin->getOpenID($access_token);
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		$LoginLogic = new LoginLogic;
		$user_id = $LoginLogic->userIsExists($openid);
		if($user_id) {
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
			exit;
		}

		try
		{
			$user_info 	= $ThirdLogin->getUserInfo($access_token);
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		$new_user = [
			'nickname' 		=> $user_info['nickname'],
			'gender'   		=> $user_info['gender'],
			'avatar'		=> $user_info['figureurl_qq_1'],
			'openid'		=> $openid,
			'third_party'	=> 'qq'
		];

		$user_id = $LoginLogic->register($new_user);
		if(false !== $user_id) 
		{
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} 
			else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
		} 
		else 
		{
			$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
		}
	}

	/**
	 * 新浪微博登录回调
	 */
	public function sina(){
		$config = (new ConfigModel())->findOne(['type' => 'sina']);

		$ThirdLogin = new ThirdLogin('sina', $config['app_id'], $config['app_secret'], sprintf("%s/plugin/third_login/login/sina.html", request()->domain()));

		try
		{
			$access_token = $ThirdLogin->getAccessToken(session('third_login_state'));
			$openid       = $ThirdLogin->openid();
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		//如果openid已经注册，直接登录
		$LoginLogic = new LoginLogic;
		$user_id = $LoginLogic->userIsExists($openid);
		if($user_id) {
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
			exit;
		}

		//openid未注册
		try
		{
			$user_info = $ThirdLogin->getUserInfo($access_token);
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		$new_user = [
			'nickname' 		=> $user_info['screen_name'],
			'gender'   		=> $user_info['gender'],
			'avatar'		=> $user_info['profile_image_url'],
			'openid'		=> $openid,
			'third_party'	=> '新浪微博'
		];

		$user_id = $LoginLogic->register($new_user);
		if(false !== $user_id) 
		{
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} 
			else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
		} 
		else 
		{
			$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
		}
	}

	/**
	 * 微信登录回调
	 */
	public function wechat(){
		$config = (new ConfigModel())->findOne(['type' => 'wechat']);

		$ThirdLogin = new ThirdLogin('wechat', $config['app_id'], $config['app_secret'], sprintf("%s/plugin/third_login/login/wechat.html", request()->domain()));

		try
		{
			$access_token = $ThirdLogin->getAccessToken(session('third_login_state'));
			$openid       = $ThirdLogin->openid();
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		//如果openid已经注册，直接登录
		$LoginLogic = new LoginLogic;
		$user_id = $LoginLogic->userIsExists($openid);
		if($user_id) {
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
			exit;
		}

		//openid未注册
		try
		{
			$user_info = $ThirdLogin->getUserInfo($access_token);
		}catch(\Exception $e)
		{
			echo $e->getMessage();
			exit;
		}

		$new_user = [
			'nickname' 		=> $user_info['nickname'],
			'gender'   		=> $user_info['sex'],
			'avatar'		=> $user_info['headimgurl'],
			'openid'		=> $openid,
			'third_party'	=> '微信'
		];

		$user_id = $LoginLogic->register($new_user);
		if(false !== $user_id) 
		{
			if($LoginLogic->login($user_id)) 
			{
				$this->success('登录成功', $LoginLogic->getLoginHttpReferer());
			} 
			else 
			{
				$this->error('登录失败', $LoginLogic->getLoginHttpReferer());
			}
		} 
		else 
		{
			$this->error('登录失败，第三方注册失败', $LoginLogic->getLoginHttpReferer());
		}
	}
}