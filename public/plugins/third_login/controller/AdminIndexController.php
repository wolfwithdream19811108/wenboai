<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-03-29 10:48:49
// +----------------------------------------------
namespace plugins\third_login\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\third_login\model\ConfigModel;

class AdminIndexController extends PluginAdminBaseController{
	/**
	* 第三方登录
	* @adminMenu(
	*     'name'   => '第三方登录',
	*     'parent' => 'admin/Plugin/default',
	*     'display'=> true,
	*     'hasView'=> true,
	*     'order'  => 10000,
	*     'icon'   => '',
	* )
	*/
    public function index(){
        $ConfigModel = new ConfigModel();
        $configs     = $ConfigModel->selectAll();
        $this->assign('configs', $configs);
    	return $this->fetch();
    }

    /**
     * 保存配置
     */
    public function doSave(){
        $data = input('post.');

        $result = $this->validate($data, 'Config.update');
        if(true !== $result) {
            $this->error($result);
        }

        $id = $data['id'];
        unset($data['id']);

        $ConfigModel = new ConfigModel();
        if(false !== $ConfigModel->updateOne(['id' => $id], $data)) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }
}