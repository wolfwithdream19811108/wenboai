<?php

namespace app\imageai\service;

use think\Db;

class TestAccountService
{
    const MAX_BATCH_SIZE = 500;

    public static function tablesReady()
    {
        try {
            Db::name('imageai_test_account')->limit(1)->select();
            Db::name('imageai_test_account_batch')->limit(1)->select();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function createBatch(array $data, $adminId)
    {
        $prefix = trim((string)($data['prefix'] ?? ''));
        $startText = trim((string)($data['start'] ?? ''));
        $count = (int)($data['count'] ?? 0);
        $password = (string)($data['password'] ?? '');
        $points = max(0, (int)($data['points'] ?? 0));
        $expiresAt = max(0, (int)($data['expires_at'] ?? 0));
        $status = empty($data['status']) ? 0 : 1;

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{1,20}$/D', $prefix)) {
            throw new \InvalidArgumentException('账号前缀须以字母开头，只能包含字母、数字和下划线，长度为2至21位');
        }
        if (!preg_match('/^\d{1,10}$/D', $startText)) {
            throw new \InvalidArgumentException('起始编号必须是1至10位数字');
        }
        if ($count < 1 || $count > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException('单批创建数量须为1至' . self::MAX_BATCH_SIZE);
        }
        $this->assertPassword($password);
        if ($points > 10000000) {
            throw new \InvalidArgumentException('初始积分不能超过10000000');
        }

        $width = strlen($startText);
        $start = (int)$startText;
        $usernames = [];
        for ($i = 0; $i < $count; $i++) {
            $username = $prefix . str_pad((string)($start + $i), $width, '0', STR_PAD_LEFT);
            $this->assertUsername($username);
            $usernames[] = $username;
        }
        $existing = Db::name('user')->whereIn('user_login', $usernames)->column('user_login');
        if (!empty($existing)) {
            throw new \RuntimeException('以下账号已经存在：' . implode('、', array_slice($existing, 0, 8)));
        }

        $batchCode = 'TA' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
        $now = time();
        $credentials = [];

        Db::startTrans();
        try {
            $batchId = Db::name('imageai_test_account_batch')->insertGetId([
                'batch_code' => $batchCode,
                'account_prefix' => $prefix,
                'start_number' => $startText,
                'account_count' => $count,
                'initial_points' => $points,
                'expires_at' => $expiresAt,
                'status' => $status,
                'created_by' => (int)$adminId,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            foreach ($usernames as $username) {
                $userId = $this->insertUser($username, $password, $points, $status, $now);
                $this->insertProfile($userId, $now);
                Db::name('imageai_test_account')->insert([
                    'user_id' => $userId,
                    'batch_id' => $batchId,
                    'expires_at' => $expiresAt,
                    'status' => $status,
                    'created_by' => (int)$adminId,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                $credentials[] = ['username' => $username, 'password' => $password];
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return ['batch_id' => $batchId, 'batch_code' => $batchCode, 'credentials' => $credentials];
    }

    public function createOne(array $data, $adminId)
    {
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $points = max(0, (int)($data['points'] ?? 0));
        $expiresAt = max(0, (int)($data['expires_at'] ?? 0));
        $status = empty($data['status']) ? 0 : 1;
        $this->assertUsername($username);
        $this->assertPassword($password);
        if ($points > 10000000) {
            throw new \InvalidArgumentException('初始积分不能超过10000000');
        }
        if (Db::name('user')->where('user_login', $username)->count()) {
            throw new \RuntimeException('测试账号已经存在');
        }

        $batchCode = 'SINGLE' . date('YmdHis') . strtoupper(bin2hex(random_bytes(2)));
        $now = time();
        Db::startTrans();
        try {
            $batchId = Db::name('imageai_test_account_batch')->insertGetId([
                'batch_code' => $batchCode,
                'account_prefix' => $username,
                'start_number' => '',
                'account_count' => 1,
                'initial_points' => $points,
                'expires_at' => $expiresAt,
                'status' => $status,
                'created_by' => (int)$adminId,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $userId = $this->insertUser($username, $password, $points, $status, $now);
            $this->insertProfile($userId, $now);
            Db::name('imageai_test_account')->insert([
                'user_id' => $userId,
                'batch_id' => $batchId,
                'expires_at' => $expiresAt,
                'status' => $status,
                'created_by' => (int)$adminId,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return ['batch_id' => $batchId, 'batch_code' => $batchCode, 'credentials' => [
            ['username' => $username, 'password' => $password],
        ]];
    }

    public function operate(array $userIds, $operation, array $data = [])
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            throw new \InvalidArgumentException('请选择测试账号');
        }
        $testUserIds = Db::name('imageai_test_account')->whereIn('user_id', $userIds)->column('user_id');
        if (count($testUserIds) !== count($userIds)) {
            throw new \InvalidArgumentException('选择中包含非测试账号');
        }

        $now = time();
        switch ((string)$operation) {
            case 'enable':
            case 'disable':
                $status = $operation === 'enable' ? 1 : 0;
                Db::name('imageai_test_account')->whereIn('user_id', $userIds)
                    ->update(['status' => $status, 'update_time' => $now]);
                return;
            case 'reset_password':
                $password = (string)($data['password'] ?? '');
                $this->assertPassword($password);
                Db::name('user')->whereIn('id', $userIds)->update(['user_pass' => cmf_password($password)]);
                return;
            case 'set_points':
                $points = (int)($data['points'] ?? -1);
                if ($points < 0 || $points > 10000000) {
                    throw new \InvalidArgumentException('积分须在0至10000000之间');
                }
                foreach ($userIds as $userId) {
                    $this->setPoints($userId, $points);
                }
                return;
            case 'add_points':
                $points = (int)($data['points'] ?? 0);
                if ($points < -10000000 || $points > 10000000 || $points === 0) {
                    throw new \InvalidArgumentException('积分调整值须为非零整数，且绝对值不超过10000000');
                }
                foreach ($userIds as $userId) {
                    $current = (int)Db::name('user_points')->where('userid', $userId)->value('points');
                    $this->setPoints($userId, max(0, $current + $points));
                }
                return;
            case 'set_expiry':
                $expiresAt = max(0, (int)($data['expires_at'] ?? 0));
                Db::name('imageai_test_account')->whereIn('user_id', $userIds)
                    ->update(['expires_at' => $expiresAt, 'update_time' => $now]);
                return;
            default:
                throw new \InvalidArgumentException('不支持的批量操作');
        }
    }

    public static function login($username, $password)
    {
        $row = Db::name('imageai_test_account')->alias('t')
            ->join('__USER__ u', 'u.id=t.user_id')
            ->where('u.user_login', (string)$username)
            ->field('t.status AS test_status,t.expires_at,u.*')
            ->find();
        if (!$row || !cmf_compare_password((string)$password, (string)$row['user_pass'])) {
            return ['ok' => false, 'message' => '测试账号或密码错误'];
        }
        if ((int)$row['user_status'] !== 1 || (int)$row['test_status'] !== 1) {
            return ['ok' => false, 'message' => '测试账号已停用'];
        }
        if (!empty($row['expires_at']) && (int)$row['expires_at'] < time()) {
            return ['ok' => false, 'message' => '测试账号已过期'];
        }

        unset($row['test_status'], $row['expires_at']);
        session('user', $row);
        Db::name('user')->where('id', $row['id'])->update([
            'last_login_time' => time(),
            'last_login_ip' => get_client_ip(0, true),
        ]);
        $token = cmf_generate_user_token($row['id'], 'web');
        if (!empty($token)) {
            session('token', $token);
        }
        return ['ok' => true, 'user_id' => (int)$row['id']];
    }

    public static function isUsable($userId)
    {
        $account = Db::name('imageai_test_account')->where('user_id', (int)$userId)->find();
        if (!$account) {
            return false;
        }
        return (int)$account['status'] === 1
            && (empty($account['expires_at']) || (int)$account['expires_at'] >= time());
    }

    public static function isTestAccount($userId)
    {
        return Db::name('imageai_test_account')->where('user_id', (int)$userId)->count() > 0;
    }

    private function insertUser($username, $password, $points, $status, $now)
    {
        $userId = Db::name('user')->insertGetId([
            'user_type' => 2,
            'user_status' => 1,
            'mobile' => '',
            'user_pass' => cmf_password($password),
            'user_login' => $username,
            'user_email' => '',
            'user_nickname' => $username,
            'last_login_ip' => '',
            'create_time' => $now,
            'last_login_time' => 0,
        ]);
        Db::name('user_points')->insert(['userid' => $userId, 'points' => $points]);
        return $userId;
    }

    private function insertProfile($userId, $now)
    {
        Db::name('imageai_user_profile')->insert([
            'user_id' => (int)$userId,
            'account_mode' => 'test',
            'register_source' => 'admin_test',
            'status' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    private function setPoints($userId, $points)
    {
        if (Db::name('user_points')->where('userid', (int)$userId)->count()) {
            Db::name('user_points')->where('userid', (int)$userId)->update(['points' => (int)$points]);
        } else {
            Db::name('user_points')->insert(['userid' => (int)$userId, 'points' => (int)$points]);
        }
    }

    private function assertUsername($username)
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{3,31}$/D', (string)$username)) {
            throw new \InvalidArgumentException('测试账号须以字母开头，只能包含字母、数字和下划线，长度为4至32位');
        }
    }

    private function assertPassword($password)
    {
        $length = strlen((string)$password);
        if ($length < 8 || $length > 32) {
            throw new \InvalidArgumentException('密码长度须为8至32位');
        }
    }
}
