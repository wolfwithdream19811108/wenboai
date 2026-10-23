<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\oss\lib;
require_once __DIR__ . '/aliyun-oss-php-sdk-2.3.1.phar';

use OSS\OssClient;
use OSS\Core\OssException;
use think\facade\Log;


class Oss
{

    private $config;

    private $storageRoot;
	
	private $endpoint;
	
	private $domain;


    /**
     * @var \plugins\qiniu\QiniuPlugin
     */
    private $plugin;

    /**
     * Qiniu constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $pluginClass = cmf_get_plugin_class('Oss');

        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();
		$this->domain = empty($this->config['domain'])?$this->config['bucket'].'.'.$this->config['endpoint']:$this->config['domain'];
        $this->storageRoot = $this->config['protocol'] . '://' . $this->domain . '/';
		$this->endpoint = $this->config['protocol'] . '://' . empty($this->config['domain'])?$this->config['endpoint']:$this->config['domain'] . '/';
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
		$accessKeyId = $this->config['accessKeyId'];//"<您从OSS获得的AccessKeyId>";
		$accessKeySecret = $this->config['accessKeySecret'];//"<您从OSS获得的AccessKeySecret>";
		$endpoint = $this->endpoint;//"<您选定的OSS数据中心访问域名，例如http://oss-cn-hangzhou.aliyuncs.com>";
		$bucket= $this->config['bucket'];//" <您使用的Bucket名字，注意命名规范>";
		$object = $file;
        $filePath = realpath($filePath);

		try {
			$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
			$res = $ossClient->uploadFile($bucket, $object, $filePath);
		} catch (OssException $e) {

//			print $e->getMessage();
			Log::record('upload: class oss line '.__LINE__.' error: '.$e->getMessage(),'oss');
			return ;
		}

		
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
    public function getPreviewUrl($file, $style = 'watermark')
    {
        $url = $this->getUrl($file, $style);

        return $url;
    }

    /**
     * 获取图片地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getImageUrl($file, $style = 'watermark')
    {
        $config = $this->config;
        $url    = $this->storageRoot . $file;

        if (!empty($style)) {
            if(empty($config['style_separator'])){
                $url = $url . '?x-oss-process='.$config['styles_watermark'];
            } else {
                $url = $url . $config['style_separator'] . $style;
            }
            
        }

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
        $url    = $this->storageRoot . $file;

        if (!empty($style)) {
            if(empty($config['style_separator'])){
                $url = $url . '?x-oss-process='.$config['styles_watermark'];
            } else {
                $url = $url . $config['style_separator'] . $style;
            }
            
        }

        return $url;
    }

    /**
     * 获取文件下载地址
     * @param string $file
     * @param int $expires
     * @return mixed
     */
    public function getFileDownloadUrl($file, $expires = 3600)
    {
        $accessKeyId = $this->config['accessKeyId'];//"<您从OSS获得的AccessKeyId>";
		$accessKeySecret = $this->config['accessKeySecret'];//"<您从OSS获得的AccessKeySecret>";
		$endpoint = $this->endpoint;//"<您选定的OSS数据中心访问域名，例如http://oss-cn-hangzhou.aliyuncs.com>";
		$bucket= $this->config['bucket'];//" <您使用的Bucket名字，注意命名规范>";
		$object = $file;
		$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
		$timeout = $expires; // URL的有效期是3600秒
		try{
			$signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
		} catch(OssException $e) {
            Log::record('getFileDownloadUrl: class oss line '.__LINE__.' error: '.$e->getMessage(),'oss');
            return ;
		}
        return $signedUrl;
    }

    /**
     * 获取云存储域名
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
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
            if(empty($config['style_separator'])){
                $styleSeparator = '?x-oss-process=';
            }
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