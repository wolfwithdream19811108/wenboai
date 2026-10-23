<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-05 00:26:05
// +----------------------------------------------
namespace plugins\third_login\lib\thirdlogin;

class ThirdLogin{
	/**
	 * 操作句柄
	 * @var object
	 */
	protected $handler = null;

	/**
	 * 登录类型
	 * @var array
	 */
	protected $loginType = ['qq', 'sina', 'wechat'];

	/**
	 * 登录类型
	 * @var string
	 */
	protected $type;


	/**
	 * 构造函数
	 * @param array $config 登录配置
	 */
	public function __construct($type = 'qq', $appid = null, $appSecret = null, $callbackUrl = null) {
		$this->type = $type;
		$this->init($appid, $appSecret, $callbackUrl);
	}

	/**
	 * 初始化
	 * @param  array  $config 登录配置
	 * @return Driver
	 */
	public function init($appid = null, $appSecret = null, $callbackUrl = null){
		if(is_null($this->handler)) {
			$this->handler = $this->initLogin($appid, $appSecret, $callbackUrl);
		}

		return $this->handler;
	}

	/**
	 * 初始化登录实例
	 * @param  array  $config 登录配置
	 * @return Driver
	 */
	public function initLogin($appid, $appSecret, $callbackUrl){
		$class = '\\plugins\\third_login\\lib\\thirdlogin\\login\\'.ucwords($this->type);
		return new $class($appid, $appSecret, $callbackUrl);
	}

	public function __call($method, $args) {
		return call_user_func_array([$this->init(), $method], $args);
	}
}