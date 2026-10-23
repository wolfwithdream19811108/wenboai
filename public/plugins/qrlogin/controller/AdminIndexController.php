<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\qrlogin\controller;
use cmf\controller\PluginAdminBaseController;
use think\Db;
use think\Cache;
use app\user\model\UserModel;


/**
 * Class AdminIndexController
 * @package plugins\qrlogin\controller
 * @adminMenuRoot(
 *     'name'   =>'管理员微信绑定',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'weixin',
 *     'remark' =>'管理员微信绑定入口'
 * )
 */
class AdminIndexController extends PluginAdminBaseController
{

    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            header('HTTP/1.1 404 Not Found');
            header('Status:404 Not Found');
            $this->error('非法登录！！');
        }
    }
    /**
     * 微信绑定
     * @adminMenu(
     *     'name'   => '微信绑定',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '微信绑定',
     *     'param'  => ''
     * )
     */
    function index()
    {   $Uid = cmf_get_current_admin_id();
		if($Uid==1){
			$where = [
				'status' 	=> 1,
				'union_id' 	=> 'WXadmin'
			];
		}else{
			$where = [
				'status' 	=> 1,
				'user_id'  	=> $Uid,
				'union_id' 	=> 'WXadmin'
			];
		}
        $lists = Db::name('third_party_user')->field('a.*,u.user_nickname,u.user_login,u.sex,u.avatar')
            ->alias('a')
            ->join('__USER__ u', 'a.user_id = u.id')
            ->where($where)
            ->order("create_time DESC")
            ->paginate(10);

        // 获取分页显示
        $page = $lists->render();
        $this->assign('lists', $lists);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch('/admin_index');
    }

    //删除
    function delete()
    {
        $id = input('param.id', 0, 'intval');
        if (empty($id)) {
            $this->error('非法数据！');
        }
        Db::name("third_party_user")->where("id", $id)->delete();
        $this->success('删除成功！');
    }
	function QrUuid() {
	    $qruuid = base_convert(str_replace('.', '', trim(uniqid('uuid', true), 'uuid')),16,36);
		cache($qruuid, 'waiting',180);
		cookie("qruuid", $qruuid,180);
	    $this->success($qruuid);
	}
	function checkQrUuid() {
		$_POST = $this->request->param();
		$qruuid = $_POST['q'];
		$qrType = $_POST['t'];
		$data = cache($qruuid);
		if(empty($data)){
			$this->error('二维码无效');
		}
		if($data=='login'){
			$this->success('2');
		}
		if(preg_match("/^[a-zA-Z0-9_-]{28}$/",$data)){
				$userdata   = ['user_id'=> cmf_get_current_admin_id()];
				Db::name("third_party_user")->where(['openid'=>$data,'union_id'=>"WXadmin"])->update($userdata);
				$this->success('3');
		}
		$this->success('0');
	}
}
