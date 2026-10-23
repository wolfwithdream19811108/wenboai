<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
namespace plugins\pay_reading\controller; //Demo插件英文名，改成你的插件英文就行了

use cmf\controller\PluginAdminBaseController;
use think\Db;

class AdminIndexController extends PluginAdminBaseController
{

    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }

    /**
     * 付费阅读插件
     * @adminMenu(
     *     'name'   => '付费阅读',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '付费阅读插件',
     *     'param'  => ''
     * )
     */
    public function index()
    {        
        $page=$this->request->get('page')||1;
        $data = Db::name('plugin_payreading_payedlist')->alias('a')
        ->join('__USER__ b', 'a.uid=b.id')
        ->join('__PLUGIN_PAYREADING_INFO__ c', 'a.postid=c.postid')
        ->join('__PORTAL_POST__ d', 'a.postid=d.id')
        ->join('__PORTAL_CATEGORY_POST__ e', 'a.postid=e.post_id')
        ->order('a.payed_time desc')
        ->field('b.id,b.user_nickname,a.postid,c.__pay_money,d.post_title,from_unixtime(a.payed_time,"%Y-%m-%d %H:%i:%s") as payed_time,a.payed_money,b.balance,e.category_id as cid')
        ->paginate(10);
        $this->assign('data',$data);
        $this->assign('page',$data->render());        
        return $this->fetch('/admin_index');
    }



}
