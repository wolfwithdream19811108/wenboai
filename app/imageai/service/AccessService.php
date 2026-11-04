<?php

namespace app\imageai\service;

use think\Db;

class AccessService
{
    const SETTINGS_OPTION = 'imageai_access_settings';
    const ACCOUNT_MODE_NORMAL = 'normal';
    const ACCOUNT_MODE_API_ONLY = 'api_only';

    public static function settings()
    {
        $settings = cmf_get_option(self::SETTINGS_OPTION);
        if (!is_array($settings)) {
            $settings = [];
        }
        $mode = isset($settings['registration_account_mode'])
            ? (string)$settings['registration_account_mode']
            : self::ACCOUNT_MODE_NORMAL;
        if (!in_array($mode, [self::ACCOUNT_MODE_NORMAL, self::ACCOUNT_MODE_API_ONLY], true)) {
            $mode = self::ACCOUNT_MODE_NORMAL;
        }
        return ['registration_account_mode' => $mode];
    }

    public static function registrationAccountMode()
    {
        return self::settings()['registration_account_mode'];
    }

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
        return self::accountMode($userId) === self::ACCOUNT_MODE_API_ONLY;
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
