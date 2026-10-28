<?php

namespace app\imageai\service;

use think\Db;

class ApiKeyService
{
    const MAX_ACTIVE_KEYS = 5;

    public function create($userId, $name = '', $expiresInDays = 0)
    {
        $activeCount = Db::name('imageai_api_key')
            ->where('user_id', (int)$userId)
            ->where('status', 1)
            ->count();
        if ($activeCount >= self::MAX_ACTIVE_KEYS) {
            throw new \RuntimeException('每个用户最多保留5个有效 API Key');
        }

        $plain = 'wb_sk_' . bin2hex(random_bytes(24));
        $now = time();
        $expiresInDays = max(0, (int)$expiresInDays);
        $id = Db::name('imageai_api_key')->insertGetId([
            'user_id' => (int)$userId,
            'name' => trim((string)$name) ?: '默认密钥',
            'key_hash' => hash('sha256', $plain),
            'key_prefix' => substr($plain, 0, 12),
            'key_suffix' => substr($plain, -6),
            'status' => 1,
            'expires_at' => $expiresInDays > 0 ? $now + $expiresInDays * 86400 : null,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        return ['id' => $id, 'key' => $plain];
    }

    public function authenticate($plain, $clientIp = '')
    {
        $plain = trim((string)$plain);
        if ($plain === '') {
            return null;
        }

        $key = Db::name('imageai_api_key')
            ->where('key_hash', hash('sha256', $plain))
            ->where('status', 1)
            ->find();
        if (!$key || (!empty($key['expires_at']) && (int)$key['expires_at'] < time())) {
            return null;
        }
        if (!AccessService::isEnabled($key['user_id'])) {
            return null;
        }

        Db::name('imageai_api_key')->where('id', $key['id'])->update([
            'last_used_at' => time(),
            'last_used_ip' => (string)$clientIp,
        ]);
        return $key;
    }

    public function revoke($userId, $keyId)
    {
        return Db::name('imageai_api_key')
            ->where(['id' => (int)$keyId, 'user_id' => (int)$userId])
            ->update(['status' => 0, 'update_time' => time()]);
    }

    public function checkRateLimit($keyId, $limit)
    {
        $cacheKey = 'imageai_rate_' . (int)$keyId . '_' . date('YmdHi');
        $count = (int)cache($cacheKey);
        if ($count >= (int)$limit) {
            return false;
        }
        cache($cacheKey, $count + 1, 70);
        return true;
    }
}
