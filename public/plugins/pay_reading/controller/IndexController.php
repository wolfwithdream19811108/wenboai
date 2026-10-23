<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
namespace plugins\pay_reading\controller; 
use cmf\controller\PluginBaseController;
use plugins\pay_reading\Model\PluginPayReadingModel;
use think\Db;

class IndexController extends PluginBaseController
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->user = cmf_get_current_user();//获取用户，可判断是否登录
        
        if (!empty($this->user)) {
            $this->assign("user", $this->user);
        }else {
            echo json_encode(['code'=>100,'data'=>'请先登录。']);
            exit;
        }
    }

    function index()
    {
        exit(11);
        $view = new \app\user\controller\IndexController();
        $user = cmf_get_current_user();
        if(!empty($user)){
            $view->assign('user_login',$user['user_nickname']);            
            $view->assign('user',$user);
        }else{
            $view->assign('user_login',false);
            return $view->fetch('/user/login');
        }
        $data = Db::name('user')->where('id',$user['id'])->find();
        $view->assign('balance',$data['balance']);
        return $view->fetch('/user/balance/index');
    }

    function buy(){
        $res = $this->buy_reading($this->request->post('postid'), $this->user['id']);
        return json($res);
    }

    public function buy_reading($postid, $uid){
        $info = Db::name('plugin_payreading_payedlist')->where('uid', $uid)
        ->where('postid', $postid)->find();
        if(!empty($info)) return ['code'=>1000,'data'=>'已购买，无须重复购买！'];
        $info = Db::name('plugin_payreading_info')->where('postid', $postid)->find();
        if(empty($info) || $info['__pay_is_pay'] == 0) return ['code'=>1000, 'data'=>'无须购买！'];
        $user = Db::name('user')->where('id', $uid)->find();
        if((float)$user['balance']<(float)$info['__pay_money']) return ['code'=>1000,'data'=>'余额不足！'];
        Db::transaction(function() use($postid,$uid,$info){
            Db::name('plugin_payreading_payedlist')
            ->insert([
                'postid' => $postid,
                'uid'    => $uid,
                'payed_money'   => $info['__pay_money'],
                'payed_time'    => (int)time()
            ]);
            Db::name('user')->where('id', $uid)->exp('balance','balance-'.$info['__pay_money'])->update();
        });
        return ['code'=>0];
    }

}
