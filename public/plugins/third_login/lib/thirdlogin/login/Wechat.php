<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-15 16:16:35
// +----------------------------------------------
namespace plugins\third_login\lib\thirdlogin\login;

/**
 * 微信登录
 */
class Wechat extends Base
{
	/**
	 * api接口域名
	 */
	const API_DOMAIN = 'https://api.weixin.qq.com/';
	/**
	 * 开放平台域名
	 */
	const OPEN_DOMAIN = 'https://open.weixin.qq.com/';

	/**
	 * 获取url地址
	 * @param string $name 跟在域名后的文本
	 * @param array $params GET参数
	 * @return string
	 */
	public function getUrl($name, $params = array())
	{
		if('http' === substr($name, 0, 4))
		{
			$domain = $name;
		}
		else
		{
			$domain = static::API_DOMAIN . $name;
		}

		return sprintf("%s%s", $domain, (empty($params) ? '' : '?'.http_build_query($params)));
	}

	/**
	 * 第一步:获取PC页登录所需的url，一般用于生成二维码
	 * @param string $callbackUrl 登录回调地址
	 * @return string
	 */
	public function getAuthUrl($callbackUrl = null)
	{
		$params = array(
			'appid'				=>	$this->appid,
			'redirect_uri'		=>	null === $callbackUrl ? $this->getRedirectUrl() : $callbackUrl,
			'response_type'		=>	'code',
			'scope'				=>	'snsapi_login',
			'state'				=>	$this->getState(),
		);

		return $this->getUrl(static::OPEN_DOMAIN . 'connect/qrconnect', $params) . '#wechat_redirect';
	}

	/**
	 * 第二步:处理回调并获取access_token
	 * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
	 * @return string
	 */
	protected function __getAccessToken($code = '')
	{
		$params = [
			'grant_type'=> 'authorization_code',
			'appid'		=> $this->appid,
			'secret'	=> $this->appSecret,
			'code' 		=> !empty($code) ? $code : (isset($_GET['code']) ? $_GET['code'] : '')
		];

		$response = $this->http->get($this->getUrl('sns/oauth2/access_token', $params));
		$this->result = json_decode($response->body, true);

		if(isset($this->result['errcode']))
		{
			throw new \Exception($this->result['errmsg'], $this->result['errcode']);
		} 
		else 
		{
			$this->openid = $this->result['openid'];
			return $this->accessToken = $this->result['access_token'];
		}
	}

	/**
	 * 获取用户资料
	 * @param string $accessToken
	 * @return array
	 */
	public function getUserInfo($accessToken = '') 
	{
		if(null === $this->openid) {
			$this->getAccessToken($accessToken);
		}

		$params = [
			'access_token' 	=> empty($accessToken) ? $this->accessToken : $accessToken,
			'openid'		=> $this->openid
		];

		$response 	  = $this->http->get($this->getUrl('sns/userinfo', $params));
		$this->result = json_decode($response->body, true);

		if(isset($this->result['errcode']))
		{
			throw new \Exception($this->result['errmsg'], $this->result['errcode']);
		} 
		else 
		{
			return $this->result;
		}
	}
}