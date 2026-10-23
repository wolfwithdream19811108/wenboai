<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\qrlogin;
use cmf\lib\Plugin;

class QrloginPlugin extends Plugin
{
    public $info = array(
        'name'        => 'Qrlogin',
        'title'       => '扫码登录',
        'description' => '微信扫码登录【前台】【后台】，后台登录页面自定义、绑定管理员微信，使用服务号授权登录，支持电脑端和移动端。',
        'status'      => 1,
        'author'      => 'Sam',
        'version'     => '1.2'
    );
    public $hasAdmin = 1;

    public function install()
    {
        return true;
    }
    public function uninstall()
    {
        return true;
    }
    public function adminLogin()
    {
		$config = $this->getConfig();
        $this->assign($config);
        return $this->fetch('widget_adminLogin');
    }
}