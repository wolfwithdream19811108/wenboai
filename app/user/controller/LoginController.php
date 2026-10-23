<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use think\facade\Validate;
use cmf\controller\HomeBaseController;
use app\user\model\UserModel;
use think\Db;


class LoginController extends HomeBaseController
{



    /**
     * 登录
     */
    public function index()
    {
        
   
        $redirect = $this->request->param("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            if (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0) {
            } else {
                $redirect = base64_decode($redirect);
            }
        }
        if(!empty($redirect)){
            session('login_http_referer', $redirect);
        }
        if (cmf_is_user_login()) { //已经登录时直接跳到首页
            return redirect($this->request->root() . '/');
        } else {
            return $this->fetch(":login");
        }
    }

/*
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'captcha'  => 'require',
                'username' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'username.require' => '用户名不能为空',
                'password.require' => '密码不能为空',
                'password.max'     => '密码不能超过32个字符',
                'password.min'     => '密码不能小于6个字符',
                'captcha.require'  => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error(lang('CAPTCHA_NOT_RIGHT'));
            }

            $userModel         = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $userModel->doEmail($user);
            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->doMobile($user);
            } else {
                $user['user_login'] = $data['username'];
                $log                = $userModel->doName($user);
            }
            $session_login_http_referer = session('login_http_referer');
            $redirect                   = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
            switch ($log) {
                case 0:
                    cmf_user_action('login');
                    $this->addLoginLog();
                    $this->success(lang('LOGIN_SUCCESS'), $redirect);
                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问系统');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }
    
    
 public function dologinnoverify()
    {
        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'username' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'username.require' => '用户名不能为空',
                'password.require' => '密码不能为空',
                'password.max'     => '密码不能超过32个字符',
                'password.min'     => '密码不能小于6个字符',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }


            $userModel         = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $userModel->doEmail($user);
            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->doMobile($user);
            } else {
                $user['user_login'] = $data['username'];
                $log                = $userModel->doName($user);
            }
            $session_login_http_referer = session('login_http_referer');
            $redirect                   = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
            switch ($log) {
                case 0:
                    cmf_user_action('login');
                    $this->addLoginLog();
                    $this->success(lang('LOGIN_SUCCESS'), $redirect);
                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问系统');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }   
    */
private function loginJson(
    $captchaResult,
    $bizResult,
    $message,
    $url = '',
    $status = 200
) {
    return json([
        'code' => $bizResult ? 1 : 0,
        'msg'  => $message,
        'url'  => $url,
        'data' => [
            'captchaResult' => $captchaResult,
            'bizResult'     => $bizResult,
        ],
    ], $status);
}

public function doLogin()
{
    if (!$this->request->isPost()) {
        return $this->loginJson(
            false,
            false,
            '请求错误',
            '',
            405
        );
    }

    // null 关闭这个字段的默认过滤，保持阿里云参数原样。
    $captchaVerifyParam = $this->request->post(
        'captchaVerifyParam',
        '',
        null
    );

    $captchaResult = cmf_verify_aliyun_captcha(
        $captchaVerifyParam
    );

    // 每次登录都必须在服务端验证本次阿里云参数。
/*    if ($captchaResult['verifyResult'] !== true) {
        return $this->loginJson(
            false,
            false,
            '验证码验证失败，请重新完成滑块验证'
        );
    }*/
    
if ($captchaResult['verifyResult'] !== true) {
    return json([
        'code' => 0,
        'msg'  => '验证码验证失败，请重新完成滑块验证',
        'url'  => '',
        'data' => [
            'captchaResult' => false,
            'bizResult'     => false,
            'verifyCode'   => $captchaResult['verifyCode']
                ?? 'UNKNOWN',
        ],
    ]);
}

    // 账号密码沿用你原来的 POST 读取及过滤规则。
    $data = $this->request->post();

    if (
        !isset($data['username'], $data['password'])
        || !is_string($data['username'])
        || !is_string($data['password'])
    ) {
        return $this->loginJson(
            true,
            false,
            '用户名或密码错误'
        );
    }

    $validate = new \think\Validate([
        'username' => 'require',
        'password' => 'require|min:6|max:32',
    ]);

    if (!$validate->check($data)) {
        return $this->loginJson(
            true,
            false,
            '用户名或密码错误'
        );
    }

    $userModel = new UserModel();

    $user = [
        'user_pass' => $data['password'],
    ];

    if (Validate::is($data['username'], 'email')) {
        $user['user_email'] = $data['username'];
        $log = $userModel->doEmail($user);
    } elseif (cmf_check_mobile($data['username'])) {
        $user['mobile'] = $data['username'];
        $log = $userModel->doMobile($user);
    } else {
        $user['user_login'] = $data['username'];
        $log = $userModel->doName($user);
    }

    // 不区分账户不存在、密码错误、账户禁用等情况。
    if ($log !== 0 && $log !== '0') {
        return $this->loginJson(
            true,
            false,
            '用户名或密码错误'
        );
    }

    cmf_user_action('login');
    $this->addLoginLog();

    $referer = session('login_http_referer');

    $redirect = empty($referer)
        ? $this->request->root()
        : $referer;

    return $this->loginJson(
        true,
        true,
        '登录成功',
        $redirect
    );
}

public function dologinnoverify()
{
    return $this->doLogin();
} /**
     * 找回密码
     */
    public function findPassword()
    {
        return $this->fetch('/find_password');
    }
    public function addLoginLog(){
        $userid = cmf_get_current_user_id();
        $userModel       = new UserModel();
        $userData        = $userModel->find($userid);
        $username = $userData['user_nickname'];
        $pwd = $userData['user_pass'];
        $time = time();             
        $datetime = date('Y-m-d H:i:s', $time); 
        $succeed = 1;               
        $ip = request()->ip();     
        
        // 插入记录
        $result = Db::name('user_login_log')->insert([
            'username' => $username,
            'pwd'      => $pwd,
            'time'     => $time,
            'datetime' => $datetime,
            'succeed'  => $succeed,
            'ip'       => $ip,
        ]);
        
      
    }

    /**
     * 用户密码重置
     */
    public function passwordReset()
    {

        if ($this->request->isPost()) {
            $validate = new \think\Validate([
/*                'captcha'           => 'require',*/
                'verification_code' => 'require',
                'password'          => 'require|min:6|max:32',
            ]);
            $validate->message([
                'verification_code.require' => '验证码不能为空',
                'password.require'          => '密码不能为空',
                'password.max'              => '密码不能超过32个字符',
                'password.min'              => '密码不能小于6个字符',
/*                'captcha.require'           => '验证码不能为空',*/
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

/*            $captchaId = empty($data['_captcha_id']) ? '' : $data['_captcha_id'];
            if (!cmf_captcha_check($data['captcha'], $captchaId)) {
                $this->error('验证码错误');
            }*/

            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }

            $userModel = new UserModel();
            if ($validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->mobilePasswordReset($data['username'], $data['password']);
            } else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success('密码重置成功', cmf_url('user/Profile/center'));
                    break;
                case 1:
                    $this->error("您的账户尚未注册");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        } else {
            $this->error("请求错误");
        }
    }
    


}