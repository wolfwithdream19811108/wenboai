<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\RouteModel;
use app\ai\service\ImageGenerationConfig;
use app\imageai\service\TestAccountService;
use cmf\controller\AdminBaseController;

use think\Db;

/**
 * Class SettingController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   =>'设置',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'cogs',
 *     'remark' =>'系统设置入口'
 * )
 */
class SettingController extends AdminBaseController
{

    /**
     * 网站信息
     * @adminMenu(
     *     'name'   => '网站信息',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '网站信息',
     *     'param'  => ''
     * )
     */
    public function site()
    {
        $content = hook_one('admin_setting_site_view');

        if (!empty($content)) {
            return $content;
        }

        $noNeedDirs     = [".", "..", ".svn", 'fonts'];
        $adminThemesDir = config('template.cmf_admin_theme_path') . config('template.cmf_admin_default_theme') . '/public/assets/themes/';
        $adminStyles    = cmf_scan_dir($adminThemesDir . '*', GLOB_ONLYDIR);
        $adminStyles    = array_diff($adminStyles, $noNeedDirs);
        $cdnSettings    = cmf_get_option('cdn_settings');
        $cmfSettings    = cmf_get_option('cmf_settings');
        $adminSettings  = cmf_get_option('admin_settings');
        $functionSettings  = cmf_get_option('function_settings');
        $functionKeys = ImageGenerationConfig::keys();
        $imageGenerationSettings = ImageGenerationConfig::settings();

        $this->assign('site_info', cmf_get_option('site_info'));
        $this->assign("admin_styles", $adminStyles);
        $this->assign("templates", []);
        $this->assign("cdn_settings", $cdnSettings);
        $this->assign("admin_settings", $adminSettings);
        $this->assign("function_settings", $functionSettings);
        $this->assign('function_key_status', ImageGenerationConfig::keyStatus($functionKeys));
        $this->assign('image_generation_settings', $imageGenerationSettings);
        $this->assign("cmf_settings", $cmfSettings);
        $this->assignTestAccounts();

        return $this->fetch();
    }

    /**
     * 网站信息设置提交
     * @adminMenu(
     *     'name'   => '网站信息设置提交',
     *     'parent' => 'site',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '网站信息设置提交',
     *     'param'  => ''
     * )
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $testAccountAction = trim((string)$this->request->post('test_account_action', ''));
            if ($testAccountAction !== '') {
                return $this->handleTestAccountAction($testAccountAction);
            }
            $result = $this->validate($this->request->param(), 'SettingSite');
            if ($result !== true) {
                $this->error($result);
            }

            $currentFunctionKeys = ImageGenerationConfig::keys();
            $submittedFunctionKeys = $this->request->param('function_keys/a');
            $clearFunctionKeys = $this->request->param('function_keys_clear/a');
            $functionKeys = $this->mergeImageFunctionKeys(
                $currentFunctionKeys,
                is_array($submittedFunctionKeys) ? $submittedFunctionKeys : [],
                is_array($clearFunctionKeys) ? $clearFunctionKeys : []
            );

            $submittedImageSettings = $this->request->param('image_generation_settings/a');
            $imageGenerationSettings = is_array($submittedImageSettings)
                ? $this->normalizeImageGenerationSettings($submittedImageSettings, $functionKeys)
                : ImageGenerationConfig::settings();

            $options = $this->request->param('options/a');
            cmf_set_option('site_info', $options);

            $cmfSettings = $this->request->param('cmf_settings/a');

            $bannedUsernames                 = preg_replace("/[^0-9A-Za-z_\\x{4e00}-\\x{9fa5}-]/u", ",", $cmfSettings['banned_usernames']);
            $cmfSettings['banned_usernames'] = $bannedUsernames;
            cmf_set_option('cmf_settings', $cmfSettings);

            $cdnSettings = $this->request->param('cdn_settings/a');
            cmf_set_option('cdn_settings', $cdnSettings);

            $funSettings = $this->request->param('function_settings/a');
            $funSettings = [
                'analysis_object' => (int)$funSettings['analysis_object'],
                'open_AI3D' => (int)$funSettings['open_AI3D'],
                'need_login' => (int)$funSettings['need_login']
            ];
            cmf_set_option('function_settings', $funSettings);
            cmf_set_option(ImageGenerationConfig::KEYS_OPTION, $functionKeys, true);
            cmf_set_option(ImageGenerationConfig::SETTINGS_OPTION, $imageGenerationSettings, true);

            $adminSettings = $this->request->param('admin_settings/a');

            $routeModel = new RouteModel();
            if (!empty($adminSettings['admin_password'])) {
                $routeModel->setRoute($adminSettings['admin_password'] . '$', 'admin/Index/index', [], 2, 5000);
            } else {
                $routeModel->deleteRoute('admin/Index/index', []);
            }

            $routeModel->getRoutes(true);
            cmf_set_option('admin_settings', $adminSettings);

            $this->success("保存成功！", '');

        }
    }

    private function assignTestAccounts()
    {
        $ready = TestAccountService::tablesReady();
        $this->assign('test_account_tables_ready', $ready);
        $this->assign('test_accounts', []);
        $this->assign('test_account_page', '');
        $this->assign('test_account_batches', []);
        if (!$ready) {
            return;
        }

        $keyword = trim((string)$this->request->param('test_keyword', ''));
        $query = Db::name('imageai_test_account')->alias('t')
            ->join('__USER__ u', 'u.id=t.user_id')
            ->join('__IMAGEAI_TEST_ACCOUNT_BATCH__ b', 'b.id=t.batch_id', 'LEFT')
            ->join('__USER_POINTS__ p', 'p.userid=t.user_id', 'LEFT')
            ->field('t.*,u.user_login,u.user_nickname,u.last_login_time,b.batch_code,p.points');
        if ($keyword !== '') {
            $query->whereLike('u.user_login|b.batch_code', '%' . $keyword . '%');
        }
        $paginator = $query->order('t.create_time DESC')->paginate(30, false, [
            'query' => $this->request->param(),
        ]);
        $accounts = $paginator->items();
        $userIds = array_column($accounts, 'user_id');
        $workCounts = [];
        if (!empty($userIds)) {
            $workRows = Db::name('ai_user_works')->whereIn('uid', $userIds)
                ->where('picture_url', '<>', '')
                ->field('uid,COUNT(*) AS work_count')->group('uid')->select();
            foreach ($workRows as $row) {
                $workCounts[(int)$row['uid']] = (int)$row['work_count'];
            }
        }
        foreach ($accounts as &$account) {
            $account['work_count'] = $workCounts[(int)$account['user_id']] ?? 0;
            $account['is_active'] = (int)$account['status'] === 1
                && (empty($account['expires_at']) || (int)$account['expires_at'] >= time());
            $account['is_expired'] = !empty($account['expires_at']) && (int)$account['expires_at'] < time();
        }
        unset($account);

        $this->assign('test_accounts', $accounts);
        $this->assign('test_account_page', $paginator->render());
        $this->assign('test_account_batches', Db::name('imageai_test_account_batch')
            ->order('id DESC')->limit(20)->select());
    }

    private function handleTestAccountAction($action)
    {
        if (!TestAccountService::tablesReady()) {
            return json(['code' => 0, 'msg' => '测试账号数据表尚未安装，请先执行数据库迁移']);
        }
        $service = new TestAccountService();
        try {
            if ($action === 'batch_create') {
                $result = $service->createBatch([
                    'prefix' => $this->request->post('prefix'),
                    'start' => $this->request->post('start'),
                    'count' => $this->request->post('count'),
                    'password' => $this->request->post('password'),
                    'points' => $this->request->post('points'),
                    'expires_at' => $this->parseTestAccountExpiry($this->request->post('expires_on')),
                    'status' => $this->request->post('status'),
                ], cmf_get_current_admin_id());
                return json(['code' => 1, 'msg' => '批量测试账号创建成功', 'data' => $result]);
            }
            if ($action === 'single_create') {
                $result = $service->createOne([
                    'username' => $this->request->post('username'),
                    'password' => $this->request->post('password'),
                    'points' => $this->request->post('points'),
                    'expires_at' => $this->parseTestAccountExpiry($this->request->post('expires_on')),
                    'status' => $this->request->post('status'),
                ], cmf_get_current_admin_id());
                return json(['code' => 1, 'msg' => '测试账号创建成功', 'data' => $result]);
            }
            if ($action === 'operate') {
                $ids = $this->request->post('user_ids/a');
                if (!is_array($ids)) {
                    $ids = preg_split('/\s*,\s*/', trim((string)$this->request->post('user_ids', '')));
                }
                $operation = trim((string)$this->request->post('operation'));
                $service->operate($ids, $operation, [
                    'password' => $this->request->post('password'),
                    'points' => $this->request->post('points'),
                    'expires_at' => $this->parseTestAccountExpiry($this->request->post('expires_on')),
                ]);
                return json(['code' => 1, 'msg' => '测试账号操作成功']);
            }
            throw new \InvalidArgumentException('不支持的测试账号操作');
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    private function parseTestAccountExpiry($date)
    {
        $date = trim((string)$date);
        if ($date === '') {
            return 0;
        }
        $timestamp = strtotime($date . ' 23:59:59');
        if (!$timestamp) {
            throw new \InvalidArgumentException('有效期格式不正确');
        }
        return $timestamp;
    }

    private function mergeImageFunctionKeys(array $current, array $submitted, array $clear)
    {
        $scalarKeys = [
            'volc_access_key_id',
            'volc_secret_access_key',
            'image_classify_api_key',
            'upyun_service',
            'upyun_operator',
            'upyun_password',
            'wujie_appid',
            'wujie_signkey',
            'yidun_secret_id',
            'yidun_secret_key',
            'yidun_business_id',
        ];
        foreach ($scalarKeys as $name) {
            if (!empty($clear[$name])) {
                unset($current[$name]);
                continue;
            }
            if (isset($submitted[$name]) && trim((string)$submitted[$name]) !== '') {
                $current[$name] = trim((string)$submitted[$name]);
            }
        }

        if (!empty($clear['image_test_api_keys'])) {
            unset($current['image_test_api_keys']);
        } elseif (isset($submitted['image_test_api_keys']) && trim((string)$submitted['image_test_api_keys']) !== '') {
            $keys = preg_split('/[\r\n,]+/', (string)$submitted['image_test_api_keys']);
            $keys = array_values(array_unique(array_filter(array_map('trim', $keys))));
            if (empty($keys)) {
                $this->error('测试图片接口 Bearer Key 格式无效');
            }
            $current['image_test_api_keys'] = $keys;
        }

        return $current;
    }

    private function normalizeImageGenerationSettings(array $input, array $functionKeys)
    {
        $defaults = ImageGenerationConfig::defaults();
        $classifier = isset($input['classifier']) && is_array($input['classifier'])
            ? $input['classifier'] : [];
        $watermark = isset($input['watermark']) && is_array($input['watermark'])
            ? $input['watermark'] : [];
        $metadata = isset($input['metadata']) && is_array($input['metadata'])
            ? $input['metadata'] : [];
        $storage = isset($input['storage']) && is_array($input['storage'])
            ? $input['storage'] : [];
        $endpoint = isset($input['endpoint']) && is_array($input['endpoint'])
            ? $input['endpoint'] : [];

        $settings = [
            'classifier' => [
                'enabled' => empty($classifier['enabled']) ? 0 : 1,
                'api_url' => trim((string)($classifier['api_url'] ?? '')),
                'model' => trim((string)($classifier['model'] ?? '')),
                'timeout' => (int)($classifier['timeout'] ?? $defaults['classifier']['timeout']),
                'system_prompt' => trim((string)($classifier['system_prompt'] ?? '')),
                'fail_closed' => 1,
            ],
            'watermark' => [
                'enabled' => empty($watermark['enabled']) ? 0 : 1,
                'text' => trim((string)($watermark['text'] ?? '')),
                'size_percent' => (int)($watermark['size_percent'] ?? $defaults['watermark']['size_percent']),
                'opacity_percent' => (int)($watermark['opacity_percent'] ?? $defaults['watermark']['opacity_percent']),
                'position' => trim((string)($watermark['position'] ?? $defaults['watermark']['position'])),
            ],
            'metadata' => [
                'enabled' => empty($metadata['enabled']) ? 0 : 1,
                'label' => trim((string)($metadata['label'] ?? '')),
                'content_producer' => trim((string)($metadata['content_producer'] ?? '')),
                'content_propagator' => trim((string)($metadata['content_propagator'] ?? '')),
                'xmp_prefix' => trim((string)($metadata['xmp_prefix'] ?? '')),
                'xmp_namespace' => trim((string)($metadata['xmp_namespace'] ?? '')),
                'id_prefix' => trim((string)($metadata['id_prefix'] ?? '')),
            ],
            'storage' => [
                'sync_upyun' => empty($storage['sync_upyun']) ? 0 : 1,
                'upyun_domain' => rtrim(trim((string)($storage['upyun_domain'] ?? '')), '/'),
                'upyun_remote_dir' => '/' . trim((string)($storage['upyun_remote_dir'] ?? ''), '/') . '/',
                'local_save_rel_dir' => trim((string)($storage['local_save_rel_dir'] ?? ''), '/\\'),
                'local_url_base' => rtrim(trim((string)($storage['local_url_base'] ?? '')), '/'),
            ],
            'endpoint' => [
                'default_model' => strtolower(trim((string)($endpoint['default_model'] ?? ''))),
                'api_points' => (int)($endpoint['api_points'] ?? $defaults['endpoint']['api_points']),
                'rate_limit_per_minute' => (int)($endpoint['rate_limit_per_minute'] ?? $defaults['endpoint']['rate_limit_per_minute']),
                'poll_interval' => (int)($endpoint['poll_interval'] ?? $defaults['endpoint']['poll_interval']),
                'max_wait_time' => (int)($endpoint['max_wait_time'] ?? $defaults['endpoint']['max_wait_time']),
            ],
        ];

        if ($settings['classifier']['enabled']) {
            if (!filter_var($settings['classifier']['api_url'], FILTER_VALIDATE_URL)
                || stripos($settings['classifier']['api_url'], 'https://') !== 0) {
                $this->error('类目判断接口地址必须是有效的 HTTPS 地址');
            }
            if ($settings['classifier']['model'] === '' || $settings['classifier']['system_prompt'] === '') {
                $this->error('启用类目判断时，模型和系统提示词不能为空');
            }
            if (empty($functionKeys['image_classify_api_key'])) {
                $this->error('启用类目判断时，必须配置类目判断 API Key');
            }
        }
        if ($settings['classifier']['timeout'] < 1 || $settings['classifier']['timeout'] > 120) {
            $this->error('类目判断超时时间必须在 1～120 秒之间');
        }
        if (empty($functionKeys['volc_access_key_id']) || empty($functionKeys['volc_secret_access_key'])) {
            $this->error('火山引擎 AccessKey ID 和 Secret AccessKey 不能为空');
        }
        if (empty($functionKeys['image_test_api_keys']) || !is_array($functionKeys['image_test_api_keys'])) {
            $this->error('测试图片接口 Bearer Key 至少需要配置一个');
        }

        if ($settings['watermark']['enabled']) {
            $text = $settings['watermark']['text'];
            if ($text === '' || !preg_match('/(?:AI|人工智能)/iu', $text)
                || (strpos($text, '生成') === false && strpos($text, '合成') === false)) {
                $this->error('文字水印必须包含 AI/人工智能和生成/合成');
            }
        }
        if ($settings['watermark']['size_percent'] < 5 || $settings['watermark']['size_percent'] > 20) {
            $this->error('文字水印大小必须在 5～20 之间');
        }
        if ($settings['watermark']['opacity_percent'] < 80 || $settings['watermark']['opacity_percent'] > 100) {
            $this->error('文字水印不透明度必须在 80～100 之间');
        }
        if (!in_array($settings['watermark']['position'], ['top-left', 'top-right', 'bottom-left', 'bottom-right'], true)) {
            $this->error('文字水印位置无效');
        }

        if ($settings['metadata']['enabled']) {
            if ($settings['metadata']['label'] === '' || $settings['metadata']['content_producer'] === ''
                || $settings['metadata']['content_propagator'] === '') {
                $this->error('启用 Metadata 时，Label、内容生产者和传播者不能为空');
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $settings['metadata']['xmp_prefix'])) {
                $this->error('Metadata XMP 前缀格式无效');
            }
            if (!filter_var($settings['metadata']['xmp_namespace'], FILTER_VALIDATE_URL)) {
                $this->error('Metadata Namespace 必须是有效 URL');
            }
            if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/D', $settings['metadata']['id_prefix'])) {
                $this->error('Metadata ID 前缀只能包含字母、数字、下划线和中划线，最长 32 位');
            }
        }

        if ($settings['storage']['sync_upyun']) {
            foreach (['upyun_service', 'upyun_operator', 'upyun_password'] as $name) {
                if (empty($functionKeys[$name])) {
                    $this->error('启用又拍云同步时，必须完整配置又拍云凭据');
                }
            }
            if (!filter_var($settings['storage']['upyun_domain'], FILTER_VALIDATE_URL)
                || stripos($settings['storage']['upyun_domain'], 'https://') !== 0) {
                $this->error('又拍云访问域名必须是有效的 HTTPS 地址');
            }
        }
        foreach (['local_save_rel_dir', 'upyun_remote_dir'] as $name) {
            if (strpos($settings['storage'][$name], '..') !== false
                || !preg_match('#^/?[A-Za-z0-9/_-]+/?$#D', $settings['storage'][$name])) {
                $this->error('图片保存目录格式无效');
            }
        }
        if (!filter_var($settings['storage']['local_url_base'], FILTER_VALIDATE_URL)) {
            $this->error('本地图片访问域名格式无效');
        }
        if ($settings['endpoint']['default_model'] === '') {
            $this->error('默认图片模型不能为空');
        }
        if ($settings['endpoint']['api_points'] < 0 || $settings['endpoint']['api_points'] > 100000) {
            $this->error('API 单张积分必须在 0～100000 之间');
        }
        if ($settings['endpoint']['rate_limit_per_minute'] < 1 || $settings['endpoint']['rate_limit_per_minute'] > 10000) {
            $this->error('API 每分钟请求上限必须在 1～10000 之间');
        }
        if ($settings['endpoint']['poll_interval'] < 1 || $settings['endpoint']['poll_interval'] > 30) {
            $this->error('轮询间隔必须在 1～30 秒之间');
        }
        if ($settings['endpoint']['max_wait_time'] < 30 || $settings['endpoint']['max_wait_time'] > 300) {
            $this->error('最大等待时间必须在 30～300 秒之间');
        }

        return $settings;
    }

    /**
     * 密码修改
     * @adminMenu(
     *     'name'   => '密码修改',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改',
     *     'param'  => ''
     * )
     */
    public function password()
    {
        return $this->fetch();
    }

    /**
     * 密码修改提交
     * @adminMenu(
     *     'name'   => '密码修改提交',
     *     'parent' => 'password',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改提交',
     *     'param'  => ''
     * )
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            if (empty($data['old_password'])) {
                $this->error("原始密码不能为空！");
            }
            if (empty($data['password'])) {
                $this->error("新密码不能为空！");
            }

            $userId = cmf_get_current_admin_id();

            $admin = Db::name('user')->where("id", $userId)->find();

            $oldPassword = $data['old_password'];
            $password    = $data['password'];
            $rePassword  = $data['re_password'];

            if (cmf_compare_password($oldPassword, $admin['user_pass'])) {
                if ($password == $rePassword) {

                    if (cmf_compare_password($password, $admin['user_pass'])) {
                        $this->error("新密码不能和原始密码相同！");
                    } else {
                        Db::name('user')->where('id', $userId)->update(['user_pass' => cmf_password($password)]);
                        $this->success("密码修改成功！");
                    }
                } else {
                    $this->error("密码输入不一致！");
                }

            } else {
                $this->error("原始密码不正确！");
            }
        }
    }

    /**
     * 上传限制设置界面
     * @adminMenu(
     *     'name'   => '上传设置',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置',
     *     'param'  => ''
     * )
     */
    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();
        $this->assign('upload_setting', $uploadSetting);
        return $this->fetch();
    }

    /**
     * 上传限制设置界面提交
     * @adminMenu(
     *     'name'   => '上传设置提交',
     *     'parent' => 'upload',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置提交',
     *     'param'  => ''
     * )
     */
    public function uploadPost()
    {
        if ($this->request->isPost()) {
            //TODO 非空验证
            $uploadSetting = $this->request->post();

            cmf_set_option('upload_setting', $uploadSetting);
            $this->success('保存成功！');
        }

    }

    /**
     * 清除缓存
     * @adminMenu(
     *     'name'   => '清除缓存',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '清除缓存',
     *     'param'  => ''
     * )
     */
    public function clearCache()
    {
        $content = hook_one('admin_setting_clear_cache_view');

        if (!empty($content)) {
            return $content;
        }

        cmf_clear_cache();
        return $this->fetch();
    }


}
