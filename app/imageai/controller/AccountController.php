<?php

namespace app\imageai\controller;

use app\imageai\service\AccessService;
use app\imageai\service\ApiKeyService;
use app\imageai\service\RealNameConfig;
use app\imageai\service\RealNameService;
use cmf\controller\HomeBaseController;
use think\Db;

class AccountController extends HomeBaseController
{
    protected function initialize()
    {
        parent::initialize();
        if (!cmf_is_user_login()) {
            $this->redirect('/imageai/login');
        }
        AccessService::ensureProfile(cmf_get_current_user_id());
        if (!AccessService::isEnabled(cmf_get_current_user_id())) {
            $this->error('ImageAI 账号已停用', '/imageai/logout');
        }
        $this->assign('account_mode', AccessService::accountMode(cmf_get_current_user_id()));
    }

    public function index()
    {
        $userId = cmf_get_current_user_id();
        $this->assign('key_count', Db::name('imageai_api_key')->where(['user_id' => $userId, 'status' => 1])->count());
        $this->assign('request_count', Db::name('imageai_api_request_log')->where('user_id', $userId)->count());
        $this->assign('work_count', Db::name('ai_user_works')->where('uid', $userId)->where('picture_url', '<>', '')->count());
        $this->assign('points', (int)Db::name('user_points')->where('userid', $userId)->value('points'));
        $this->assign('generation_eligibility', AccessService::generationEligibility($userId));
        return $this->fetch('/account/index');
    }

    public function apiKeys()
    {
        $eligibility = AccessService::generationEligibility(cmf_get_current_user_id());
        $keys = Db::name('imageai_api_key')->where('user_id', cmf_get_current_user_id())->order('id DESC')->select();
        $this->assign('keys', $keys);
        $this->assign('real_name_eligible', !empty($eligibility['allowed']));
        $this->assign('new_api_key', session('imageai_new_api_key'));
        session('imageai_new_api_key', null);
        return $this->fetch('/account/api_keys');
    }

    public function createApiKey()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        try {
            $result = (new ApiKeyService())->create(
                cmf_get_current_user_id(),
                $this->request->post('name'),
                $this->request->post('expires_in_days', 0, 'intval')
            );
            session('imageai_new_api_key', $result['key']);
            return redirect('/imageai/account/api-keys');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function revokeApiKey()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        (new ApiKeyService())->revoke(cmf_get_current_user_id(), $this->request->param('id'));
        return redirect('/imageai/account/api-keys');
    }

    public function works()
    {
        $list = Db::name('ai_user_works')->where('uid', cmf_get_current_user_id())->where('picture_url', '<>', '')->order('create_timestamp DESC')->paginate(24);
        $this->assign('list', $list);
        $this->assign('page', $this->renderPagination($list, '作品分页'));
        return $this->fetch('/account/works');
    }

    public function requests()
    {
        $list = Db::name('imageai_api_request_log')->where('user_id', cmf_get_current_user_id())->order('id DESC')->paginate(30);
        $this->assign('list', $list);
        $this->assign('page', $this->renderPagination($list, '请求分页'));
        return $this->fetch('/account/requests');
    }

    public function security()
    {
        $this->assign('current_user', cmf_get_current_user());
        return $this->fetch('/account/security');
    }

    public function realName()
    {
        $userId = cmf_get_current_user_id();
        $this->assign('real_name_enabled', !empty(RealNameConfig::settings()['enabled']));
        $this->assign('real_name_record', RealNameService::record($userId));
        $this->assign('real_name_notice', session('imageai_real_name_notice'));
        $this->assign('real_name_error', session('imageai_real_name_error'));
        session('imageai_real_name_notice', null);
        session('imageai_real_name_error', null);
        return $this->fetch('/account/real_name');
    }

    public function realNamePost()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        if (empty(RealNameConfig::settings()['enabled'])) {
            session('imageai_real_name_error', '实名认证功能当前未启用');
            return redirect('/imageai/account/real-name');
        }

        $userId = cmf_get_current_user_id();
        $record = RealNameService::record($userId);
        if ($record && in_array((string)$record['status'], [
            RealNameService::STATUS_ADULT,
            RealNameService::STATUS_MINOR,
        ], true)) {
            session('imageai_real_name_notice', '当前账号已经完成实名认证，无需重复提交');
            return redirect('/imageai/account/real-name');
        }

        try {
            $result = (new RealNameService())->verify(
                $userId,
                $this->request->post('real_name'),
                $this->request->post('id_card'),
                $this->request->post('consent') === '1'
            );
            session(
                'imageai_real_name_notice',
                !empty($result['adult']) ? '实名认证成功，已开通图片生成服务' : '实名认证成功，但当前未满18周岁，暂不能使用图片生成服务'
            );
        } catch (\Throwable $e) {
            session('imageai_real_name_error', $e->getMessage());
        }
        return redirect('/imageai/account/real-name');
    }

    private function renderPagination($paginator, $ariaLabel)
    {
        if (!$paginator->hasPages()) {
            return '';
        }

        $current = (int)$paginator->currentPage();
        $last = (int)$paginator->lastPage();
        $pages = [1, $last];
        for ($page = max(1, $current - 2); $page <= min($last, $current + 2); $page++) {
            $pages[] = $page;
        }
        $pages = array_values(array_unique($pages));
        sort($pages);

        $html = '<nav aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8')
            . '" style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:24px">';
        $html .= $this->paginationControl('«', $current > 1 ? $this->pageUrl($paginator, $current - 1) : null);

        $previous = 0;
        foreach ($pages as $page) {
            if ($previous > 0 && $page > $previous + 1) {
                $html .= $this->paginationControl('…', null);
            }
            $html .= $this->paginationControl((string)$page, $this->pageUrl($paginator, $page), $page === $current);
            $previous = $page;
        }

        $html .= $this->paginationControl('»', $current < $last ? $this->pageUrl($paginator, $current + 1) : null);
        return $html . '</nav>';
    }

    private function pageUrl($paginator, $page)
    {
        $urls = $paginator->getUrlRange($page, $page);
        return $urls[$page];
    }

    private function paginationControl($label, $url = null, $active = false)
    {
        $base = 'display:inline-flex;min-width:36px;height:36px;align-items:center;justify-content:center;'
            . 'padding:0 11px;border:1px solid #e4e7ec;border-radius:7px;line-height:1;text-decoration:none;';
        $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        if ($active) {
            return '<span style="' . $base . 'background:#1967d2;color:#fff;font-weight:600">' . $label . '</span>';
        }
        if ($url === null) {
            return '<span style="' . $base . 'background:#f8fafc;color:#98a2b3">' . $label . '</span>';
        }
        return '<a style="' . $base . 'background:#fff;color:#172033" href="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
    }
}
