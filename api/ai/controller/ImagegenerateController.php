<?php
// +----------------------------------------------------------------------
// | 图片生成控制器（包含无界AI和即梦4.0）
// +----------------------------------------------------------------------
namespace api\ai\controller;

use api\ApiHelper;
use cmf\controller\RestBaseController;
use app\user\model\UserPointsModel;
use api\ai\service\external\WujieService;
use api\ai\service\external\JimengService;
use api\ai\service\external\VolcEngineSignService;
use api\ai\service\storage\UpyunService;
use api\ai\service\external\YidunTextCheckService;
use app\ai\service\ImageGenerationConfig;
use app\imageai\service\ApiKeyService;
use app\imageai\service\BillingService;
use app\imageai\service\RequestService;
require_once __DIR__ . '/AigcPngMetadata.php';

class ImagegenerateController extends RestBaseController
{
    private $SYNC_IMAGE_UPYUN;
    private $volcAccessKeyId;
    private $volcSecretAccessKey;
    private $testApiKeys = [];
    private $pollInterval;
    private $maxWaitTime;
    private $defaultModel;
    private $localSaveRelDir;
    private $localUrlBase;
    private $enableAigcMetadata;
    private $aigcLabel;
    private $aigcContentProducer;
    private $aigcContentPropagator;
    private $aigcXmpPrefix;
    private $aigcXmpNamespace;
    private $aigcIdPrefix;
    private $enableVisibleWatermark;
    private $visibleWatermarkText;
    private $visibleWatermarkSizePercent;
    private $visibleWatermarkOpacityPercent;
    private $visibleWatermarkPosition;
    private $visibleWatermarkFontFile = 'AigcWatermarkFont.ttf';

    private $api;
    private $wujieService;
    private $jimengService;
    private $upyunService;
    private $yidunService;
    private $classifyEnabled;
    private $classifyApiKey;
    private $classifyApiUrl;
    private $classifyModel;
    private $classifyTimeout;
    private $classifySystemPrompt;
    private $upyunRemoteDir;
    private $configurationErrors = [];
    private $dryRunConfigurationErrors = [];
    private $imageAiRequestId = '';
    private $imageAiApiKey = null;
    private $imageAiPrompt = '';
    private $imageAiModel = '';
    private $imageAiStartedAt = 0.0;
    private $imageAiPointsReserved = 0;
    private $imageAiResponseLogged = false;
    private $imageAiPointsPerImage = 50;
    private $imageAiRateLimitPerMinute = 60;
    private $imageAiTaskId = '';
    private $imageAiWidth = 0;
    private $imageAiHeight = 0;
    private $imageAiProduceId = '';

    public function __construct()
    {
        parent::__construct();
        $this->api = ApiHelper::getInstance();

        $settings = ImageGenerationConfig::settings();
        $keys = ImageGenerationConfig::keys();

        $this->SYNC_IMAGE_UPYUN = (bool)$settings['storage']['sync_upyun'];
        $this->volcAccessKeyId = trim((string)($keys['volc_access_key_id'] ?? ''));
        $this->volcSecretAccessKey = trim((string)($keys['volc_secret_access_key'] ?? ''));
        $this->testApiKeys = isset($keys['image_test_api_keys']) && is_array($keys['image_test_api_keys'])
            ? array_values(array_filter(array_map('trim', $keys['image_test_api_keys'])))
            : [];
        $this->pollInterval = (int)$settings['endpoint']['poll_interval'];
        $this->maxWaitTime = (int)$settings['endpoint']['max_wait_time'];
        $this->defaultModel = trim((string)$settings['endpoint']['default_model']);
        $this->imageAiPointsPerImage = max(0, (int)($settings['endpoint']['api_points'] ?? 50));
        $this->imageAiRateLimitPerMinute = max(1, (int)($settings['endpoint']['rate_limit_per_minute'] ?? 60));
        $this->localSaveRelDir = trim((string)$settings['storage']['local_save_rel_dir'], '/\\');
        $this->localUrlBase = rtrim((string)$settings['storage']['local_url_base'], '/');
        $this->upyunRemoteDir = '/' . trim((string)$settings['storage']['upyun_remote_dir'], '/') . '/';

        $this->enableAigcMetadata = (bool)$settings['metadata']['enabled'];
        $this->aigcLabel = trim((string)$settings['metadata']['label']);
        $this->aigcContentProducer = trim((string)$settings['metadata']['content_producer']);
        $this->aigcContentPropagator = trim((string)$settings['metadata']['content_propagator']);
        $this->aigcXmpPrefix = trim((string)$settings['metadata']['xmp_prefix']);
        $this->aigcXmpNamespace = trim((string)$settings['metadata']['xmp_namespace']);
        $this->aigcIdPrefix = trim((string)$settings['metadata']['id_prefix']);

        $this->enableVisibleWatermark = (bool)$settings['watermark']['enabled'];
        $this->visibleWatermarkText = trim((string)$settings['watermark']['text']);
        $this->visibleWatermarkSizePercent = (int)$settings['watermark']['size_percent'];
        $this->visibleWatermarkOpacityPercent = (int)$settings['watermark']['opacity_percent'];
        $this->visibleWatermarkPosition = (string)$settings['watermark']['position'];

        $this->classifyEnabled = (bool)$settings['classifier']['enabled'];
        $this->classifyApiKey = trim((string)($keys['image_classify_api_key'] ?? ''));
        $this->classifyApiUrl = trim((string)$settings['classifier']['api_url']);
        $this->classifyModel = trim((string)$settings['classifier']['model']);
        $this->classifyTimeout = (int)$settings['classifier']['timeout'];
        $this->classifySystemPrompt = trim((string)$settings['classifier']['system_prompt']);
        $this->configurationErrors = ImageGenerationConfig::runtimeErrors($settings, $keys);
        $this->dryRunConfigurationErrors = ImageGenerationConfig::dryRunErrors($settings, $keys);

        $this->initServices();
    }

    private function initServices()
    {
        $this->wujieService = new WujieService();
        $this->yidunService = new YidunTextCheckService();

        $volcSignService = new VolcEngineSignService(
            $this->volcAccessKeyId,
            $this->volcSecretAccessKey
        );
        $this->jimengService = new JimengService($volcSignService);

        if ($this->SYNC_IMAGE_UPYUN) {
            $keys = ImageGenerationConfig::keys();
            if (!empty($keys['upyun_service']) && !empty($keys['upyun_operator'])
                && !empty($keys['upyun_password'])) {
                $this->upyunService = new UpyunService(
                    (string)$keys['upyun_service'],
                    (string)$keys['upyun_operator'],
                    (string)$keys['upyun_password'],
                    (string)ImageGenerationConfig::settings()['storage']['upyun_domain']
                );
            }
        }
    }

    // ==================== 无界AI接口 ====================

    /**
     * 创建图片生成任务
     */
    public function create()
    {
        if (!$this->api->validateMethod('POST')) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $data = $this->api->parseJson([
            'isPro' => ['required' => true, 'type' => 'boolean'],
            'num' => ['type' => 'integer', 'min' => 1, 'max' => 10],
            'batch_count' => ['type' => 'integer', 'min' => 1, 'max' => 10],
        ]);

        if ($data === false) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $picNum = ApiHelper::filterPositiveInt($data['num'] ?? $data['batch_count'] ?? 0, 0, 10);
        if ($picNum <= 0) {
            return ApiHelper::error('缺少num或batch_count参数', 400);
        }

        if (!$this->api->checkLoginAndPoints($picNum)) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $isPro = filter_var($data['isPro'], FILTER_VALIDATE_BOOLEAN);
        $rawData = file_get_contents('php://input');

        $response = $this->wujieService->createTask($rawData, $isPro);

        if (!$response['success'] && $response['error']) {
            return ApiHelper::error('请求失败: ' . $response['error'], 500);
        }

        $responseData = json_decode(str_replace('\\', '', $response['data']), true);
        return json_last_error() === JSON_ERROR_NONE
            ? json($responseData)
            : ApiHelper::error('响应解析失败', 500);
    }
    /**
     * 按接口规范开发的文生图测试接口
     *
     * 请求体:
     * {
     *   "model": "jianyuan",      // 非必选，默认 jianyuan
     *   "max_tokens": 1024,       // 接收但忽略
     *   "stream": true/false,     // true: 结果放choices; false: 结果放content
     *   "dry_run": true/false,    // true: 只执行鉴权、校验和类目判断，不调用生图
     *   "dialogue": [
     *     {"role": "user", "content": "画一只猫"}
     *   ]
     * }
     *
     * 响应（不论成功失败 HTTP 状态码均为 200）:
     * {
     *   "content": "图片URL",     // 非流式结果
     *   "choices": [...],         // 流式结果
     *   "status": "success/failed",
     *   "reason": "success/失败原因"
     * }
     */
    public function create_for_test()
    {
        $this->imageAiRequestId = RequestService::newId();
        $this->imageAiStartedAt = microtime(true);
        // 关闭执行时间限制，防止轮询期间脚本被中断
        @set_time_limit($this->maxWaitTime + $this->classifyTimeout + 30);
        @ignore_user_abort(true);

        // 1. 校验请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->specResponse(false, '', '请求方法不支持，请使用POST方法', false);
        }

        // 2. 校验API_KEY
        $apiKey = $this->getBearerToken();
        $validApiKey = false;
        if ($apiKey !== '') {
            foreach ($this->testApiKeys as $allowedApiKey) {
                if (hash_equals($allowedApiKey, $apiKey)) {
                    $validApiKey = true;
                    break;
                }
            }
            if (!$validApiKey) {
                $this->imageAiApiKey = (new ApiKeyService())->authenticate($apiKey, get_client_ip(0, true));
                $validApiKey = !empty($this->imageAiApiKey);
            }
        }
        if (!$validApiKey) {
            return $this->specResponse(false, '', '鉴权失败：API_KEY无效', false);
        }
        if ($this->imageAiApiKey
            && !(new ApiKeyService())->checkRateLimit($this->imageAiApiKey['id'], $this->imageAiRateLimitPerMinute)) {
            return $this->specResponse(false, '', '请求过于频繁，请稍后再试', false);
        }

        // 3. 解析请求体
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $this->specResponse(false, '', '无效的JSON格式', false);
        }

        // 4. 解析参数（model非必选有默认值，max_tokens接收但忽略）
        $model  = isset($data['model']) && !empty($data['model']) ? strtolower(trim($data['model'])) : $this->defaultModel;
        $this->imageAiModel = $model;
        $stream = isset($data['stream']) ? filter_var($data['stream'], FILTER_VALIDATE_BOOLEAN) : false;
        $dryRun = isset($data['dry_run']) && filter_var($data['dry_run'], FILTER_VALIDATE_BOOLEAN);
        $dryRunProbe = isset($data['dry_run_probe'])
            && filter_var($data['dry_run_probe'], FILTER_VALIDATE_BOOLEAN);

        // 安全握手不需要提示词，也不调用类目模型或图片生成服务。
        // 批测脚本必须先确认服务器明确支持 dry-run，避免旧版本忽略参数后误生图。
        if ($dryRunProbe) {
            if (!$dryRun) {
                return $this->specResponse(
                    false,
                    '',
                    'dry_run_probe 必须与 dry_run=true 同时使用',
                    false,
                    [
                        'dry_run' => false,
                        'dry_run_supported' => true,
                        'generation_called' => false,
                    ]
                );
            }

            return $this->specResponse(
                true,
                '',
                'success',
                false,
                [
                    'dry_run' => true,
                    'dry_run_supported' => true,
                    'generation_called' => false,
                ]
            );
        }

        $activeConfigurationErrors = $dryRun
            ? $this->dryRunConfigurationErrors
            : $this->configurationErrors;
        if (!empty($activeConfigurationErrors)) {
            return $this->specResponse(
                false,
                '',
                $dryRun ? '类目判断服务配置不完整，请联系管理员' : '图片生成服务配置不完整，请联系管理员',
                $stream,
                $dryRun ? [
                    'dry_run' => true,
                    'generation_called' => false,
                    'would_generate_in_live_mode' => false,
                    'decision' => 'error',
                    'classification' => [
                        'enabled' => $this->classifyEnabled,
                        'available' => false,
                        'is_sensitive' => null,
                        'is_artwork' => null,
                        'is_restricted_domain' => null,
                        'reason' => 'configuration_error',
                    ],
                ] : null
            );
        }

        // 5. 从dialogue中提取最后一条用户消息作为prompt
        $prompt = $this->extractLastUserPrompt($data['dialogue'] ?? null);
        if ($prompt === null) {
            return $this->specResponse(false, '', '缺少dialogue参数或dialogue中不存在role为user的有效消息', $stream);
        }
        $this->imageAiPrompt = $prompt;

        // 6. 目前仅支持 jianyuan 模型
        if ($model !== 'jianyuan') {
            return $this->specResponse(false, '', '不支持的模型: ' . $model . '，当前支持的模型: jianyuan', $stream);
        }

        $aiCheck = [
            'available' => true,
            'sensitive' => false,
            'artwork' => true,
            'restricted_domain' => false,
            'reason' => $this->classifyEnabled ? '' : '类目判断功能已关闭',
        ];

        if ($this->classifyEnabled) {
            // AI 三维度判断；服务异常时 fail-close。
            try {
                $aiCheck = $this->checkPromptWithAi($prompt);
            } catch (\Throwable $e) {
                $aiCheck = [
                    'available' => false,
                    'sensitive' => true,
                    'artwork' => false,
                    'restricted_domain' => true,
                    'reason' => $e->getMessage(),
                ];
            }

        }

        $decision = 'allow';
        $decisionReason = 'success';
        if (!$aiCheck['available']) {
            $decision = 'error';
            $decisionReason = '内容类目判断服务暂不可用';
        } elseif ($aiCheck['sensitive']) {
            $decision = 'reject';
            $decisionReason = '输入内容包含敏感信息，拒绝服务';
        } elseif ($aiCheck['restricted_domain'] || !$aiCheck['artwork']) {
            $decision = 'reject';
            $decisionReason = '提示词不属于艺术品/工艺美术品/文创作品范畴，拒绝生成';
        }

        if ($dryRun) {
            return $this->specResponse(
                $decision === 'allow',
                '',
                $decisionReason,
                $stream,
                [
                    'dry_run' => true,
                    'generation_called' => false,
                    'would_generate_in_live_mode' => $decision === 'allow',
                    'decision' => $decision,
                    'classification' => [
                        'enabled' => $this->classifyEnabled,
                        'available' => (bool)$aiCheck['available'],
                        'is_sensitive' => (bool)$aiCheck['sensitive'],
                        'is_artwork' => (bool)$aiCheck['artwork'],
                        'is_restricted_domain' => (bool)$aiCheck['restricted_domain'],
                        'reason' => (string)$aiCheck['reason'],
                    ],
                ]
            );
        }

        if ($decision !== 'allow') {
            return $this->specResponse(false, '', $decisionReason, $stream);
        }

        if ($this->imageAiApiKey) {
            $billing = new BillingService();
            if (!$billing->reserve($this->imageAiApiKey['user_id'], $this->imageAiPointsPerImage)) {
                return $this->specResponse(false, '', '积分不足，请先充值', $stream);
            }
            $this->imageAiPointsReserved = $this->imageAiPointsPerImage;
        }

        // 7. 提交即梦4.0图片生成任务（固定生成1张）
        try {
            $submitResult = $this->jimengService->submitTask([
                'prompt'       => $prompt,
                'force_single' => true,
            ]);
        } catch (\Throwable $e) {
            return $this->specResponse(false, '', '图片生成任务提交异常: ' . $e->getMessage(), $stream);
        }

        if (empty($submitResult['success']) || empty($submitResult['data']['task_id'])) {
            $msg = $submitResult['msg'] ?? '未知错误';
            return $this->specResponse(false, '', '图片生成任务提交失败: ' . $msg, $stream);
        }

        $taskId = $submitResult['data']['task_id'];
        $this->imageAiTaskId = (string)$taskId;

        // 8. 轮询任务状态直到成功、失败或超时
        $imageUrl  = null;
        $failMsg   = null;
        $startTime = time();

        while ((time() - $startTime) < $this->maxWaitTime) {
            sleep($this->pollInterval);

            try {
                $queryResult = $this->jimengService->queryTask($taskId, ['return_url' => true]);
            } catch (\Throwable $e) {
                $failMsg = '查询任务状态异常: ' . $e->getMessage();
                break;
            }

            if (empty($queryResult['success'])) {
                $failMsg = '查询任务状态失败: ' . ($queryResult['msg'] ?? '未知错误');
                break;
            }

            $taskStatus = $queryResult['data']['status'] ?? '';

            if ($taskStatus === 'done') {
                $imageUrls = $queryResult['data']['image_urls'] ?? [];
                if (!empty($imageUrls) && !empty($imageUrls[0])) {
                    $imageUrl = $imageUrls[0];
                } else {
                    $failMsg = '任务已完成但未返回图片URL';
                }
                break;
            }

            if (in_array($taskStatus, ['not_found', 'expired', 'failed'], true)) {
                $failMsg = '图片生成失败，任务状态: ' . $taskStatus;
                break;
            }

            // in_queue / generating 等状态继续轮询
        }

        // 9. 超时判断
        if ($imageUrl === null && $failMsg === null) {
            return $this->specResponse(false, '', '生成超时', $stream);
        }

        if ($imageUrl === null) {
            return $this->specResponse(false, '', $failMsg, $stream);
        }

        // 10. 下载图片转存到本地public目录，保证URL长期稳定可用
        $saveResult = $this->saveImageToLocal($imageUrl);
        if (!$saveResult['success']) {
            return $this->specResponse(false, '', '图片转存失败: ' . $saveResult['error'], $stream);
        }
        $imageUrl = $saveResult['url'];
        $this->imageAiWidth = (int)($saveResult['width'] ?? 0);
        $this->imageAiHeight = (int)($saveResult['height'] ?? 0);
        $this->imageAiProduceId = (string)($saveResult['produce_id'] ?? '');

        // 11. 开启同步时，将已经添加文字水印和 AIGC metadata 的最终 PNG 上传又拍云。
        if ($this->SYNC_IMAGE_UPYUN && !$this->upyunService) {
            return $this->specResponse(false, '', '图片同步又拍云配置不完整', $stream);
        }
        if ($this->SYNC_IMAGE_UPYUN) {
            $upyunImageUrl = $this->upyunService->uploadFromUrl(
                $saveResult['path'],
                $this->upyunRemoteDir . date('Ymd') . '/'
            );
            if (!$upyunImageUrl) {
                return $this->specResponse(false, '', '图片同步又拍云失败', $stream);
            }
            $imageUrl = $upyunImageUrl;
        }

        // 12. 按规范返回结果（纯URL字符串）
        return $this->specResponse(true, $imageUrl, 'success', $stream);
    }
/**
     * AI双维度判断：敏感内容（从严）+ 文创类目（从宽）
     * fail-close：调用失败或结果无法解析时 available=false，由调用方拒绝服务
     *
     * @param string $prompt 用户提示词
     * @return array ['available' => bool, 'sensitive' => bool, 'artwork' => bool, 'reason' => string]
     */
 /**
 * AI三维度判断：
 * 1. 敏感内容（从严）
 * 2. 文创类目（从宽）
 * 3. 受限领域
 *
 * fail-close：
 * 调用失败或结果无法解析时 available=false，由调用方拒绝服务
 *
 * @param string $prompt 用户提示词
 * @return array
 */
    private function checkPromptWithAi($prompt)
    {
        $requestData = [
            'model' => $this->classifyModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->classifySystemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 200,
            'stream' => false,
        ];

        $ch = curl_init($this->classifyApiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->classifyApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $requestData,
                JSON_UNESCAPED_UNICODE
            ),
            CURLOPT_TIMEOUT => $this->classifyTimeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // 网络层失败 → fail-close
        if ($response === false || $curlError) {
            return [
                'available' => false,
                'sensitive' => true,
                'artwork' => false,
                'restricted_domain' => true,
                'reason' => '分类服务网络异常: ' . $curlError,
            ];
        }

        // DashScope 对敏感输入可能直接返回 400
        if ($httpCode === 400) {
            $errBody = json_decode($response, true);
            $errCode = $errBody['error']['code'] ?? '';

            if (
                stripos($errCode, 'data_inspection') !== false
                || stripos($errCode, 'inappropriate') !== false
            ) {
                return [
                    'available' => true,
                    'sensitive' => true,
                    'artwork' => false,
                    'restricted_domain' => false,
                    'reason' => '上游内容审查拒绝',
                ];
            }

            return [
                'available' => false,
                'sensitive' => true,
                'artwork' => false,
                'restricted_domain' => true,
                'reason' => '分类服务HTTP状态码: 400',
            ];
        }

        if ($httpCode !== 200) {
            return [
                'available' => false,
                'sensitive' => true,
                'artwork' => false,
                'restricted_domain' => true,
                'reason' => '分类服务HTTP状态码: ' . $httpCode,
            ];
        }

        $responseData = json_decode($response, true);
        $content = $responseData['choices'][0]['message']['content'] ?? '';

        if ($content === '') {
            return [
                'available' => false,
                'sensitive' => true,
                'artwork' => false,
                'restricted_domain' => true,
                'reason' => '分类服务返回内容为空',
            ];
        }

        // 容错提取JSON
        // 模型可能返回 ```json ... ``` 或附带其他文字
        $jsonStr = $content;

        if (preg_match('/\{.*\}/su', $content, $matches)) {
            $jsonStr = $matches[0];
        }

        $result = json_decode($jsonStr, true);

        // 三个字段必须全部存在
        if (
            json_last_error() !== JSON_ERROR_NONE
            || !isset($result['is_artwork'])
            || !isset($result['is_sensitive'])
            || !isset($result['is_restricted_domain'])
        ) {
            return [
                'available' => false,
                'sensitive' => true,
                'artwork' => false,
                'restricted_domain' => true,
                'reason' => '分类结果解析失败',
            ];
        }

        return [
            'available' => true,

            'sensitive' => filter_var(
                $result['is_sensitive'],
                FILTER_VALIDATE_BOOLEAN
            ),

            'artwork' => filter_var(
                $result['is_artwork'],
                FILTER_VALIDATE_BOOLEAN
            ),

            'restricted_domain' => filter_var(
                $result['is_restricted_domain'],
                FILTER_VALIDATE_BOOLEAN
            ),

            'reason' => '',
        ];
    }

/**
     * 下载远程图片保存到本地public目录，返回可长期访问的URL
     *
     * @param string $remoteUrl 远程图片URL（即梦原始地址，带过期签名）
     * @return array ['success' => bool, 'url' => string, 'error' => string]
     */
    private function saveImageToLocal($remoteUrl)
    {
        // 1. 下载图片二进制
        $ch = curl_init($remoteUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AigcImageSync/1.0)',
        ]);
        $imageData = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($imageData === false || $curlError) {
            return ['success' => false, 'url' => '', 'error' => '下载图片失败: ' . $curlError];
        }
        if ($httpCode !== 200 || strlen($imageData) === 0) {
            return ['success' => false, 'url' => '', 'error' => '下载图片失败, HTTP状态码: ' . $httpCode];
        }

        // 2. 从图片二进制真实检测格式，确定扩展名
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            return ['success' => false, 'url' => '', 'error' => '下载内容不是有效的图片'];
        }
        $extMap = [
            IMAGETYPE_JPEG => '.jpg',
            IMAGETYPE_PNG  => '.png',
            IMAGETYPE_WEBP => '.webp',
            IMAGETYPE_GIF  => '.gif',
            IMAGETYPE_BMP  => '.bmp',
        ];
        $ext = $extMap[$imageInfo[2]] ?? '.png';

        // 3. 先绘制显式标识，再写入隐式标识；后续图像重编码会清除 PNG 元数据。
        if ($imageInfo[2] !== IMAGETYPE_PNG) {
            return ['success' => false, 'url' => '', 'error' => 'AI 标识仅支持 PNG 图片'];
        }
        if ($this->enableVisibleWatermark) {
            $watermarkResult = $this->addVisibleAiWatermark($imageData);
            if (!$watermarkResult['success']) {
                return ['success' => false, 'url' => '', 'error' => $watermarkResult['error']];
            }
            $imageData = $watermarkResult['data'];
        }

        if ($this->enableAigcMetadata) {
            try {
                $produceId = '';
                $imageData = AigcPngMetadata::replace(
                    $imageData,
                    $this->aigcContentProducer,
                    $this->aigcContentPropagator,
                    $this->aigcXmpPrefix,
                    $this->aigcXmpNamespace,
                    $this->aigcLabel,
                    $this->aigcIdPrefix,
                    $produceId
                );
            } catch (\Throwable $e) {
                return ['success' => false, 'url' => '', 'error' => '写入 AIGC 隐式标识失败: ' . $e->getMessage()];
            }
        }

        // 3. 确定public物理路径
        if (defined('WEB_ROOT')) {
            $publicRoot = rtrim(WEB_ROOT, '/\\');
        } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $publicRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        } else {
            return ['success' => false, 'url' => '', 'error' => '无法确定public目录路径'];
        }

        // 4. 按日期分子目录，自动创建
        $dateDir = date('Ymd');
        $saveDir = $publicRoot . '/' . $this->localSaveRelDir . '/' . $dateDir;
        if (!is_dir($saveDir) && !@mkdir($saveDir, 0755, true)) {
            return ['success' => false, 'url' => '', 'error' => '创建保存目录失败: ' . $saveDir];
        }
        if (!is_writable($saveDir)) {
            return ['success' => false, 'url' => '', 'error' => '保存目录不可写: ' . $saveDir];
        }

        // 5. 生成唯一文件名并写入
        $filename = 'aigc_' . date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . $ext;
        $filePath = $saveDir . '/' . $filename;

        if (@file_put_contents($filePath, $imageData) === false) {
            return ['success' => false, 'url' => '', 'error' => '写入图片文件失败'];
        }

        // 6. 拼接可访问URL
        $url = rtrim($this->localUrlBase, '/') . '/' . $this->localSaveRelDir . '/' . $dateDir . '/' . $filename;

        return [
            'success' => true,
            'url' => $url,
            'path' => $filePath,
            'width' => (int)$imageInfo[0],
            'height' => (int)$imageInfo[1],
            'produce_id' => isset($produceId) ? $produceId : '',
            'error' => '',
        ];
    }

    /**
     * 在 PNG 右下角绘制清晰可辨的 AI 生成标识。
     * 字符实际高度至少为图片短边的 5%，尺寸或配置不合规时不发布图片。
     */
    private function addVisibleAiWatermark($imageData)
    {
        $label = trim($this->visibleWatermarkText);
        if ($label === '' || !preg_match('/(?:AI|人工智能)/iu', $label)
            || (strpos($label, '生成') === false && strpos($label, '合成') === false)) {
            return ['success' => false, 'data' => '', 'error' => '显式标识文字必须包含 AI/人工智能和生成/合成'];
        }
        $sizePercent = $this->visibleWatermarkSizePercent;
        $opacityPercent = $this->visibleWatermarkOpacityPercent;
        if (!is_numeric($sizePercent) || $sizePercent < 5 || $sizePercent > 20) {
            return ['success' => false, 'data' => '', 'error' => 'AI 标识大小须设置为 5～20'];
        }
        if (!is_numeric($opacityPercent) || $opacityPercent < 80 || $opacityPercent > 100) {
            return ['success' => false, 'data' => '', 'error' => 'AI 标识不透明度须设置为 80～100'];
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagettftext')
            || !function_exists('imagettfbbox') || !function_exists('imagepng')) {
            return ['success' => false, 'data' => '', 'error' => '服务器缺少 GD/FreeType 图片处理支持'];
        }

        $fontPath = __DIR__ . '/' . $this->visibleWatermarkFontFile;
        if (!is_readable($fontPath)) {
            return ['success' => false, 'data' => '', 'error' => '找不到 AI 标识字体文件'];
        }
        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            return ['success' => false, 'data' => '', 'error' => '无法解码 PNG 图片'];
        }
        if (!imageistruecolor($image) && !imagepalettetotruecolor($image)) {
            imagedestroy($image);
            return ['success' => false, 'data' => '', 'error' => '无法处理 PNG 调色板图片'];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $shortSide = min($width, $height);
        $requiredHeight = (int)ceil($shortSide * $sizePercent / 100);
        $fontSize = max(8, $requiredHeight);
        $box = false;
        $textHeight = 0;
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $label);
            if ($box === false) {
                break;
            }
            $textHeight = max($box[1], $box[3], $box[5], $box[7])
                - min($box[1], $box[3], $box[5], $box[7]);
            if ($textHeight >= $requiredHeight) {
                break;
            }
            $fontSize = max($fontSize + 1,
                (int)ceil($fontSize * $requiredHeight / max(1, $textHeight)));
        }
        if ($box === false || $textHeight < $requiredHeight) {
            imagedestroy($image);
            return ['success' => false, 'data' => '', 'error' => '无法计算符合尺寸要求的 AI 标识'];
        }

        $minX = min($box[0], $box[2], $box[4], $box[6]);
        $minY = min($box[1], $box[3], $box[5], $box[7]);
        $textWidth = max($box[0], $box[2], $box[4], $box[6]) - $minX;
        $paddingX = max(4, (int)ceil($shortSide * 0.018));
        $paddingY = max(3, (int)ceil($shortSide * 0.012));
        $margin = max(4, (int)ceil($shortSide * 0.02));
        $badgeWidth = $textWidth + 2 * $paddingX;
        $badgeHeight = $textHeight + 2 * $paddingY;
        if ($badgeWidth + 2 * $margin > $width || $badgeHeight + 2 * $margin > $height) {
            imagedestroy($image);
            return ['success' => false, 'data' => '', 'error' => 'AI 标识文字过长，无法在图片中完整显示'];
        }

        switch ($this->visibleWatermarkPosition) {
            case 'top-left':
                $x = $margin;
                $y = $margin;
                break;
            case 'top-right':
                $x = $width - $margin - $badgeWidth;
                $y = $margin;
                break;
            case 'bottom-left':
                $x = $margin;
                $y = $height - $margin - $badgeHeight;
                break;
            case 'bottom-right':
            default:
                $x = $width - $margin - $badgeWidth;
                $y = $height - $margin - $badgeHeight;
                break;
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);
        $alpha = (int)round(127 * (100 - $opacityPercent) / 100);
        $background = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
        $white = imagecolorallocatealpha($image, 255, 255, 255, $alpha);
        imagefilledrectangle($image, $x, $y, $x + $badgeWidth - 1, $y + $badgeHeight - 1, $background);
        $drawn = imagettftext($image, $fontSize, 0,
            $x + $paddingX - $minX, $y + $paddingY - $minY, $white, $fontPath, $label);
        if ($drawn === false) {
            imagedestroy($image);
            return ['success' => false, 'data' => '', 'error' => '绘制 AI 标识失败'];
        }

        ob_start();
        $encoded = imagepng($image, null, 6);
        $markedData = ob_get_clean();
        imagedestroy($image);
        if (!$encoded || !is_string($markedData) || $markedData === '') {
            return ['success' => false, 'data' => '', 'error' => '保存带 AI 标识的 PNG 失败'];
        }
        return ['success' => true, 'data' => $markedData, 'error' => ''];
    }

    /**
     * 从请求头中提取Bearer Token
     */
    private function getBearerToken()
    {
        $authHeader = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $authHeader = $value;
                    break;
                }
            }
        }

        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * 从dialogue中提取最后一条role=user的content
     */
    private function extractLastUserPrompt($dialogue)
    {
        if (!is_array($dialogue) || empty($dialogue)) {
            return null;
        }

        $prompt = null;
        foreach ($dialogue as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role    = isset($message['role']) ? strtolower(trim($message['role'])) : '';
            $content = isset($message['content']) ? trim($message['content']) : '';
            if ($role === 'user' && $content !== '') {
                $prompt = $content;
            }
        }
        return $prompt;
    }

    /**
     * 按规范输出响应
     * 不论成功失败，HTTP状态码均为200，最外层始终包含 content / choices / status / reason
     *
     * @param bool   $success 是否成功
     * @param string $result  图片URL（成功时）
     * @param string $reason  success或失败原因
     * @param bool   $stream  流式标识：true结果放choices，false结果放content
     * @param array|null $data 可选附加数据（dry-run 使用）
     */
    private function specResponse($success, $result, $reason, $stream, array $data = null)
    {
        if ($success) {
            $response = [
                'content' => $stream ? '' : $result,
                'choices' => $stream ? [['delta' => $result, 'finish_reason' => 'stop']] : '',
                'status'  => 'success',
                'reason'  => 'success',
            ];
        } else {
            $response = [
                'content' => '',
                'choices' => $stream ? [['delta' => '', 'finish_reason' => 'stop']] : '',
                'status'  => 'failed',
                'reason'  => $reason,
            ];
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($this->imageAiRequestId !== '') {
            $response['request_id'] = $this->imageAiRequestId;
        }

        if (!$this->imageAiResponseLogged && $this->imageAiRequestId !== '') {
            $this->imageAiResponseLogged = true;
            $status = $success ? 'success' : 'error';
            if (isset($data['decision']) && in_array($data['decision'], ['reject', 'error'], true)) {
                $status = $data['decision'];
            } elseif (!$success && (strpos($reason, '敏感') !== false || strpos($reason, '不属于') !== false)) {
                $status = 'reject';
            }

            if (!$success && $this->imageAiPointsReserved > 0 && $this->imageAiApiKey) {
                (new BillingService())->refund($this->imageAiApiKey['user_id'], $this->imageAiPointsReserved);
                $this->imageAiPointsReserved = 0;
            }

            try {
                $requestService = new RequestService();
                $requestService->log([
                    'request_id' => $this->imageAiRequestId,
                    'user_id' => $this->imageAiApiKey ? (int)$this->imageAiApiKey['user_id'] : 0,
                    'api_key_id' => $this->imageAiApiKey ? (int)$this->imageAiApiKey['id'] : 0,
                    'model' => $this->imageAiModel,
                    'status' => $status,
                    'reason' => $success ? 'success' : (string)$reason,
                    'prompt_digest' => $this->imageAiPrompt === '' ? '' : hash('sha256', $this->imageAiPrompt),
                    'image_url' => $success ? (string)$result : '',
                    'points' => $success ? $this->imageAiPointsReserved : 0,
                    'duration_ms' => $this->imageAiStartedAt > 0
                        ? (int)round((microtime(true) - $this->imageAiStartedAt) * 1000) : 0,
                    'client_ip' => get_client_ip(0, true),
                ]);
                if ($success && $result !== '' && $this->imageAiApiKey) {
                    $requestService->recordWork(
                        $this->imageAiApiKey['user_id'],
                        $this->imageAiRequestId,
                        $result,
                        $this->imageAiPrompt,
                        $this->imageAiModel,
                        [
                            'api_key_id' => (int)$this->imageAiApiKey['id'],
                            'task_id' => $this->imageAiTaskId,
                            'width' => $this->imageAiWidth,
                            'height' => $this->imageAiHeight,
                            'produce_id' => $this->imageAiProduceId,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                error_log('ImageAI request audit failed: ' . $e->getMessage());
            }
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    /**
     * 获取图片状态
     */
    public function getImageStatus()
    {
        if (!$this->api->validateMethod('POST')) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $data = $this->api->parseJson([
            'key' => ['required' => true, 'type' => 'string', 'maxLength' => 64]
        ]);

        if ($data === false) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $key = ApiHelper::filterAlphaNum($data['key']);
        if (empty($key)) {
            return ApiHelper::error('key参数格式不正确', 400);
        }

        $response = $this->wujieService->getImageStatus($key);

        if (!$response['success'] && $response['error']) {
            return ApiHelper::error('请求失败: ' . $response['error'], 500);
        }

        $responseData = json_decode(str_replace('\\', '', $response['data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiHelper::error('响应解析失败', 500);
        }

        // 处理图片URL同步到又拍云
        $pictureUrl = $responseData['data']['picture_url'] ?? null;
        if ($pictureUrl && $this->SYNC_IMAGE_UPYUN && $this->upyunService) {
            $upyunImageUrl = $this->upyunService->uploadFromUrl($pictureUrl);
            if ($upyunImageUrl) {
                $responseData['data']['picture_url'] = $upyunImageUrl;
            }
        }




        return json($responseData);
    }

    /**
     * 获取生成中信息
     */
    public function getGeneratingInfo()
    {
        if (!$this->api->validateMethod('POST')) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $data = $this->api->parseJson([
            'keys' => ['required' => true, 'type' => 'array'],
        ]);

        if ($data === false) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $keys = ApiHelper::filterStringArray($data['keys'], 64, 50);

        $response = $this->wujieService->getGeneratingInfo($keys);

        $responseData = json_decode($response['data'], true);

        // 处理图片URL同步到又拍云

/*        if (isset($responseData['data']['infos']) && is_array($responseData['data']['infos'])) {
            foreach ($responseData['data']['infos'] as &$info) {
                if (!empty($info['picture_url']) && $this->SYNC_IMAGE_UPYUN && $this->upyunService) {
                    $upyunImageUrl = $this->upyunService->uploadFromUrl($info['picture_url']);
                    if ($upyunImageUrl) {
                        $info['picture_url'] = $upyunImageUrl;
                    }
                }
            }
            unset($info);
        } */
        $responseData['code']  = 1;
        http_response_code($response['httpCode']);
        header('Content-Type: application/json');
        echo json_encode($responseData);
        exit;
    }

    /**
     * 加速任务
     */
    public function acclearate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Invalid request method.';
        }

        $rawData = file_get_contents('php://input');

        if (!json_decode($rawData)) {
            return 'Invalid JSON format.';
        }

        $data = json_decode($rawData, true);

        if (!isset($data['key'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing key parameter.']);
        }



        $response = $this->wujieService->accelerate($data);

        http_response_code($response['httpCode']);
        header('Content-Type: application/json');
        echo $response['data'];
        exit;
    }
   /**
     * 获取模型队列信息
     */
    public function getModelQueueInfo()
    {
        if (!$this->api->validateMethod('GET')) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $modelCode = $this->request->get('model');
        if (empty($modelCode)) {
            return ApiHelper::error('缺少model参数', 400);
        }

        $modelCode = ApiHelper::filterPositiveInt($modelCode, 0, 999999);
        if ($modelCode <= 0) {
            return ApiHelper::error('model参数格式不正确', 400);
        }

        $response = $this->wujieService->getModelQueueInfo($modelCode);

        if (!$response['success'] && $response['error']) {
            return ApiHelper::error('请求失败: ' . $response['error'], 500);
        }

        $responseData = json_decode($response['data'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiHelper::error('响应解析失败', 500);
        }

        return json($responseData);
    }

    // ==================== 即梦4.0接口 ====================

    /**
     * 即梦4.0 - 提交图片生成任务
     */
    public function jimengCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return json(['code' => 405, 'msg' => '请求方法不支持，请使用 POST 方法', 'data' => null]);
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            return json(['code' => 400, 'msg' => '无效的 JSON 格式', 'data' => null]);
        }

        if (!isset($data['prompt']) || empty(trim($data['prompt']))) {
            http_response_code(400);
            return json(['code' => 400, 'msg' => '缺少必填参数 prompt', 'data' => null]);
        }

        if (!cmf_is_user_login()) {
            http_response_code(401);
            return json(['code' => 401, 'msg' => '用户未登录，请先登录', 'data' => null]);
        }

        // 获取图片数量
        $picNum = max(1, intval($data['num'] ?? $data['batch_count'] ?? 1));

        // 检查积分
        $userid = cmf_get_current_user_id();
        $UserPointsModel = new UserPointsModel();
        $pointsCheck = $UserPointsModel->checkPointsEnough($userid, $picNum);

        if (!$pointsCheck['enough']) {
            http_response_code(400);
            return json([
                'code' => 400,
                'msg' => '积分不足，需要' . $pointsCheck['required'] . '积分，当前' . $pointsCheck['current'] . '积分',
                'data' => [
                    'required' => $pointsCheck['required'],
                    'current' => $pointsCheck['current']
                ]
            ]);
        }

        // 构建参数并调用服务
        $params = ['prompt' => trim($data['prompt'])];

        // 可选参数
        $optionalFields = ['image_urls', 'size', 'width', 'height', 'scale', 'force_single', 'min_ratio', 'max_ratio'];
        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $params[$field] = $data[$field];
            }
        }

        $result = $this->jimengService->submitTask($params);

        if (!$result['success']) {
            return json([
                'code' => $result['code'],
                'msg' => $result['msg'],
                'data' => null,
                'request_id' => $result['request_id'] ?? null
            ]);
        }

        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'task_id' => $result['data']['task_id'],
                'status' => $result['data']['status']
            ],
            'request_id' => $result['request_id'] ?? null
        ]);
    }

    /**
     * 即梦4.0 - 查询任务状态
     */
    public function jimengQuery()
    {
        if (!$this->api->validateMethod('POST')) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $data = $this->api->parseJson([
            'task_id' => ['required' => true, 'type' => 'string', 'maxLength' => 64],
            'return_url' => ['type' => 'boolean'],
            'add_logo' => ['type' => 'boolean'],
            'logo_position' => ['type' => 'integer'],
            'logo_language' => ['type' => 'integer'],
            'logo_opacity' => ['type' => 'float', 'min' => 0, 'max' => 1],
            'logo_text' => ['type' => 'string', 'maxLength' => 100],
        ]);

        if ($data === false) {
            return ApiHelper::returnError($this->api->getLastError());
        }

        $taskId = ApiHelper::filterAlphaNum($data['task_id']);
        if (empty($taskId)) {
            return ApiHelper::error('task_id参数格式不正确', 400);
        }

        // 构建查询选项
        $options = [];
        $optionFields = ['return_url', 'add_logo', 'logo_position', 'logo_language', 'logo_opacity', 'logo_text'];
        foreach ($optionFields as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }

        $result = $this->jimengService->queryTask($taskId, $options);

        if (!$result['success']) {
            return json([
                'code' => $result['code'],
                'msg' => $result['msg'],
                'data' => null
            ]);
        }

        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $result['data']
        ]);
    }
}
