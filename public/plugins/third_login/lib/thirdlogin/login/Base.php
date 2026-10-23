<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-04 21:24:05
// +----------------------------------------------
namespace plugins\third_login\lib\thirdlogin\login;

use plugins\third_login\lib\http\Curl;

/**
 * 第三方登录基类
 */

abstract class Base{
	/**
	 * http请求类
	 * @var Curl object
	 */
	protected $http;

	/**
	 * 应用唯一标识
	 * @var string
	 */
	protected $appid;

	/**
	 * appid对应的密钥
	 * @var string
	 */
	protected $appSecret;

	/**
	 * 登录回调地址
	 * @var string
	 */
	protected $callbackUrl;

	/**
	 * state值 调用getAuthorizationCode方法后获取到
	 * @var string
	 */
	protected $state;

	/**
	 * 接口调用结果
	 * @var array
	 */
	protected $result;

	/**
	 * 错误信息
	 * @var string
	 */
	protected $error  = '';

	/**
	 * openid，调用getOpenID后可以获取到
	 * @var string
	 */
	protected $openid;

	/**
	 * 构造方法
	 * @param string $appid 应用的唯一标识
	 * @param string $appSecret appid对应的密钥
	 * @param string $callbackUrl 登录回调地址
	 */
	public function __construct($appid = null, $appSecret = null, $callbackUrl = null)
	{
		$this->appid 		= $appid;
		$this->appSecret	= $appSecret;
		$this->callbackUrl  = $callbackUrl;
		$this->http         = new Curl();
	}

	/**
	 * 检测state是否相等
	 * @param string $storeState 本地存储的正确的state
	 * @param string $state 	 回调传递过来的state
	 * @return bool
	 */
	public function checkState($storeState, $state = null)
	{
		if(null == $state)
		{
			if(null === $this->state)
			{
				if(isset($_GET['state']))
				{
					$state = $_GET['state'];
				} 
				else 
				{
					$state = '';
				}
			} 
			else
			{
				$state = $this->state;
			}
		}

		return $storeState === $state;
	}

	/**
	 * 获取状态值 用于第三方应用防止CSRF攻击
	 * @return string
	 */
	public function getState()
	{
		if(null === $this->state) 
		{
			$this->state = md5(uniqid(rand(), true));
		}

		return $this->state;
	}

	/**
	 * 第一步:获取登录页面跳转url
	 * @param string $callbackUrl 登录回调地址
	 * @return string
	 */
	public abstract function getAuthUrl($callbackUrl = '');

	/**
	 * 第二步:处理回调并获取access_token。与getAccessToken不同的是会验证state值是否匹配，防止csrf攻击。
	 * @param string $storeState 存储的正确的state
	 * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
	 * @param string $state 回调接收到的state，为null则通过get参数获取
	 * @return string
	 */
	public function getAccessToken($storeState = '', $code = '', $state = '')
	{
		if(!$this->checkState($storeState, $state))
		{
			throw new \InvalidArgumentException('state验证失败');
		}

		return $this->__getAccessToken($code);
	}

	/**
	 * 第二步:处理回调并获取access_token
	 * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
	 * @return string
	 */
	protected abstract function __getAccessToken($code = '');

	/**
	 * 获取回调地址
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->callbackUrl;
	}

	/**
	 * 获取用户资料
	 * @param string $accessToken
	 * @return array
	 */
	public abstract function getUserInfo($accessToken = null);

	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
		return $this->error;
	}

	/**
	 * 设置错误信息
	 * @param string $error 错误信息
	 */
	public function setError($error = '') {
		return $this->error = $error;
	}

	/**
	 * 返回openid值
	 * @return string
	 */
	public function openid(){
		return $this->openid;
	}

	/**
	 * 获取url地址
	 * @param  string $name   跟在域名后的文本
	 * @param  array  $params 请求参数
	 * @return string
	 */
	public function getUrl($name, $params = [])
	{
		return sprintf("%s%s%s", static::API_DOMAIN, $name, (empty($params) ? '' : '?'.http_build_query($params)));
	}
}