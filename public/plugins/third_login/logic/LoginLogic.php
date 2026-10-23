<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-06 22:36:11
// +----------------------------------------------
namespace plugins\third_login\logic;

use plugins\third_login\model\QqModel;
use think\Db;
use plugins\third_login\lib\Util;

class LoginLogic{
	/**
	 * 判断用户是否存在
	 * @param  string $openid openid
	 * @return integer
	 */
	public function userIsExists($openid = ''){
		return Db::name('third_party_user')->where(['openid' => $openid])->value('user_id');
	}

	/**
	 * 注册新的用户
	 * @param  array $data 用户信息
	 * @return bool|integer
	 */
	public function register($user){
		$user_name = $this->generateUserName();

        $site_user   = [
            'user_login'      => $user_name,
            'user_email'      => '',
            'mobile'          => '',
            'user_nickname'   => $user['nickname'],
            'user_pass'       => cmf_password($user_name),
            'avatar'          => $this->downloadAvatar($user['avatar']),
            'sex'             => $this->findSex($user['gender']),
            'last_login_ip'   => get_client_ip(0, true),
            'create_time'     => time(),
            'last_login_time' => time(),
            'user_status'     => 1,
            "user_type"       => 2,//会员
        ];

        Db::startTrans();
        try{
        	$user_id = Db::name('user')->insertGetId($site_user);
        	
        	$third_user = [
        		'user_id' 			=> $user_id,
        		'last_login_time'  	=> time(),
        		'create_time'       => time(),
        		'login_times'       => 0,
        		'status'            => 1,
        		'nickname'          => $user['nickname'],
        		'third_party'       => $user['third_party'],
        		'last_login_ip'		=> get_client_ip(0, true),
        		'openid'  			=> $user['openid']
        	];

        	Db::name('third_party_user')->insert($third_user);
        	Db::commit();
        }catch(\Exception $e) {
        	Db::rollback();
        	return false;
        }

		return $user_id;
	}

	/**
	 * 登录
	 * @param  integer $user_id 用户id
	 * @return bool
	 */
	public function login($user_id = 0)
	{
		$user = Db::name('user')->where(['id' => $user_id])->find();
		if(!empty($user)) 
		{
			session('user', $user);
			$this->updateUserInfo($user_id);
			return true;
		}
		return false;
	}

	/**
	 * 更新用户登录信息
	 * @param  string $user_id 用户id
	 */
	public function updateUserInfo($user_id)
	{
		$update_info = [
			'last_login_time' => time(),
			'login_times'	  => Db::raw('login_times+1'),
			'last_login_ip'	  => get_client_ip(0, true)
		];

		Db::name('third_party_user')->where('user_id', $user_id)->update($update_info);
	}
	
	/**
	 * 将性别转为数字表示
	 * @param  string $sex_name 性别名
	 * @return integer
	 */
	public function findSex($sex_name){
		$sex = [
			['保密', 'n'],
			['男', 'm'],
			['女', 'f']
		];

		foreach ($sex as $key => $value) {
			if(false !== array_search($sex_name, $value)) {
				return $key;
			}
		}

		return $sex_name;
	}

	/**
	 * 创建随机用户名
	 * @return string
	 */
	private function generateUserName(){
		$letters 	= range('a', 'z');
		$user_name  = '';

		for($i = 0; $i < 3; $i++) {
			$user_name .= $letters[mt_rand(0, 25)];
		}

		$timestamp_arr = explode(' ',microtime());
		$user_name .= $timestamp_arr[0] * 100000000;

		if(!$this->checkUserName($user_name)) {
			return $user_name;
		} else {
			self::generateUserName();
		}
	}

	/**
	 * 检查用户名是否存在
	 * @param  string $user_name 用户名
	 * @return bool
	 */
	private function checkUserName($user_name = ''){
		$count = Db::name('user')->where(['user_login' => $user_name])->count();
		return $count > 0 ? true : false;
	}

	/**
	 * 下载用户头像
	 * @param  stirng $url 下载url
	 * @return string
	 */
	public function downloadAvatar($url) {
		$img_name = md5(microtime());
		$path = sprintf('avatar/%s/%s.jpg', date('Ymd', time()), $img_name);
		Util::downloadRemoteFile($url, request()->root().'upload/'.$path);
		return $path;
	}

	/**
	 * 获取成功后跳转url
	 * @return string
	 */
	public function getLoginHttpReferer(){
		$login_http_referer = !empty(session('login_http_referer')) ? session('login_http_referer') : request()->root();

		return $login_http_referer;
	}
}