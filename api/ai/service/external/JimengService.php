<?php
// +----------------------------------------------------------------------
// | 即梦4.0 图片生成服务
// +----------------------------------------------------------------------
namespace api\ai\service\external;

class JimengService
{
    private $volcSignService;
    private $apiVersion = '2022-08-31';
    private $reqKey = 'jimeng_t2i_v40';

    /**
     * 错误码映射
     */
    private static $errorMap = [
        50411 => '输入图片审核未通过，请更换图片',
        50511 => '生成的图片未通过审核，请重试或修改提示词',
        50412 => '输入文本前审核未通过，请修改提示词',
        50512 => '输出文本后审核未通过',
        50413 => '提示词包含敏感词或版权词，请修改',
        50518 => '输入图片版权审核未通过，请更换图片',
        50519 => '输出图片版权审核未通过，请重试',
        50520 => '审核服务异常，请稍后重试',
        50521 => '版权词服务异常，请稍后重试',
        50522 => '版权图服务异常，请稍后重试',
        50429 => '请求过于频繁，请稍后重试',
        50430 => '系统繁忙，请稍后重试',
        50500 => '服务器内部错误，请稍后重试',
        50501 => '算法处理错误，请稍后重试'
    ];

    /**
     * 状态码映射
     */
    private static $statusMap = [
        'in_queue' => 0,
        'generating' => 1,
        'done' => 2,
        'not_found' => -1,
        'expired' => -2
    ];

    public function __construct(VolcEngineSignService $volcSignService)
    {
        $this->volcSignService = $volcSignService;
    }

    /**
     * 提交图片生成任务（统一入口）
     *
     * @param array $params 参数数组
     *   - prompt: string 必填，提示词
     *   - image_urls: array 可选，参考图片URL数组
     *   - width: int 可选，宽度
     *   - height: int 可选，高度
     *   - scale: float 可选，缩放比例
     *   - force_single: bool 可选，强制单图
     *   - size: int 可选，尺寸
     *   - min_ratio: float 可选，最小比例
     *   - max_ratio: float 可选，最大比例
     * @return array
     */
    public function submitTask(array $params): array
    {
        // 构建payload
        $payload = $this->buildSubmitPayload($params);

        try {
            $response = $this->volcSignService->callApi(
                'CVSync2AsyncSubmitTask',
                $this->apiVersion,
                $payload
            );

            return $this->handleSubmitResponse($response);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 查询任务状态（统一入口）
     *
     * @param string $taskId 任务ID
     * @param array $options 可选参数
     *   - return_url: bool 是否返回URL
     *   - add_logo: bool 是否添加水印
     *   - logo_position: int 水印位置
     *   - logo_language: int 水印语言
     *   - logo_opacity: float 水印透明度
     *   - logo_text: string 水印文字
     * @return array
     */
    public function queryTask(string $taskId, array $options = []): array
    {
        $payload = $this->buildQueryPayload($taskId, $options);

        try {
            $response = $this->volcSignService->callApi(
                'CVSync2AsyncGetResult',
                $this->apiVersion,
                $payload
            );

            return $this->handleQueryResponse($response, $taskId);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 构建提交任务的payload
     */
    private function buildSubmitPayload(array $params): array
    {
        $payload = [
            'req_key' => $this->reqKey,
            'prompt' => trim($params['prompt']),
        ];

        // 可选参数定义：字段名 => 类型
        $optionalFields = [
            'image_urls' => 'array',
            'width' => 'int',
            'height' => 'int',
            'scale' => 'float',
            'force_single' => 'bool',
            'size' => 'int',
            'min_ratio' => 'float',
            'max_ratio' => 'float',
        ];

        foreach ($optionalFields as $field => $type) {
            if (isset($params[$field])) {
                $payload[$field] = $this->castValue($params[$field], $type);
            }
        }

        // 特殊处理：image_urls 限制最多10个
        if (isset($payload['image_urls']) && is_array($payload['image_urls'])) {
            $payload['image_urls'] = array_slice($payload['image_urls'], 0, 10);
        }

        return $payload;
    }

    /**
     * 构建查询任务的payload
     */
    private function buildQueryPayload(string $taskId, array $options): array
    {
        $reqJson = [
            'return_url' => $options['return_url'] ?? true,
            'logo_info' => [
                'add_logo' => $options['add_logo'] ?? false
            ]
        ];

        // 可选的logo参数
        $logoFields = ['logo_position', 'logo_language', 'logo_opacity', 'logo_text'];
        foreach ($logoFields as $field) {
            if (isset($options[$field])) {
                $reqJson['logo_info'][$field] = $options[$field];
            }
        }

        return [
            'req_key' => $this->reqKey,
            'task_id' => $taskId,
            'req_json' => json_encode($reqJson, JSON_UNESCAPED_UNICODE)
        ];
    }

    /**
     * 处理提交任务的响应
     */
    private function handleSubmitResponse(array $response): array
    {
        $data = $response['data'] ?? [];

        if (!$response['success']) {
            $errorCode = $data['code'] ?? 500;
            return [
                'success' => false,
                'code' => $errorCode,
                'msg' => $this->mapErrorCode($errorCode, $data['message'] ?? '未知错误'),
                'data' => null,
                'request_id' => $data['request_id'] ?? null
            ];
        }

        $taskId = $data['data']['task_id'] ?? null;
        if (!$taskId) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => '提交任务失败，未返回task_id',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'task_id' => $taskId,
                'status' => 'in_queue',
                'status_code' => 0
            ],
            'request_id' => $data['request_id'] ?? null
        ];
    }

    /**
     * 处理查询任务的响应
     */
    private function handleQueryResponse(array $response, string $taskId): array
    {
        $data = $response['data'] ?? [];

        if (!$response['success']) {
            $errorCode = $data['code'] ?? 500;
            return [
                'success' => false,
                'code' => $errorCode,
                'msg' => $this->mapErrorCode($errorCode, $data['message'] ?? '未知错误'),
                'data' => null
            ];
        }

        $status = $data['data']['status'] ?? 'unknown';
        $imageUrls = $data['data']['image_urls'] ?? [];
        $mappedStatus = self::$statusMap[$status] ?? 0;

        // 处理失败原因
        $failReason = null;
        if ($mappedStatus < 0) {
            $failReason = ($status === 'not_found') ? '任务未找到' : '任务已过期';
        }

        return [
            'success' => true,
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'task_id' => $taskId,
                'status' => $status,
                'status_code' => $mappedStatus,
                'image_url' => !empty($imageUrls) ? $imageUrls[0] : '',
                'image_urls' => $imageUrls,
                'fail_reason' => $failReason
            ]
        ];
    }

    /**
     * 类型转换
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                return boolval($value);
            case 'array':
                return is_array($value) ? $value : [$value];
            default:
                return $value;
        }
    }

    /**
     * 错误码映射
     */
    public function mapErrorCode(int $code, string $default = '未知错误'): string
    {
        return self::$errorMap[$code] ?? $default;
    }

    /**
     * 获取远程图片尺寸并调整到即梦允许范围
     *
     * @param string $url 图片URL
     * @return array|null 返回 ['width' => x, 'height' => y] 或 null
     */
    public function getAdjustedImageSize(string $url): ?array
    {
        try {
            $imageInfo = @getimagesize($url);
            if (!$imageInfo || !isset($imageInfo[0]) || !isset($imageInfo[1])) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // 检查宽高比（范围：1/3 到 3）
            $ratio = $width / $height;
            if ($ratio < 1/3) {
                $ratio = 1/3;
                $height = $width / $ratio;
            } elseif ($ratio > 3) {
                $ratio = 3;
                $height = $width / $ratio;
            }

            // 检查面积并调整（范围：1024*1024 到 4096*4096）
            $area = $width * $height;
            $minArea = 1024 * 1024;
            $maxArea = 4096 * 4096;

            if ($area < $minArea) {
                $scale = sqrt($minArea / $area);
                $width = intval($width * $scale);
                $height = intval($height * $scale);
            } elseif ($area > $maxArea) {
                $scale = sqrt($maxArea / $area);
                $width = intval($width * $scale);
                $height = intval($height * $scale);
            }

            // 确保宽高在有效范围内
            $width = max(1024, min(4096, intval($width)));
            $height = max(1024, min(4096, intval($height)));

            return [
                'width' => $width,
                'height' => $height
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
