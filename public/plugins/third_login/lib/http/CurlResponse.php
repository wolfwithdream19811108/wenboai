<?php
namespace plugins\third_login\lib\http;

/**
 * curl响应信息类
 */

class CurlResponse {
    /**
     * 响应信息
     * @var string
     */
    public $body = '';
    
    /**
     * 响应头信息
     * @var array
     */
    public $headers = array();

    /**
     * 构造函数
     * @param string $response curl响应信息
     */
    function __construct($response = '') {
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';
        
        preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));
        
        # Remove headers from the response body
        $this->body = str_replace($headers_string, '', $response);
        
        # Extract the version and status from the first header
        $version_and_status = array_shift($headers);
        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
        $this->headers['Http-Version'] = $matches[1];
        $this->headers['Status-Code'] = $matches[2];
        $this->headers['Status'] = $matches[2].' '.$matches[3];
        
        # 将头信息转换为关联数组
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->headers[$matches[1]] = $matches[2];
        }
    }
    
    /**
     * 返回curl响应信息
     * @return string
     */
    function __toString() {
        return $this->body;
    }
    
}