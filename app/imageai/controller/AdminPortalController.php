<?php

namespace app\imageai\controller;

use app\imageai\service\AgreementService;
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
            ->field('p.*,u.mobile,u.user_nickname,u.create_time AS user_create_time');
        if ($keyword !== '') {
            $query->whereLike('u.mobile|u.user_nickname', '%' . $keyword . '%');
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
            ->field('k.*,u.mobile')->order('k.id DESC')->paginate(30);
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
        $query = Db::name('imageai_api_request_log')->alias('r')->join('__USER__ u', 'u.id=r.user_id', 'LEFT')
            ->field('r.*,u.mobile');
        if (in_array($status, ['success', 'reject', 'error'], true)) {
            $query->where('r.status', $status);
        }
        $list = $query->order('r.id DESC')->paginate(30, false, ['query' => $this->request->param()]);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
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
            ->field('w.*,u.mobile,d.source,d.model AS api_model,d.api_key_id,d.upstream_task_id,d.produce_id,d.width AS api_width,d.height AS api_height')
            ->where('w.picture_url', '<>', '')->order('w.create_timestamp DESC')->paginate(24);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch('/admin_portal/works');
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
        $userId = (int)$this->request->param('id');
        $status = $this->request->param('status') ? 1 : 0;
        Db::name('imageai_user_profile')->where('user_id', $userId)->update(['status' => $status, 'update_time' => time()]);
        $this->success($status ? '用户已启用' : '用户已停用', url('AdminPortal/users'));
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
