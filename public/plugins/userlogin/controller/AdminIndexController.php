<?php
// +----------------------------------------------------------------------
// | Userlogin [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\userlogin\controller;

use think\Db;
use cmf\controller\PluginBaseController;
use plugins\userlogin\model\UserLoginLogModel;

class AdminIndexController extends PluginBaseController
{
    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            $this->error('未登录');
        }
    }

    function index()
    {
        $request = input('request.');
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['username']    = ['like', "%$keyword%"];
            $keywordComplex['ip']    = ['like', "%$keyword%"];
        }
        $userloginlog = new UserLoginLogModel();
        $logins         = $userloginlog->whereOr($keywordComplex)->order("id DESC")->paginate(10);
        $page = $logins->render();
        $this->assign('logins', $logins);
        $this->assign('page', $page);
        return $this->fetch('/admin_index');
    }
}
