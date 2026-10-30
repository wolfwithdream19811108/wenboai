<?php

namespace app\imageai\service;

use api\ai\service\storage\UpyunService;
use app\ai\service\ImageGenerationConfig;
use think\Db;

class FailureLogService
{
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';

    public function record(array $data)
    {
        $now = time();
        $context = $this->sanitizeContext($data['context'] ?? []);
        $row = [
            'request_id' => substr((string)($data['request_id'] ?? ''), 0, 32),
            'user_id' => max(0, (int)($data['user_id'] ?? 0)),
            'api_key_id' => max(0, (int)($data['api_key_id'] ?? 0)),
            'stage' => substr((string)($data['stage'] ?? 'unknown'), 0, 30),
            'error_code' => substr((string)($data['error_code'] ?? 'UNKNOWN_ERROR'), 0, 64),
            'error_message' => $this->sanitizeText((string)($data['error_message'] ?? '未知错误'), 1000),
            'http_status' => max(0, (int)($data['http_status'] ?? 0)),
            'prompt_digest' => substr((string)($data['prompt_digest'] ?? ''), 0, 64),
            'local_image_url' => substr((string)($data['local_image_url'] ?? ''), 0, 500),
            'local_file_path' => substr((string)($data['local_file_path'] ?? ''), 0, 1000),
            'remote_path' => substr((string)($data['remote_path'] ?? ''), 0, 500),
            'remote_image_url' => substr((string)($data['remote_image_url'] ?? ''), 0, 500),
            'context_json' => $context === [] ? '' : json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            ),
            'retry_count' => max(0, (int)($data['retry_count'] ?? 0)),
            'status' => in_array(($data['status'] ?? ''), [self::STATUS_PENDING, self::STATUS_RESOLVED, self::STATUS_IGNORED], true)
                ? $data['status'] : self::STATUS_PENDING,
            'resolved_at' => max(0, (int)($data['resolved_at'] ?? 0)),
            'client_ip' => substr((string)($data['client_ip'] ?? ''), 0, 45),
            'create_time' => $now,
            'update_time' => $now,
        ];

        try {
            $id = (int)Db::name('imageai_failure_log')->insertGetId($row);
            $this->pruneOldOncePerDay();
            return $id;
        } catch (\Throwable $e) {
            $this->writeRuntimeFallback($row, $e->getMessage());
            return 0;
        }
    }

    public function retryUpyun($id)
    {
        $log = Db::name('imageai_failure_log')->where('id', (int)$id)->find();
        if (!$log || (string)$log['stage'] !== 'upyun') {
            throw new \RuntimeException('未找到又拍云失败记录');
        }
        if ((string)$log['status'] === self::STATUS_RESOLVED) {
            throw new \RuntimeException('该记录已经同步成功');
        }

        $result = $this->performUpyunRetry($log);
        $now = time();
        if (empty($result['success'])) {
            $context = json_decode((string)$log['context_json'], true);
            if (!is_array($context)) {
                $context = [];
            }
            $context['last_retry'] = $result['context'] ?? [];
            Db::name('imageai_failure_log')->where('id', (int)$id)->update([
                'error_code' => substr((string)($result['error_code'] ?? 'UPYUN_RETRY_FAILED'), 0, 64),
                'error_message' => $this->sanitizeText((string)($result['error_message'] ?? '又拍云重试失败'), 1000),
                'http_status' => max(0, (int)($result['http_status'] ?? 0)),
                'context_json' => json_encode(
                    $this->sanitizeContext($context),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                ),
                'retry_count' => (int)$log['retry_count'] + 1,
                'update_time' => $now,
            ]);
            return $result;
        }

        $remoteUrl = (string)$result['url'];
        Db::startTrans();
        try {
            Db::name('imageai_failure_log')->where('id', (int)$id)->update([
                'status' => self::STATUS_RESOLVED,
                'remote_image_url' => $remoteUrl,
                'error_code' => 'RESOLVED',
                'error_message' => '又拍云重新同步成功',
                'http_status' => (int)($result['http_status'] ?? 200),
                'retry_count' => (int)$log['retry_count'] + 1,
                'resolved_at' => $now,
                'update_time' => $now,
            ]);
            if (!empty($log['request_id'])) {
                Db::name('ai_user_works')->where('key', $log['request_id'])->update([
                    'picture_url' => $remoteUrl,
                    'update_timestamp' => $now,
                ]);
                Db::name('imageai_work_detail')->where('request_id', $log['request_id'])->update([
                    'image_url' => $remoteUrl,
                ]);
                Db::name('imageai_api_request_log')->where('request_id', $log['request_id'])->update([
                    'image_url' => $remoteUrl,
                ]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $result;
    }

    public function pruneOld($days = 90)
    {
        $days = max(1, (int)$days);
        try {
            return Db::name('imageai_failure_log')->where('create_time', '<', time() - $days * 86400)->delete();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function pruneOldOncePerDay()
    {
        $cacheKey = 'imageai_failure_log_pruned_' . date('Ymd');
        try {
            if (!cache($cacheKey)) {
                $this->pruneOld(90);
                cache($cacheKey, 1, 90000);
            }
        } catch (\Throwable $e) {
            // 日志清理失败不能影响主请求。
        }
    }

    private function performUpyunRetry(array $log)
    {
        $localPath = (string)$log['local_file_path'];
        if ($localPath === '' || !is_file($localPath) || !is_readable($localPath)) {
            return [
                'success' => false,
                'error_code' => 'LOCAL_FILE_UNAVAILABLE',
                'error_message' => '服务器本地图片不存在或不可读',
                'http_status' => 0,
                'context' => [],
            ];
        }

        $settings = ImageGenerationConfig::settings();
        $keys = ImageGenerationConfig::keys();
        foreach (['upyun_service', 'upyun_operator', 'upyun_password'] as $name) {
            if (empty($keys[$name])) {
                return [
                    'success' => false,
                    'error_code' => 'UPYUN_CONFIG_INCOMPLETE',
                    'error_message' => '又拍云配置不完整',
                    'http_status' => 0,
                    'context' => [],
                ];
            }
        }
        $domain = trim((string)($settings['storage']['upyun_domain'] ?? ''));
        if ($domain === '') {
            return [
                'success' => false,
                'error_code' => 'UPYUN_CONFIG_INCOMPLETE',
                'error_message' => '又拍云访问域名未配置',
                'http_status' => 0,
                'context' => [],
            ];
        }

        $service = new UpyunService(
            (string)$keys['upyun_service'],
            (string)$keys['upyun_operator'],
            (string)$keys['upyun_password'],
            $domain
        );
        return $service->uploadFile($localPath, (string)$log['remote_path']);
    }

    private function sanitizeContext($value)
    {
        if (!is_array($value)) {
            return ['detail' => $this->sanitizeText((string)$value, 2000)];
        }
        $clean = [];
        foreach ($value as $key => $item) {
            $keyText = (string)$key;
            if (preg_match('/password|secret|authorization|api[_-]?key|access[_-]?key|token|prompt/i', $keyText)) {
                $clean[$keyText] = '[REDACTED]';
            } elseif (is_array($item)) {
                $clean[$keyText] = $this->sanitizeContext($item);
            } elseif (is_scalar($item) || $item === null) {
                $clean[$keyText] = $this->sanitizeText((string)$item, 2000);
            }
        }
        return $clean;
    }

    private function sanitizeText($value, $limit)
    {
        $value = (string)$value;
        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        $value = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/-]+/i', 'Bearer [REDACTED]', $value);
        $value = preg_replace('/("?(?:password|secret|api[_-]?key|access[_-]?key|token)"?\s*[:=]\s*)[^\s,;}]+/i', '$1[REDACTED]', $value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, (int)$limit, 'UTF-8');
        }
        return substr($value, 0, (int)$limit);
    }

    private function writeRuntimeFallback(array $row, $databaseError)
    {
        $row['database_error'] = $this->sanitizeText((string)$databaseError, 500);
        $line = json_encode(
            $row,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ) . PHP_EOL;
        $directory = defined('RUNTIME_PATH') ? rtrim(RUNTIME_PATH, '/\\') . '/log/imageai' : '';
        if ($directory !== '' && (is_dir($directory) || @mkdir($directory, 0750, true))) {
            if (@file_put_contents($directory . '/' . date('Ymd') . '.log', $line, FILE_APPEND | LOCK_EX) !== false) {
                return;
            }
        }
        error_log('ImageAI failure log fallback: ' . $line);
    }
}
