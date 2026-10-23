<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-04 21:23:20
// +----------------------------------------------
namespace plugins\third_login\lib\thirdlogin\login;

/**
 * qq登录
 */
class Qq extends Base{
	const API_DOMAIN = 'https://graph.qq.com/';

	/**
	 * 第一步:获取登录页面跳转url
	 * @param string $callbackUrl 登录回调地址
	 * @return string
	 */
	public function getAuthUrl($callbackUrl = null) 
	{
		$params = [
			'response_type' => 'code',
			'client_id'		=> $this->appid,
			'redirect_uri'  => null === $callbackUrl ? $this->getRedirectUrl() : $callbackUrl,
			'state'			=> $this->getState()
		];

		return $this->getUrl('oauth2.0/authorize', $params);
	}

	/**
	 * 第二步:处理回调并获取access_token
	 * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
	 * @return string
	 */
	protected function __getAccessToken($code = '') {
		$params = [
			'grant_type' 	=> 'authorization_code',
			'client_id'		=> $this->appid,
			'client_secret' => $this->appSecret,
			'code' 			=> !empty($code) ? $code : (isset($_GET['code']) ? $_GET['code'] : ''),
			'redirect_uri'  => $this->getRedirectUrl()
		];

		$response = $this->http->get($this->getUrl('oauth2.0/token', $params));
		$this->result = $this->parseAccessTokenResponse($response->body);

		if(isset($this->result['error']) && 0 != $this->result['error'])
		{
			throw new \Exception($this->result['error_description'], $this->result['error']);
		}
		else
		{
			return $this->accessToken = $this->result['access_token'];
		}
	}

	/**
	 * 获取用户openid
	 * @param  string $accessToken access token
	 * @return string
	 */
	public function getOpenID($accessToken = '')
	{
		$params = [
			'access_token' 	=> empty($access_token) ? $this->accessToken : $access_token
		];

		$response = $this->http->get($this->getUrl('oauth2.0/me', $params));
		$this->result   = $this->parseOpenIdResponse($response->body);

		if(isset($this->result['error']) && 0 != $this->result['error'])
		{
			throw new \Exception($this->result['error_description'], $this->result['error']);
		}
		else
		{
			$this->openid = $this->result['openid'];
			return $this->openid;
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
			'openid'		=> $this->openid,
			'oauth_consumer_key' => $this->appid
		];

		$response 	  = $this->http->get($this->getUrl('user/get_user_info', $params));
		$this->result = json_decode($response->body, true);

		if(isset($this->result['ret']) && 0 != $this->result['ret'])
		{
			throw new \Exception($this->result['msg'], $this->result['ret']);
		}
		else
		{
			return $this->result;
		}
	}

	/**
	 * 解析AccessToken响应信息
	 * @param  string $response 响应信息
	 * @return array|bool
	 */
	protected function parseAccessTokenResponse($response){
		if(strpos($response, 'callback') !== false) {
			$lpos = strpos($response, '(');
			$rpos = strpos($response, ')');

			$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
			$error 	   = json_decode($response, true);

			return $error;
		}

		$params = [];
		parse_str($response, $params);
		return $params;
	}

	/**
	 * 解析OpenId响应信息
	 * @param  string $response 响应信息
	 * @return array|bool
	 */
	protected function parseOpenIdResponse($response) {
        if(strpos($response, "callback") !== false){
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos -1);
        }

        $response = json_decode($response, true);
        if(isset($response->error)) {
        	$this->setError($response);
        	return false;
        }

        return $response;
	}
}