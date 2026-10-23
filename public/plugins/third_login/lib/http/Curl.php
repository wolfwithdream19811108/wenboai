<?php
namespace plugins\third_login\lib\http;
use plugins\third_login\lib\http\CurlResponse;

/**
 * curl请求类
 */

class Curl {
    /**
     * curl请求头信息
     *
     * @var array
    **/
    public $headers = array();
    
    /**
     * curl额外选项
     *
     * @var array
    **/
    public $options = array();
    
    /**
     * 来源url
     *
     * @var string
    **/
    public $referer;
    
    /**
     * 用户代理信息
     *
     * @var string
    **/
    public $userGgent;
    
    /**
     * 错误信息
     *
     * @var string
     * @access protected
    **/
    protected $error = '';
    
    /**
     * curl对象实例
     *
     * @var resource
     * @access protected
    **/
    protected $request;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->cookie_file = dirname(__FILE__).DIRECTORY_SEPARATOR.'curl_cookie.txt';
    }
    
    /**
     * 设置用户代理信息
     * @param string $user_agent 用户代理信息
     * @return  $this
     */
    public function setUserAgent($user_agent = '') {
        if(!empty($user_agent)) {
            $this->userGgent = $user_agent;
        } else {
            $this->userGgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }
        return $this;
    }

    /**
     * 设置请求来源url
     * @param string $referer 来源url
     */
    public function setReferer($referer = ''){
        if(!empty($referer)) {
            $this->referer = $referer;
        }

        return $this;
    }

    /**
     * 设置头信息
     * @param array $headers 头信息
     * @return $this
     */
    public function setHeaders($headers = array()){
        if(is_array($headers)) {
            $this->headers = $headers;
        }

        return $this;        
    }

    /**
     * 设置curl额外选项
     * @param array $options 额外选项
     */
    public function setOptions($options = array()) {
        if(is_array($options)) {
            $this->options = $options;
        }
        return $this;    
    }

    /**
     * delete请求
     * @param  string        $url  请求url
     * @param  array|string  $vars 请求参数
     * @return CurlResponse object
     */
    public function delete($url = '', $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }

    /**
     * get请求
     * @param  string        $url  请求url
     * @param  array|string  $vars 请求参数
     * @return CurlResponse object
     */
    public function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }
    
    /**
     * head请求
     * @param  string        $url  请求url
     * @param  array|string  $vars 请求参数
     * @return CurlResponse object
     */
    public function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }

    /**
     * post请求
     * @param  string        $url  请求url
     * @param  array|string  $vars 请求参数
     * @return CurlResponse object
     */
    public function post($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }

    /**
     * put请求
     * @param  string        $url  请求url
     * @param  array|string  $vars 请求参数
     * @return CurlResponse object
     */
    public function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }

    /**
     * 发起http请求
     * @param  string        $method 请求方式
     * @param  string        $url    请求url
     * @param  array|string  $vars   请求参数
     * @return CurlResponse|boolean
     */
    public function request($method, $url, $vars = array()) {
        $this->request = curl_init();
        if (is_array($vars)) $vars = http_build_query($vars, '', '&');
        
        $this->setRequestMethod($method);
        $this->setRequestOptions($url, $vars);
        $this->setRequestHeaders();
        
        $response = curl_exec($this->request);
        
        if ($response) {
            $response = new CurlResponse($response);
        } else {
            $this->setError(curl_errno($this->request).' - '.curl_error($this->request));
        }
        
        curl_close($this->request);
        
        return $response;
    }

    /**
     * 设置请求头信息
     */
    protected function setRequestHeaders() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * 设置请求方式
     * @param string $method 请求方式
     */
    protected function setRequestMethod($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * 设置curl请求选项
     * @param string $url  请求url
     * @param string $vars 请求参数
     */
    protected function setRequestOptions($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);
        if (!empty($vars)) curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        
        # 设置默认的curl选项
        # 设定返回信息中包含响应头信息
        curl_setopt($this->request, CURLOPT_HEADER, true);

        # true 将curl_exec()获取的信息以字符串返回，而不是直接输出。
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);

        # 在HTTP请求中包含一个"User-Agent: "头的字符串
        if(!empty($this->userGgent)) {
            curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        }
        
        # 在HTTP请求中包含一个"referer"头的字符串
        if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);

        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, FALSE);

        # 额外curl选项
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

    /**
     * 设置错误
     */
    public function setError($error){
        $this->error = $error;
    }

    /**
     * 获取错误
     * @return string
     */
    public function getError() {
        return $this->error;
    }
}