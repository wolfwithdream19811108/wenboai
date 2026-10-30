<?php

namespace app\imageai\service;

use app\ai\service\ImageGenerationConfig;

class RealNameConfig
{
    const SETTINGS_OPTION = 'imageai_real_name_settings';

    public static function defaults()
    {
        return [
            'enabled' => 1,
            'minimum_age' => 18,
            'require_test_accounts' => 1,
            'one_identity_one_account' => 1,
            'daily_attempt_limit' => 5,
            'timeout' => 10,
        ];
    }

    public static function settings()
    {
        $stored = cmf_get_option(self::SETTINGS_OPTION);
        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function credentials()
    {
        $keys = ImageGenerationConfig::keys();
        return [
            'secret_id' => trim((string)($keys['tencent_faceid_secret_id'] ?? '')),
            'secret_key' => trim((string)($keys['tencent_faceid_secret_key'] ?? '')),
        ];
    }
}
