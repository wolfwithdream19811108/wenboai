<?php
// +----------------------------------------------------------------------
// | 又拍云上传服务
// +----------------------------------------------------------------------
namespace api\ai\service\storage;
$upyunLoader = require VENDOR_PATH . 'upyun/vendor/autoload.php';
// The project already loads Guzzle 7. Keep that loader ahead of Upyun's
// bundled legacy Guzzle to avoid mixing functions from v7 with classes from v6.
if (is_object($upyunLoader)
    && method_exists($upyunLoader, 'unregister')
    && method_exists($upyunLoader, 'register')) {
    $upyunLoader->unregister();
    $upyunLoader->register(false);
}
use Upyun\Upyun;
use Upyun\Config;

class UpyunService
{
    private $client;
    private $domain;

    public function __construct(string $service, string $operator, string $password, string $domain)
    {
        $config = new Config($service, $operator, $password);
        $this->client = new Upyun($config);
        $this->domain = rtrim($domain, '/');
    }

    /**
     * 从URL上传图片到又拍云
     *
     * @param string $imageUrl 原图片URL
     * @param string $savePath 保存路径（如 /works/）
     * @return string|null 成功返回完整URL，失败返回null
     */
    public function uploadFromUrl(string $imageUrl, string $savePath = '/works/'): ?string
    {
        try {
            $remotePath = $savePath . basename($imageUrl);
            $fileContent = file_get_contents($imageUrl);

            if ($fileContent === false) {
                return null;
            }

            $uploadInfo = $this->client->write($remotePath, $fileContent);

            if ($uploadInfo) {
                return $this->domain . $remotePath;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 上传文件内容
     */
    public function upload(string $remotePath, string $content): bool
    {
        try {
            return (bool)$this->client->write($remotePath, $content);
        } catch (\Exception $e) {
            return false;
        }
    }
}
