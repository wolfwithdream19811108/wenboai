<?php
namespace api;

use app\user\model\UserPointsModel;

/**
 * API统一工具类
 * 整合：请求验证、输入过滤、HTTP请求、响应处理
 */
class ApiHelper
{
    // ==================== 单例模式 ====================

    private static $instance = null;
    private $lastError = null;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取最后一次错误
     * @return array|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    // ==================== 请求验证 ====================

    /**
     * 验证请求方法
     * @param string $method 期望的请求方法
     * @return bool
     */
    public function validateMethod($method = 'POST')
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->lastError = [
                'code' => 405,
                'msg' => '请求方法不支持，请使用 ' . $method . ' 方法',
                'data' => null
            ];
            return false;
        }
        return true;
    }

    /**
     * 解析并验证JSON输入
     * @param array $rules 验证规则
     * @return array|false 成功返回数据数组，失败返回false（错误信息通过getLastError获取）
     */
    public function parseJson(array $rules = [])
    {
        $rawData = file_get_contents('php://input');

        if (empty($rawData)) {
            $this->lastError = [
                'code' => 400,
                'msg' => '请求体不能为空',
                'data' => null
            ];
            return false;
        }

        $data = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = [
                'code' => 400,
                'msg' => '无效的JSON格式: ' . json_last_error_msg(),
                'data' => null
            ];
            return false;
        }

        if (!empty($rules) && !$this->validate($data, $rules)) {
            return false;
        }

        return $data;
    }

    /**
     * 验证数据字段
     * @param array $data 待验证数据
     * @param array $rules 验证规则
     * @return bool
     */
    public function validate(array $data, array $rules)
    {
        foreach ($rules as $field => $rule) {
            // 必填检查
            if (!empty($rule['required']) && (!isset($data[$field]) || $data[$field] === '')) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => $rule['message'] ?? "缺少必填参数: {$field}",
                    'data' => null
                ];
                return false;
            }

            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            // 类型检查
            if (isset($rule['type']) && !$this->checkType($value, $rule['type'])) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 类型错误，期望: {$rule['type']}",
                    'data' => null
                ];
                return false;
            }

            // 长度检查
            if (isset($rule['maxLength']) && is_string($value) && mb_strlen($value) > $rule['maxLength']) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 长度超过限制: {$rule['maxLength']}",
                    'data' => null
                ];
                return false;
            }

            if (isset($rule['minLength']) && is_string($value) && mb_strlen($value) < $rule['minLength']) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 长度不足: {$rule['minLength']}",
                    'data' => null
                ];
                return false;
            }

            // 数值范围检查
            if (isset($rule['min']) && is_numeric($value) && $value < $rule['min']) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 不能小于: {$rule['min']}",
                    'data' => null
                ];
                return false;
            }

            if (isset($rule['max']) && is_numeric($value) && $value > $rule['max']) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 不能大于: {$rule['max']}",
                    'data' => null
                ];
                return false;
            }

            // 枚举值检查
            if (isset($rule['enum']) && !in_array($value, $rule['enum'], true)) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 值不在允许范围内",
                    'data' => null
                ];
                return false;
            }

            // 正则表达式检查
            if (isset($rule['pattern']) && is_string($value) && !preg_match($rule['pattern'], $value)) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 格式不正确",
                    'data' => null
                ];
                return false;
            }

            // URL格式检查
            if (!empty($rule['isUrl']) && is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                $this->lastError = [
                    'code' => 400,
                    'msg' => "参数 {$field} 不是有效的URL",
                    'data' => null
                ];
                return false;
            }
        }

        return true;
    }

    /**
     * 检查值类型
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    private function checkType($value, $type)
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'numeric':
                return is_numeric($value);
            case 'float':
            case 'double':
                return is_float($value) || is_numeric($value);
            case 'bool':
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }

    // ==================== 用户验证 ====================

    /**
     * 检查用户登录状态
     * @return bool
     */
    public function checkLogin()
    {
        if (!cmf_is_user_login()) {
            $this->lastError = [
                'code' => 401,
                'msg' => '用户未登录，请先登录',
                'data' => null
            ];
            return false;
        }
        return true;
    }

    /**
     * 检查用户积分是否充足
     * @param int $picNum 需要的图片数量
     * @return bool
     */
    public function checkPoints($picNum = 1)
    {
        $userid = cmf_get_current_user_id();
        $UserPointsModel = new UserPointsModel();
        $pointsCheck = $UserPointsModel->checkPointsEnough($userid, $picNum);

        if (!$pointsCheck['enough']) {
            $this->lastError = [
                'code' => 400,
                'msg' => '积分不足',
                'data' => [
                    'message' => "积分不足，需要{$pointsCheck['required']}积分，当前{$pointsCheck['current']}积分",
                    'required' => $pointsCheck['required'],
                    'current' => $pointsCheck['current']
                ]
            ];
            return false;
        }
        return true;
    }

    /**
     * 组合检查：登录 + 积分
     * @param int $picNum
     * @return bool
     */
    public function checkLoginAndPoints($picNum = 1)
    {
        return $this->checkLogin() && $this->checkPoints($picNum);
    }

    // ==================== 输入过滤（防SQL注入/XSS） ====================

    /**
     * 过滤整数
     * @param mixed $value
     * @param int $default
     * @param int|null $min
     * @param int|null $max
     * @return int
     */
    public static function filterInt($value, $default = 0, $min = null, $max = null)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false) {
            return $default;
        }

        if ($min !== null && $value < $min) {
            return $min;
        }

        if ($max !== null && $value > $max) {
            return $max;
        }

        return $value;
    }

    /**
     * 过滤正整数
     * @param mixed $value
     * @param int $default
     * @param int $max
     * @return int
     */
    public static function filterPositiveInt($value, $default = 1, $max = PHP_INT_MAX)
    {
        return self::filterInt($value, $default, 1, $max);
    }

    /**
     * 过滤字符串
     * @param mixed $value
     * @param int $maxLength
     * @param string $default
     * @return string
     */
    public static function filterString($value, $maxLength = 255, $default = '')
    {
        if (!is_string($value)) {
            return $default;
        }

        // 移除不可见控制字符
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        return trim($value);
    }

    /**
     * 过滤HTML（防XSS）
     * @param string $value
     * @return string
     */
    public static function filterHtml($value)
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 过滤URL
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function filterUrl($value, $default = '')
    {
        if (!is_string($value)) {
            return $default;
        }

        $value = filter_var(trim($value), FILTER_VALIDATE_URL);

        return $value !== false ? $value : $default;
    }

    /**
     * 过滤分类名（字母、数字、下划线、中划线、中文）
     * @param mixed $value
     * @param string $default
     * @param int $maxLength
     * @return string
     */
    public static function filterCategory($value, $default = '', $maxLength = 50)
    {
        if (!is_string($value)) {
            return $default;
        }

        $value = preg_replace('/[^\w\-\x{4e00}-\x{9fa5}]/u', '', $value);

        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        return $value ?: $default;
    }

    /**
     * 过滤Key（只允许字母数字）
     * @param mixed $value
     * @param string $default
     * @param int $maxLength
     * @return string
     */
    public static function filterAlphaNum($value, $default = '', $maxLength = 64)
    {
        if (!is_string($value)) {
            return $default;
        }

        $value = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);

        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return $value ?: $default;
    }

    /**
     * 过滤字符串数组
     * @param mixed $value
     * @param int $maxLength 每个元素最大长度
     * @param int $maxCount 最大元素数量
     * @return array
     */
    public static function filterStringArray($value, $maxLength = 255, $maxCount = 100)
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        $count = 0;

        foreach ($value as $item) {
            if ($count >= $maxCount) {
                break;
            }

            if (is_string($item)) {
                $filtered = self::filterString($item, $maxLength);
                if (!empty($filtered)) {
                    $result[] = $filtered;
                    $count++;
                }
            }
        }

        return $result;
    }
    /**
     * 过滤浮点数
     */
    public static function filterFloat($value, $default = 0.0, $min = null, $max = null): float
    {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false) {
            return $default;
        }
        if ($min !== null && $value < $min) return $min;
        if ($max !== null && $value > $max) return $max;
        return $value;
    }

    /**
     * 验证数组参数
     */
    public static function filterArray($value, $maxItems = 100): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_slice($value, 0, $maxItems);
    }
    /**
     * 白名单过滤
     * @param mixed $value
     * @param array $whitelist
     * @param mixed $default
     * @return mixed
     */
    public static function filterWhitelist($value, array $whitelist, $default = null)
    {
        return in_array($value, $whitelist, true) ? $value : $default;
    }

    // ==================== HTTP请求 ====================

    /**
     * 发送GET请求
     * @param string $url
     * @param array $headers
     * @param array $options
     * @return array ['success' => bool, 'code' => int, 'data' => string, 'error' => string|null]
     */
    public static function httpGet($url, array $headers = [], array $options = [])
    {
        return self::httpRequest('GET', $url, null, $headers, $options);
    }

    /**
     * 发送POST请求
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return array
     */
    public static function httpPost($url, $data = null, array $headers = [], array $options = [])
    {
        return self::httpRequest('POST', $url, $data, $headers, $options);
    }

    /**
     * 发送JSON POST请求
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param array $options
     * @return array
     */
    public static function httpPostJson($url, array $data, array $headers = [], array $options = [])
    {
        $headers = array_merge(['Content-Type: application/json'], $headers);
        return self::httpRequest('POST', $url, json_encode($data, JSON_UNESCAPED_UNICODE), $headers, $options);
    }

    /**
     * 带认证的请求（适用于无界AI等）
     * @param string $url
     * @param string $method
     * @param mixed $data
     * @param string $auth
     * @return array
     */
    public static function httpWithAuth($url, $method, $data, $auth)
    {
        $headers = [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ];

        $postData = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;

        return self::httpRequest($method, $url, $postData, $headers);
    }

    /**
     * 通用HTTP请求方法
     * @param string $method
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return array
     */
    public static function httpRequest($method, $url, $data = null, array $headers = [], array $options = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => $options['timeout'] ?? 60,
            CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'] ?? 30,
            CURLOPT_SSL_VERIFYPEER => $options['ssl_verify'] ?? true,
            CURLOPT_SSL_VERIFYHOST => $options['ssl_verify'] ?? 2,
        ]);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch) ? curl_error($ch) : null;

        if ($error) {
            trace("[HttpRequest Error] {$method} {$url} - {$error}", 'error');
        }

        curl_close($ch);

        return [
            'success' => $error === null && $httpCode >= 200 && $httpCode < 300,
            'code' => $httpCode,
            'data' => $response,
            'error' => $error
        ];
    }

    // ==================== 响应处理 ====================

    /**
     * 返回成功响应
     * @param mixed $data
     * @param string $msg
     * @return \think\response\Json
     */
    public static function success($data = null, $msg = 'success')
    {
        return json([
            'code' => 200,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    /**
     * 返回错误响应
     * @param string $msg
     * @param int $code
     * @param mixed $data
     * @return \think\response\Json
     */
    public static function error($msg, $code = 400, $data = null)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    /**
     * 返回错误数组（设置HTTP状态码）
     * @param array $error ['code' => x, 'msg' => '', 'data' => null]
     * @return \think\response\Json
     */
    public static function returnError(array $error)
    {
        http_response_code($error['code']);
        return json($error);
    }
}
