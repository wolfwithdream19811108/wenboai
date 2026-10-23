<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\Cos\lib;

// php 版本  7。2 以上 cos-sdk-v5-7
require_once __DIR__ . '/cos-php-sdk-v5/cos-sdk-v5-7.phar';

use Qcloud\Cos\Client;

class Cos
{

    private $config;

    private $client;

    public function __construct($config)
    {
        $this->config = cmf_get_plugin_config("Cos");
        $secretId = trim($this->config['secret_id']); //"云 API 密钥 SecretId";
        $secretKey = trim($this->config['secret_key']); //"云 API 密钥 SecretKey";
        $this->client = new Client(
            array(
                'region' => trim($this->config['Region']),
                'schema' => $this->config['protocol'], //协议头部，默认为http
                'credentials'=> array(
                    'secretId'  => $secretId ,
                    'secretKey' => $secretKey)));
    }

    /**
     * 文件上传
     * @param string $file 上传文件路径
     * @param string $filePath 文件路径相对于upload目录
     * @param string $fileType 文件类型,image,video,audio,file
     * @param array $param 额外参数
     * @return mixed
     */
    public function upload($file, $filePath, $fileType = 'image', $param = null)
    {	
        $bucket = trim($this->config['bucket']);
        $ret = $this->client->putObject([
            'Bucket' => $bucket,
            'Key' => $file,
            'Body' => fopen($filePath, 'rb')
        ]);
		//上传后的地址
        $previewUrl = $fileType == 'image' ? $this->getPreviewUrl($file) : $this->getFileDownloadUrl($file);
        $url        = $fileType == 'image' ? $this->getImageUrl($file, 'watermark') : $this->getFileDownloadUrl($file);

        return [
            'preview_url' => $previewUrl,
            'url'         => $url,
        ];
    }

    /**
     * 获取图片预览地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getPreviewUrl($file, $style = '')
    {
        $style = empty($style) ? 'watermark' : $style;

        $url = $this->getUrl($file, $style);

        return $url;
    }

    /**
     * 获取图片地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getImageUrl($file, $style = '')
    {
        $style  = empty($style) ? 'watermark' : $style;

        $url = $this->getUrl($file, $style);

        return $url;
    }

    /**
     * 获取文件地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getUrl($file, $style = '')
    {
        $config = $this->config;
        $url    = $this->config['protocol'] . '://' .trim($this->config['domain_file']) .'/'. $file;

        if (!empty($style)) {
            $url = $url . $config['style_separator'] . $style;
        }

        return $url;
    }

    /**
     * 获取文件下载地址
     * @param string $file
     * @param int $expires
     * @return mixed
     */
    public function getFileDownloadUrl($file, $expires = '36000')
    {
        $expiration = time() + $expires;    
        $bucket = $this->config['bucket'];

        $config = $this->config;
        $url    = $this->client->getObjectUrl($bucket, $file, $expires);
        return $url;
    }

    /**
     * 获取云存储域名
     * @return mixed
     */
    public function getDomain()
    {
        return $this->config['domain_file'];
    }

    /**
     * 获取文件相对上传目录路径
     * @param string $url
     * @return mixed
     */
    public function getFilePath($url)
    {
        $parsedUrl = parse_url($url);

        if (!empty($parsedUrl['path'])) {
            $url            = ltrim($parsedUrl['path'], '/\\');
            $config         = $this->config;
            $styleSeparator = $config['style_separator'];

            $styleSeparatorPosition = strpos($url, $styleSeparator);
            if ($styleSeparatorPosition !== false) {
                $url = substr($url, 0, strpos($url, $styleSeparator));
            }
        } else {
            $url = '';
        }

        return $url;
    }
}