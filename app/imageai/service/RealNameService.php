<?php

namespace app\imageai\service;

use think\Db;

class RealNameService
{
    const STATUS_UNVERIFIED = 'unverified';
    const STATUS_ADULT = 'verified_adult';
    const STATUS_MINOR = 'verified_minor';
    const STATUS_FAILED = 'failed';

    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new TencentRealNameClient();
    }

    public static function tablesReady()
    {
        try {
            Db::name('imageai_real_name_verification')->limit(1)->select();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function record($userId)
    {
        if (!$userId || !self::tablesReady()) {
            return null;
        }
        return Db::name('imageai_real_name_verification')->where('user_id', (int)$userId)->find();
    }

    public static function eligibility($userId)
    {
        $settings = RealNameConfig::settings();
        if (empty($settings['enabled'])) {
            return ['allowed' => true, 'code' => '', 'message' => ''];
        }
        if (!self::tablesReady()) {
            return ['allowed' => false, 'code' => 'REAL_NAME_TABLE_MISSING', 'message' => '实名认证服务尚未完成部署'];
        }

        $profile = AccessService::profile($userId);
        if ($profile && (string)$profile['account_mode'] === 'test' && empty($settings['require_test_accounts'])) {
            return ['allowed' => true, 'code' => '', 'message' => ''];
        }

        $record = self::record($userId);
        if (!$record || (string)$record['status'] === self::STATUS_UNVERIFIED) {
            return ['allowed' => false, 'code' => 'REAL_NAME_REQUIRED', 'message' => '请先完成实名认证'];
        }
        if ((string)$record['status'] === self::STATUS_MINOR) {
            if (!empty($record['adult_eligible_at']) && (int)$record['adult_eligible_at'] <= time()) {
                Db::name('imageai_real_name_verification')->where('user_id', (int)$userId)->update([
                    'status' => self::STATUS_ADULT,
                    'update_time' => time(),
                ]);
                return ['allowed' => true, 'code' => '', 'message' => ''];
            }
            return ['allowed' => false, 'code' => 'ADULT_REQUIRED', 'message' => '根据年龄保护要求，未满18周岁暂不能使用图片生成服务'];
        }
        if ((string)$record['status'] !== self::STATUS_ADULT) {
            return ['allowed' => false, 'code' => 'REAL_NAME_REQUIRED', 'message' => '实名认证尚未通过，请重新认证'];
        }

        return ['allowed' => true, 'code' => '', 'message' => ''];
    }

    public static function assertEligible($userId)
    {
        $eligibility = self::eligibility($userId);
        if (empty($eligibility['allowed'])) {
            throw new \RuntimeException($eligibility['message']);
        }
    }

    public function verify($userId, $name, $idCard, $consent)
    {
        $userId = (int)$userId;
        $name = trim((string)$name);
        $idCard = strtoupper(trim((string)$idCard));
        if (!$consent) {
            throw new \InvalidArgumentException('请阅读并同意实名认证信息处理说明');
        }
        if ($name === '' || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 30) {
            throw new \InvalidArgumentException('请输入正确的真实姓名');
        }

        $birthDate = self::birthDateFromIdCard($idCard);
        $settings = RealNameConfig::settings();
        $this->checkAttemptLimit($userId, (int)$settings['daily_attempt_limit']);
        $idHmac = $this->idCardHmac($idCard);
        if (!empty($settings['one_identity_one_account'])) {
            $boundUserId = (int)Db::name('imageai_real_name_verification')
                ->where('id_card_hmac', $idHmac)
                ->where('user_id', '<>', $userId)
                ->value('user_id');
            if ($boundUserId > 0) {
                throw new \RuntimeException('该身份信息已绑定其他账号');
            }
        }

        $this->incrementAttempt($userId);
        try {
            $result = $this->client->verify($name, $idCard, $settings);
        } catch (\Throwable $e) {
            $providerCode = method_exists($e, 'getErrorCode')
                ? trim((string)$e->getErrorCode())
                : '';
            $requestId = method_exists($e, 'getRequestId')
                ? trim((string)$e->getRequestId())
                : '';
            $errorMessage = $this->providerErrorMessage($providerCode);
            trace(sprintf(
                '[ImageAI RealName] provider=tencent code=%s request_id=%s exception=%s message=%s',
                $providerCode !== '' ? $providerCode : 'SERVICE_ERROR',
                $requestId !== '' ? $requestId : '-',
                get_class($e),
                str_replace(["\r", "\n"], ' ', $e->getMessage())
            ), 'error');
            $this->saveResult($userId, [
                'status' => self::STATUS_FAILED,
                'provider_request_id' => substr($requestId, 0, 64),
                'last_result_code' => substr($providerCode !== '' ? $providerCode : 'SERVICE_ERROR', 0, 64),
                'last_error_message' => $errorMessage,
            ]);
            throw new \RuntimeException($errorMessage);
        }

        $code = (string)$result['result'];
        if ($code !== '0') {
            $message = $this->resultMessage($code);
            $this->saveResult($userId, [
                'status' => self::STATUS_FAILED,
                'provider_request_id' => substr((string)$result['request_id'], 0, 64),
                'last_result_code' => substr($code, 0, 20),
                'last_error_message' => $message,
            ]);
            throw new \RuntimeException($message);
        }

        $eligibleAt = (new \DateTimeImmutable($birthDate, new \DateTimeZone('Asia/Shanghai')))
            ->modify('+' . (int)$settings['minimum_age'] . ' years')
            ->setTime(0, 0, 0);
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Shanghai'));
        $adult = $today >= $eligibleAt;
        $status = $adult ? self::STATUS_ADULT : self::STATUS_MINOR;
        $this->saveResult($userId, [
            'status' => $status,
            'provider_request_id' => substr((string)$result['request_id'], 0, 64),
            'id_card_hmac' => $idHmac,
            'id_card_last4' => substr($idCard, -4),
            'adult_eligible_at' => $eligibleAt->getTimestamp(),
            'verified_at' => time(),
            'last_result_code' => '0',
            'last_error_message' => '',
        ]);

        return [
            'status' => $status,
            'adult' => $adult,
            'eligible_at' => $eligibleAt->getTimestamp(),
        ];
    }

    public static function birthDateFromIdCard($idCard)
    {
        $idCard = strtoupper(trim((string)$idCard));
        if (!preg_match('/^\d{17}[0-9X]$/D', $idCard)) {
            throw new \InvalidArgumentException('身份证号码格式不正确');
        }
        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checks = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += ((int)$idCard[$i]) * $weights[$i];
        }
        if ($checks[$sum % 11] !== $idCard[17]) {
            throw new \InvalidArgumentException('身份证号码校验位不正确');
        }
        $birth = substr($idCard, 6, 8);
        $date = \DateTimeImmutable::createFromFormat('!Ymd', $birth, new \DateTimeZone('Asia/Shanghai'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))
            || $date->format('Ymd') !== $birth || $date > new \DateTimeImmutable('today', new \DateTimeZone('Asia/Shanghai'))) {
            throw new \InvalidArgumentException('身份证出生日期不正确');
        }
        return $date->format('Y-m-d');
    }

    private function idCardHmac($idCard)
    {
        $key = trim((string)config('database.authcode'));
        if ($key === '') {
            throw new \RuntimeException('系统身份摘要密钥未配置');
        }
        return hash_hmac('sha256', (string)$idCard, $key);
    }

    private function checkAttemptLimit($userId, $limit)
    {
        $limit = max(1, min(20, $limit));
        $count = (int)cache('imageai_real_name_attempt_' . (int)$userId . '_' . date('Ymd'));
        if ($count >= $limit) {
            throw new \RuntimeException('今日实名认证次数已达上限，请明日再试');
        }
    }

    private function incrementAttempt($userId)
    {
        $key = 'imageai_real_name_attempt_' . (int)$userId . '_' . date('Ymd');
        cache($key, (int)cache($key) + 1, 90000);
    }

    private function saveResult($userId, array $data)
    {
        $now = time();
        $existing = Db::name('imageai_real_name_verification')->where('user_id', (int)$userId)->find();
        $data['provider'] = 'tencent';
        $data['last_attempt_at'] = $now;
        $data['update_time'] = $now;
        if ($existing) {
            Db::name('imageai_real_name_verification')->where('user_id', (int)$userId)->update($data);
            return;
        }
        $data['user_id'] = (int)$userId;
        $data['create_time'] = $now;
        Db::name('imageai_real_name_verification')->insert($data);
    }

    private function resultMessage($code)
    {
        $messages = [
            '-1' => '姓名与身份证号码不一致',
            '-2' => '身份证号码不合法',
            '-3' => '姓名格式不合法',
            '-4' => '权威认证服务暂时异常，请稍后重试',
            '-5' => '权威认证库中未找到该身份证记录',
            '-6' => '权威认证系统升级中，请稍后重试',
            '-7' => '实名认证次数已超过腾讯云当日限制',
        ];
        return isset($messages[$code]) ? $messages[$code] : '实名认证未通过，请核对信息后重试';
    }

    private function providerErrorMessage($code)
    {
        $messages = [
            'UnauthorizedOperation.Nonactivated' => '腾讯云人脸核身服务尚未开通，请先在腾讯云控制台开通服务',
            'UnauthorizedOperation.Activating' => '腾讯云人脸核身服务正在开通中，请稍后再试',
            'UnauthorizedOperation.ActivateError' => '腾讯云人脸核身服务开通异常，请联系腾讯云处理',
            'UnauthorizedOperation.NonAuthorize' => '腾讯云账号尚未完成实名认证，暂不能调用人脸核身服务',
            'UnauthorizedOperation.Arrears' => '腾讯云账号已欠费，暂不能调用人脸核身服务',
            'AuthFailure.InvalidAuthorization' => '腾讯云 FaceID 密钥无效或没有接口权限',
            'AuthFailure.SignatureFailure' => '腾讯云 FaceID 请求签名验证失败，请检查 SecretId 和 SecretKey',
            'RequestLimitExceeded' => '腾讯云实名认证请求过于频繁，请稍后再试',
        ];
        if (isset($messages[$code])) {
            return $messages[$code];
        }
        return $code !== ''
            ? '腾讯云实名认证服务调用失败（错误码：' . $code . '）'
            : '实名认证服务暂不可用，请稍后重试';
    }
}
