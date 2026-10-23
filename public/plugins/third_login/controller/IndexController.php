<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-15 19:58:01
// +----------------------------------------------
namespace plugins\third_login\controller;

use cmf\controller\PluginBaseController;

class IndexController extends PluginBaseController
{
	public function index(){
		$this->assign('user', session('user'));
		return $this->fetch();
	}
}