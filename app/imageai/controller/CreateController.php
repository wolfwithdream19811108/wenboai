<?php

namespace app\imageai\controller;

use app\ai\service\ImageGenerationConfig;
use app\imageai\service\AccessService;
use app\user\model\UserPointsModel;
use cmf\controller\HomeBaseController;

class CreateController extends HomeBaseController
{
    public function index()
    {
        $isLogin = cmf_is_user_login();
        if ($isLogin && !AccessService::isEnabled(cmf_get_current_user_id())) {
            $this->error('ImageAI 账号已停用或已过期', '/imageai/logout');
        }
        if ($isLogin) {
            $eligibility = AccessService::generationEligibility(cmf_get_current_user_id());
            if (empty($eligibility['allowed'])) {
                $this->error($eligibility['message'], '/imageai/account/real-name');
            }
            if (AccessService::isApiOnly(cmf_get_current_user_id())) {
                $this->error('当前账号未开通网页生图权限', '/imageai/account/api-keys');
            }
            $this->assign('points', (new UserPointsModel())->getUserPoints(cmf_get_current_user_id()));
        }

        $this->assign('isLogin', $isLogin);
        $this->assign('example_prompts', ImageGenerationConfig::settings()['examples']);
        return $this->fetch('/create');
    }
}
