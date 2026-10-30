<?php

namespace app\imageai\service;

use think\Db;

class AccessService
{
    public static function ensureProfile($userId)
    {
        $profile = self::profile($userId);
        if ($profile) {
            return $profile;
        }
        $now = time();
        Db::name('imageai_user_profile')->insert([
            'user_id' => (int)$userId,
            'account_mode' => 'normal',
            'register_source' => 'existing_user',
            'status' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        return self::profile($userId);
    }

    public static function profile($userId)
    {
        if (!$userId) {
            return null;
        }
        return Db::name('imageai_user_profile')->where('user_id', (int)$userId)->find();
    }

    public static function accountMode($userId)
    {
        $profile = self::profile($userId);
        return $profile ? (string)$profile['account_mode'] : 'normal';
    }

    public static function isApiOnly($userId)
    {
        return self::accountMode($userId) === 'api_only';
    }

    public static function isEnabled($userId)
    {
        if ((int)Db::name('user')->where('id', (int)$userId)->value('user_status') !== 1) {
            return false;
        }
        $profile = self::profile($userId);
        if ($profile && (int)$profile['status'] !== 1) {
            return false;
        }
        if ($profile && (string)$profile['account_mode'] === 'test') {
            return TestAccountService::isUsable($userId);
        }
        return true;
    }

    public static function generationEligibility($userId)
    {
        if (!self::isEnabled($userId)) {
            return ['allowed' => false, 'code' => 'ACCOUNT_DISABLED', 'message' => '账号已被停用或已过期'];
        }
        return RealNameService::eligibility($userId);
    }

    public static function assertGenerationEligible($userId)
    {
        $eligibility = self::generationEligibility($userId);
        if (empty($eligibility['allowed'])) {
            throw new \RuntimeException($eligibility['message']);
        }
    }
}
