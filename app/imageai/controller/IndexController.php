<?php

namespace app\imageai\controller;

use app\imageai\service\AgreementService;
use cmf\controller\HomeBaseController;

class IndexController extends HomeBaseController
{
    public function index()
    {
        return $this->fetch('/index');
    }

    public function docs()
    {
        return $this->fetch('/docs');
    }

    public function service()
    {
        $this->assign('agreement', AgreementService::get('service'));
        return $this->fetch('/service');
    }

    public function privacy()
    {
        $this->assign('agreement', AgreementService::get('privacy'));
        return $this->fetch('/privacy');
    }
}
