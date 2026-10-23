<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-08 22:38:36
// +----------------------------------------------
namespace plugins\third_login\lib\thirdlogin\login;

use plugins\third_login\lib\http\Curl;

/**
 * sina登录
 */
class Sina extends Base
{
	const API_DOMAIN = 'https://api.weibo.com/';

	/**
	 * 第一步:获取登录页面跳转url
	 * @param string $callbackUrl 登录回调地址
	 * @return string
	 */
	public function getAuthUrl($callbackUrl = null) 
	{
		$params = [
			'client_id'		=> $this->appid,
			'redirect_uri'  => null === $callbackUrl ? $this->getRedirectUrl() : $callbackUrl,
			'state'			=> $this->getState()
		];

		return $this->getUrl('oauth2/authorize', $params);
	}

	/**
	 * 第二步:处理回调并获取access_token
	 * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
	 * @return string
	 */
	protected function __getAccessToken($code = '') 
	{
		$params = [
			'grant_type' 	=> 'authorization_code',
			'client_id'		=> $this->appid,
			'client_secret' => $this->appSecret,
			'code' 			=> !empty($code) ? $code : (isset($_GET['code']) ? $_GET['code'] : ''),
			'redirect_uri'  => $this->getRedirectUrl()
		];

		$response = $this->http->post($this->getUrl('oauth2/access_token', $params));
		$this->result = json_decode($response->body, true);

		if(isset($this->result['error_code']))
		{
			throw new \Exception($this->result['error'], $this->result['error_code']);
		} 
		else 
		{
			$this->openid = $this->result['uid'];
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
			'uid'			=> $this->openid
		];

		$response 	  = $this->http->get($this->getUrl('2/users/show.json', $params));
		$this->result = json_decode($response->body, true);

		if(isset($this->result['error_code']))
		{
			throw new \Exception($this->result['error'], $this->result['error_code']);
		} 
		else 
		{
			return $this->result;
		}
	}
}