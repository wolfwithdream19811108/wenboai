<?php

namespace app\imageai\service;

use think\Db;

class RequestService
{
    public static function newId()
    {
        return bin2hex(random_bytes(16));
    }

    public function log(array $data)
    {
        $defaults = [
            'request_id' => self::newId(),
            'user_id' => 0,
            'api_key_id' => 0,
            'model' => '',
            'status' => 'error',
            'reason' => '',
            'prompt_digest' => '',
            'image_url' => '',
            'points' => 0,
            'duration_ms' => 0,
            'client_ip' => '',
            'create_time' => time(),
        ];
        return Db::name('imageai_api_request_log')->insert(array_merge($defaults, $data));
    }

    public function recordWork($userId, $requestId, $imageUrl, $prompt, $model, array $detail = [])
    {
        $user = Db::name('user')->where('id', (int)$userId)->find();
        $now = time();
        $workId = Db::name('ai_user_works')->insertGetId([
            'uid' => (int)$userId,
            'key' => (string)$requestId,
            'create_timestamp' => $now,
            'user_name' => (string)($user['user_nickname'] ?: $user['mobile']),
            'create_date' => date('Y-m-d H:i:s', $now),
            'picture_url' => (string)$imageUrl,
            'prompt' => (string)$prompt,
            'model_code' => $model === 'jianyuan' ? 1 : 0,
            'width' => (int)($detail['width'] ?? 0),
            'height' => (int)($detail['height'] ?? 0),
            'update_timestamp' => $now,
        ]);
        Db::name('imageai_work_detail')->insert([
            'work_id' => $workId,
            'request_id' => (string)$requestId,
            'user_id' => (int)$userId,
            'api_key_id' => (int)($detail['api_key_id'] ?? 0),
            'source' => 'api',
            'model' => (string)$model,
            'prompt_digest' => hash('sha256', (string)$prompt),
            'upstream_task_id' => (string)($detail['task_id'] ?? ''),
            'image_url' => (string)$imageUrl,
            'width' => (int)($detail['width'] ?? 0),
            'height' => (int)($detail['height'] ?? 0),
            'produce_id' => (string)($detail['produce_id'] ?? ''),
            'create_time' => $now,
        ]);
        return $workId;
    }
}
