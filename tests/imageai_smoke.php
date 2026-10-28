<?php

define('APP_DEBUG', false);
define('CMF_ROOT', dirname(__DIR__) . '/');
define('APP_PATH', CMF_ROOT . 'app/');
define('CMF_PATH', CMF_ROOT . 'simplewind/cmf/');
define('WEB_ROOT', CMF_ROOT . 'public/');
define('PLUGINS_PATH', WEB_ROOT . 'plugins/');
define('EXTEND_PATH', CMF_ROOT . 'simplewind/extend/');
define('VENDOR_PATH', CMF_ROOT . 'simplewind/vendor/');
define('RUNTIME_PATH', CMF_ROOT . 'data/runtime/');

require CMF_ROOT . 'simplewind/thinkphp/base.php';
\think\App::initCommon();

use app\imageai\service\ApiKeyService;
use app\imageai\service\BillingService;
use app\imageai\service\RequestService;
use think\Db;

$userId = 990000 + random_int(1, 9000);
$now = time();

try {
    Db::name('user')->insert([
        'id' => $userId,
        'user_type' => 2,
        'user_status' => 1,
        'mobile' => '139' . str_pad((string)($userId % 100000000), 8, '0', STR_PAD_LEFT),
        'user_pass' => 'smoke-test-only',
        'create_time' => $now,
    ]);
    Db::name('imageai_user_profile')->insert([
        'user_id' => $userId,
        'account_mode' => 'api_only',
        'register_source' => 'smoke_test',
        'status' => 1,
        'create_time' => $now,
        'update_time' => $now,
    ]);
    Db::name('user_points')->insert(['userid' => $userId, 'points' => 100]);

    $keys = new ApiKeyService();
    $created = $keys->create($userId, 'smoke');
    if (strpos($created['key'], 'wb_sk_') !== 0 || !$keys->authenticate($created['key'], '127.0.0.1')) {
        throw new RuntimeException('API Key create/authenticate failed');
    }
    if (Db::name('imageai_api_key')->where('id', $created['id'])->value('key_hash') === $created['key']) {
        throw new RuntimeException('API Key was stored in plaintext');
    }

    $billing = new BillingService();
    if (!$billing->reserve($userId, 50)) {
        throw new RuntimeException('Point reservation failed');
    }
    $billing->refund($userId, 50);
    if ((int)Db::name('user_points')->where('userid', $userId)->value('points') !== 100) {
        throw new RuntimeException('Point refund failed');
    }

    $keys->revoke($userId, $created['id']);
    if ($keys->authenticate($created['key'], '127.0.0.1')) {
        throw new RuntimeException('Revoked API Key was accepted');
    }
    if (!$keys->checkRateLimit($created['id'], 1) || $keys->checkRateLimit($created['id'], 1)) {
        throw new RuntimeException('API Key rate limit failed');
    }

    $requestId = RequestService::newId();
    $workId = (new RequestService())->recordWork(
        $userId,
        $requestId,
        'https://example.test/image.png',
        'smoke prompt',
        'jianyuan',
        ['api_key_id' => $created['id'], 'task_id' => 'task-smoke', 'width' => 1024, 'height' => 1024, 'produce_id' => 'wb-smoke']
    );
    if (!$workId || !Db::name('imageai_work_detail')->where('request_id', $requestId)->find()) {
        throw new RuntimeException('Work/detail recording failed');
    }
    echo "ImageAI smoke test: OK\n";
} finally {
    $workIds = Db::name('imageai_work_detail')->where('user_id', $userId)->column('work_id');
    Db::name('imageai_work_detail')->where('user_id', $userId)->delete();
    if ($workIds) {
        Db::name('ai_user_works')->where('id', 'in', $workIds)->delete();
    }
    Db::name('imageai_api_key')->where('user_id', $userId)->delete();
    Db::name('user_points')->where('userid', $userId)->delete();
    Db::name('imageai_user_profile')->where('user_id', $userId)->delete();
    Db::name('user')->where('id', $userId)->delete();
}
