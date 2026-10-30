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
        $result = $this->uploadFromUrlResult($imageUrl, $savePath);
        return !empty($result['success']) ? $result['url'] : null;
    }

    /** 上传并返回可用于诊断的结构化结果。 */
    public function uploadFromUrlResult(string $imageUrl, string $savePath = '/works/'): array
    {
        $path = (string)(parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl);
        $remotePath = rtrim($savePath, '/') . '/' . basename($path);
        return $this->uploadFile($imageUrl, $remotePath);
    }

    /** 从本地文件或 URL 读取内容后上传到指定远端路径。 */
    public function uploadFile(string $source, string $remotePath): array
    {
        $remotePath = '/' . ltrim($remotePath, '/');
        try {
            $fileContent = @file_get_contents($source);
            if ($fileContent === false) {
                $lastError = error_get_last();
                return [
                    'success' => false,
                    'url' => '',
                    'remote_path' => $remotePath,
                    'error_code' => 'SOURCE_READ_FAILED',
                    'error_message' => isset($lastError['message']) ? $lastError['message'] : '无法读取待上传图片',
                    'http_status' => 0,
                    'context' => [],
                ];
            }

            $uploadInfo = $this->client->write($remotePath, $fileContent);
            if (!$uploadInfo) {
                return [
                    'success' => false,
                    'url' => '',
                    'remote_path' => $remotePath,
                    'error_code' => 'UPYUN_WRITE_FAILED',
                    'error_message' => '又拍云写入未返回成功结果',
                    'http_status' => 0,
                    'context' => [],
                ];
            }

            return [
                'success' => true,
                'url' => $this->domain . $remotePath,
                'remote_path' => $remotePath,
                'error_code' => '',
                'error_message' => '',
                'http_status' => 200,
                'context' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'url' => '',
                'remote_path' => $remotePath,
                'error_code' => 'UPYUN_EXCEPTION',
                'error_message' => $e->getMessage(),
                'http_status' => method_exists($e, 'getCode') && is_numeric($e->getCode()) ? max(0, (int)$e->getCode()) : 0,
                'context' => ['exception_class' => get_class($e)],
            ];
        }
    }

    /**
     * 上传文件内容
     */
    public function upload(string $remotePath, string $content): bool
    {
        try {
            return (bool)$this->client->write($remotePath, $content);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
