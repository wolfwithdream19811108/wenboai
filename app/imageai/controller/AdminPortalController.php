<?php

namespace app\imageai\controller;

use app\imageai\service\AgreementService;
use app\imageai\service\FailureLogService;
use app\imageai\service\RealNameService;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * @adminMenuRoot(
 *     'name'   =>'ImageAI 开放平台',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 60,
 *     'icon'   =>'image',
 *     'remark' =>'ImageAI 用户、密钥、请求和作品管理'
 * )
 */
class AdminPortalController extends AdminBaseController
{
    const MAX_USER_POINTS = 10000000;

    /**
     * @adminMenu(
     *     'name'    => '开发者用户',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 10
     * )
     */
    public function users()
    {
        $keyword = trim((string)$this->request->param('keyword'));
        $query = Db::name('imageai_user_profile')->alias('p')
            ->join('__USER__ u', 'u.id=p.user_id')
            ->join('__IMAGEAI_REAL_NAME_VERIFICATION__ r', 'r.user_id=p.user_id', 'LEFT')
            ->join('__USER_POINTS__ up', 'up.userid=p.user_id', 'LEFT')
            ->field('p.*,u.user_login,u.mobile,u.user_nickname,u.create_time AS user_create_time,r.status AS real_name_status,r.verified_at AS real_name_verified_at,COALESCE(up.points,0) AS points');
        if ($keyword !== '') {
            $query->whereLike('u.user_login|u.mobile|u.user_nickname', '%' . $keyword . '%');
        }
        $list = $query->order('p.create_time DESC')->paginate(20, false, ['query' => $this->request->param()]);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/users');
    }

    /**
     * @adminMenu(
     *     'name'    => 'API Keys',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 20
     * )
     */
    public function keys()
    {
        $list = Db::name('imageai_api_key')->alias('k')->join('__USER__ u', 'u.id=k.user_id')
            ->field('k.*,u.user_login,u.mobile')->order('k.id DESC')->paginate(30);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/keys');
    }

    /**
     * @adminMenu(
     *     'name'    => 'API 请求',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 30
     * )
     */
    public function requests()
    {
        $status = trim((string)$this->request->param('status'));
        $source = trim((string)$this->request->param('source'));
        $reasonType = trim((string)$this->request->param('reason_type'));
        $query = Db::name('imageai_api_request_log')->alias('r')->join('__USER__ u', 'u.id=r.user_id', 'LEFT')
            ->field('r.*,u.user_login,u.mobile');
        if (in_array($status, ['success', 'reject', 'error'], true)) {
            $query->where('r.status', $status);
        }
        if (in_array($source, ['web', 'api', 'api_test'], true)) {
            $query->where('r.source', $source);
        }
        $reasonMap = [
            'sensitive' => ['输入内容包含敏感信息，拒绝服务'],
            'out_of_domain' => ['提示词不属于艺术品/工艺美术品/文创作品范畴，拒绝生成'],
            'classifier_unavailable' => ['项目分类暂时无法服务', '内容类目判断服务暂不可用'],
        ];
        if (isset($reasonMap[$reasonType])) {
            $query->whereIn('r.reason', $reasonMap[$reasonType]);
        }
        $list = $query->order('r.id DESC')->paginate(30, false, ['query' => $this->request->param()]);
        $items = $list->items();
        foreach ($items as &$item) {
            $sourceLabels = [
                'web' => '网页端',
                'api' => '用户 API',
                'api_test' => '后台测试 Key',
            ];
            $itemSource = isset($item['source']) && $item['source'] !== ''
                ? (string)$item['source']
                : ((int)$item['api_key_id'] > 0 ? 'api' : ((int)$item['user_id'] > 0 ? 'web' : 'api_test'));
            $item['source'] = $itemSource;
            $item['source_label'] = $sourceLabels[$itemSource] ?? $itemSource;
            $item['reason_type'] = '';
            $item['reason_label'] = '';
            $item['reason_class'] = 'label-default';
            if ($item['reason'] === '输入内容包含敏感信息，拒绝服务') {
                $item['reason_type'] = 'sensitive';
                $item['reason_label'] = '内容拒绝';
                $item['reason_class'] = 'label-danger';
            } elseif ($item['reason'] === '提示词不属于艺术品/工艺美术品/文创作品范畴，拒绝生成') {
                $item['reason_type'] = 'out_of_domain';
                $item['reason_label'] = '领域拒绝';
                $item['reason_class'] = 'label-warning';
            } elseif (in_array($item['reason'], $reasonMap['classifier_unavailable'], true)) {
                $item['reason_type'] = 'classifier_unavailable';
                $item['reason_label'] = '服务异常';
                $item['reason_class'] = 'label-danger';
            }
        }
        unset($item);
        $this->assign('list', $items);
        $this->assign('page', $list->render());
        $this->assign('request_status', $status);
        $this->assign('request_source', $source);
        $this->assign('request_reason_type', $reasonType);
        return $this->fetch('/admin_portal/requests');
    }

    /**
     * @adminMenu(
     *     'name'    => '生成作品',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 40
     * )
     */
    public function works()
    {
        $list = Db::name('ai_user_works')->alias('w')
            ->join('__USER__ u', 'u.id=w.uid', 'LEFT')
            ->join('__IMAGEAI_WORK_DETAIL__ d', 'd.work_id=w.id', 'LEFT')
            ->field('w.*,u.user_login,u.mobile,d.source,d.model AS api_model,d.api_key_id,d.upstream_task_id,d.produce_id,d.width AS api_width,d.height AS api_height')
            ->where('w.picture_url', '<>', '')->order('w.create_timestamp DESC')->paginate(24);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/works');
    }

    /**
     * @adminMenu(
     *     'name'    => '失败日志',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 45
     * )
     */
    public function failureLogs()
    {
        $stage = trim((string)$this->request->param('stage', ''));
        $status = trim((string)$this->request->param('status', ''));
        $requestId = trim((string)$this->request->param('request_id', ''));
        $userId = (int)$this->request->param('user_id', 0);
        $dateFrom = trim((string)$this->request->param('date_from', ''));
        $dateTo = trim((string)$this->request->param('date_to', ''));

        $query = Db::name('imageai_failure_log')->alias('f')
            ->join('__USER__ u', 'u.id=f.user_id', 'LEFT')
            ->field('f.*,u.user_login,u.mobile');
        if (in_array($stage, ['classifier', 'upyun', 'jimeng', 'local_storage', 'configuration'], true)) {
            $query->where('f.stage', $stage);
        }
        if (in_array($status, ['pending', 'resolved', 'ignored'], true)) {
            $query->where('f.status', $status);
        }
        if ($requestId !== '') {
            $query->whereLike('f.request_id', '%' . $requestId . '%');
        }
        if ($userId > 0) {
            $query->where('f.user_id', $userId);
        }
        if ($dateFrom !== '' && ($fromTimestamp = strtotime($dateFrom . ' 00:00:00'))) {
            $query->where('f.create_time', '>=', $fromTimestamp);
        }
        if ($dateTo !== '' && ($toTimestamp = strtotime($dateTo . ' 23:59:59'))) {
            $query->where('f.create_time', '<=', $toTimestamp);
        }

        $list = $query->order('f.id DESC')->paginate(30, false, ['query' => $this->request->param()]);
        $items = $list->items();
        foreach ($items as &$item) {
            $decoded = json_decode((string)$item['context_json'], true);
            $item['context_pretty'] = is_array($decoded)
                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                : '';
        }
        unset($item);
        $this->assign('list', $items);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/failure_logs');
    }

    /**
     * @adminMenu(
     *     'name'    => '实名认证记录',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 48
     * )
     */
    public function realNames()
    {
        $status = trim((string)$this->request->param('status', ''));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $query = Db::name('imageai_real_name_verification')->alias('r')
            ->join('__USER__ u', 'u.id=r.user_id', 'LEFT')
            ->field('r.*,u.user_login,u.mobile,u.user_nickname');
        if (in_array($status, [
            RealNameService::STATUS_ADULT,
            RealNameService::STATUS_MINOR,
            RealNameService::STATUS_FAILED,
            RealNameService::STATUS_UNVERIFIED,
        ], true)) {
            $query->where('r.status', $status);
        }
        if ($keyword !== '') {
            $query->whereLike('u.user_login|u.mobile|u.user_nickname|r.provider_request_id', '%' . $keyword . '%');
        }
        $list = $query->order('r.update_time DESC')->paginate(30, false, ['query' => $this->request->param()]);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/real_names');
    }

    /**
     * @adminMenu(
     *     'name'    => '重置实名认证',
     *     'parent'  => 'imageai/AdminPortal/realNames',
     *     'display' => false,
     *     'hasView' => false
     * )
     */
    public function resetRealName()
    {
        $userId = (int)$this->request->param('id');
        if ($userId <= 0) {
            $this->error('用户参数无效');
        }
        Db::name('imageai_real_name_verification')->where('user_id', $userId)->delete();
        $this->success('实名认证状态已重置', url('AdminPortal/realNames'));
    }

    /**
     * @adminMenu(
     *     'name'    => '重试又拍云同步',
     *     'parent'  => 'imageai/AdminPortal/failureLogs',
     *     'display' => false,
     *     'hasView' => false
     * )
     */
    public function retryFailure()
    {
        try {
            $result = (new FailureLogService())->retryUpyun((int)$this->request->param('id'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return;
        }
        if (empty($result['success'])) {
            $this->error((string)($result['error_message'] ?? '重新同步失败'));
            return;
        }
        $this->success('又拍云重新同步成功', url('AdminPortal/failureLogs', ['stage' => 'upyun']));
    }

    /**
     * @adminMenu(
     *     'name'    => '协议管理',
     *     'parent'  => 'imageai/AdminPortal/default',
     *     'display' => true,
     *     'hasView' => true,
     *     'order'   => 50
     * )
     */
    public function agreements()
    {
        $this->assign('agreements', AgreementService::all());
        return $this->fetch('/admin_portal/agreements');
    }

    /**
     * @adminMenu(
     *     'name'    => '发布协议',
     *     'parent'  => 'imageai/AdminPortal/agreements',
     *     'display' => false,
     *     'hasView' => false
     * )
     */
    public function agreementPost()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $type = (string)$this->request->post('type');
        $agreement = [
            'title'   => (string)$this->request->post('title'),
            'version' => (string)$this->request->post('version'),
            'content' => (string)$this->request->post('content', '', null),
        ];
        if ($agreement['title'] === '' || $agreement['version'] === '' || trim(strip_tags($agreement['content'])) === '') {
            $this->error('标题、版本号和协议正文不能为空');
        }
        if (!AgreementService::publish($type, $agreement)) {
            $this->error('协议发布失败');
        }

        $this->success('协议已发布', url('AdminPortal/agreements', ['tab' => $type]));
    }

    /**
     * @adminMenu(
     *     'name'    => '启停开发者用户',
     *     'parent'  => 'imageai/AdminPortal/users',
     *     'display' => false,
     *     'hasView' => false
     * )
     */
    public function userStatus()
    {
        $operation = $this->request->isPost()
            ? trim((string)$this->request->post('operation', ''))
            : '';
        if ($operation === 'set_account_mode') {
            $userId = (int)$this->request->post('id');
            $mode = trim((string)$this->request->post('account_mode', ''));
            $profile = Db::name('imageai_user_profile')->where('user_id', $userId)->find();
            if (!$profile) {
                return json(['code' => 0, 'msg' => 'ImageAI 用户不存在']);
            }
            if ((string)$profile['account_mode'] === 'test') {
                return json(['code' => 0, 'msg' => '测试账号能力由测试账号设置管理']);
            }
            if (!in_array($mode, ['normal', 'api_only'], true)) {
                return json(['code' => 0, 'msg' => '账号能力参数无效']);
            }
            Db::name('imageai_user_profile')->where('user_id', $userId)->update([
                'account_mode' => $mode,
                'update_time' => time(),
            ]);
            return json([
                'code' => 1,
                'msg' => $mode === 'normal' ? '已允许网页生图和 API' : '已设为仅 API',
            ]);
        }

        if (in_array($operation, ['set_points', 'add_points'], true)) {
            return $this->updateUserPoints($operation);
        }

        if ($operation === 'points_history') {
            return $this->userPointsHistory();
        }

        if ($operation === 'reset_password') {
            $userId = (int)$this->request->post('id');
            $password = (string)$this->request->post('password', '');
            $passwordConfirm = (string)$this->request->post('password_confirm', '');
            if ($userId <= 0 || !Db::name('imageai_user_profile')->where('user_id', $userId)->find()) {
                return json(['code' => 0, 'msg' => 'ImageAI 用户不存在']);
            }
            $passwordLength = strlen($password);
            if ($passwordLength < 8 || $passwordLength > 32) {
                return json(['code' => 0, 'msg' => '新密码长度必须为 8～32 位']);
            }
            if (!hash_equals($password, $passwordConfirm)) {
                return json(['code' => 0, 'msg' => '两次输入的新密码不一致']);
            }
            Db::name('user')->where('id', $userId)->update([
                'user_pass' => cmf_password($password),
            ]);
            return json(['code' => 1, 'msg' => '密码已重设']);
        }

        $userId = (int)$this->request->param('id');
        $status = $this->request->param('status') ? 1 : 0;
        Db::name('imageai_user_profile')->where('user_id', $userId)->update(['status' => $status, 'update_time' => time()]);
        $this->success($status ? '用户已启用' : '用户已停用', url('AdminPortal/users'));
    }

    private function updateUserPoints($operation)
    {
        $userId = (int)$this->request->post('id');
        $rawPoints = trim((string)$this->request->post('points', ''));
        $remark = trim((string)$this->request->post('remark', ''));
        $profile = Db::name('imageai_user_profile')->where('user_id', $userId)->find();
        if (!$profile) {
            return json(['code' => 0, 'msg' => 'ImageAI 用户不存在']);
        }
        if ((string)$profile['account_mode'] === 'test') {
            return json(['code' => 0, 'msg' => '测试账号请在“测试账号设置”中调整积分']);
        }
        if (!preg_match('/^-?[0-9]+$/D', $rawPoints)) {
            return json(['code' => 0, 'msg' => '请输入整数积分']);
        }
        $points = (int)$rawPoints;
        if ($operation === 'set_points' && ($points < 0 || $points > self::MAX_USER_POINTS)) {
            return json(['code' => 0, 'msg' => '积分必须在 0～' . self::MAX_USER_POINTS . ' 之间']);
        }
        if ($operation === 'add_points'
            && ($points === 0 || $points < -self::MAX_USER_POINTS || $points > self::MAX_USER_POINTS)) {
            return json(['code' => 0, 'msg' => '增减积分不能为 0，且不能超过 ' . self::MAX_USER_POINTS]);
        }
        if (mb_strlen($remark, 'UTF-8') > 200) {
            return json(['code' => 0, 'msg' => '备注不能超过 200 个字符']);
        }

        Db::startTrans();
        try {
            $pointsRow = Db::name('user_points')->where('userid', $userId)->lock(true)->find();
            if (!$pointsRow) {
                Db::name('user_points')->insert(['userid' => $userId, 'points' => 0]);
                $beforePoints = 0;
            } else {
                $beforePoints = (int)$pointsRow['points'];
            }
            $afterPoints = $operation === 'set_points' ? $points : $beforePoints + $points;
            if ($afterPoints < 0) {
                throw new \RuntimeException('积分不足，扣减后不能小于 0');
            }
            if ($afterPoints > self::MAX_USER_POINTS) {
                throw new \RuntimeException('调整后积分不能超过 ' . self::MAX_USER_POINTS);
            }
            Db::name('user_points')->where('userid', $userId)->update(['points' => $afterPoints]);
            Db::name('imageai_points_log')->insert([
                'user_id' => $userId,
                'admin_id' => (int)cmf_get_current_admin_id(),
                'operation' => $operation === 'set_points' ? 'set' : 'add',
                'change_points' => $afterPoints - $beforePoints,
                'before_points' => $beforePoints,
                'after_points' => $afterPoints,
                'remark' => $remark,
                'admin_ip' => get_client_ip(0, true),
                'create_time' => time(),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }

        return json([
            'code' => 1,
            'msg' => '积分调整成功',
            'data' => ['points' => $afterPoints],
        ]);
    }

    private function userPointsHistory()
    {
        $userId = (int)$this->request->post('id');
        if ($userId <= 0 || !Db::name('imageai_user_profile')->where('user_id', $userId)->find()) {
            return json(['code' => 0, 'msg' => 'ImageAI 用户不存在']);
        }
        try {
            $logs = Db::name('imageai_points_log')->alias('l')
                ->join('__USER__ a', 'a.id=l.admin_id', 'LEFT')
                ->where('l.user_id', $userId)
                ->field('l.admin_id,l.operation,l.change_points,l.before_points,l.after_points,l.remark,l.admin_ip,l.create_time,a.user_login AS admin_login,a.user_nickname AS admin_nickname')
                ->order('l.id DESC')
                ->limit(50)
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => '积分记录表尚未安装']);
        }
        foreach ($logs as &$log) {
            $log['operation_label'] = $log['operation'] === 'set' ? '设置积分' : '增减积分';
            $log['admin_name'] = $log['admin_nickname'] ?: ($log['admin_login'] ?: ('管理员#' . $log['admin_id']));
            $log['create_time_text'] = $log['create_time'] ? date('Y-m-d H:i:s', $log['create_time']) : '';
        }
        unset($log);
        return json(['code' => 1, 'msg' => '读取成功', 'data' => $logs]);
    }

    /**
     * @adminMenu(
     *     'name'    => '撤销 API Key',
     *     'parent'  => 'imageai/AdminPortal/keys',
     *     'display' => false,
     *     'hasView' => false
     * )
     */
    public function revokeKey()
    {
        Db::name('imageai_api_key')->where('id', (int)$this->request->param('id'))->update(['status' => 0, 'update_time' => time()]);
        $this->success('API Key 已撤销', url('AdminPortal/keys'));
    }
}
