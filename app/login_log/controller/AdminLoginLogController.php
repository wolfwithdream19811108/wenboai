<?php

namespace app\login_log\controller;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminLoginLogController
 * @package app\login_log\controller
 * @adminMenuRoot(
 *     'name'   =>'登录记录',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 50,
 *     'icon'   =>'user',
 *     'remark' =>'登录记录'
 * )
 */
class AdminLoginLogController extends AdminBaseController
{
 public function Index(){
       $params = $this->request->param(); 
       if(empty($params['username'])){
           
       }else{
           $username = $params['username'];
           $loginLogs = Db::name('user_login_log')->where('username',$username)->select();
           $this->assign('list', $loginLogs);           
       }

       return $this->fetch();
    }    

}
