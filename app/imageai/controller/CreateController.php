<?php

namespace app\imageai\controller;

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
        if ($isLogin && AccessService::isApiOnly(cmf_get_current_user_id())) {
            $this->error('此账号仅支持通过 API 创建图片', '/imageai/account/api-keys');
        }
        if ($isLogin) {
            $this->assign('points', (new UserPointsModel())->getUserPoints(cmf_get_current_user_id()));
        }

        $this->assign('isLogin', $isLogin);
        return $this->fetch('/create');
    }
}
