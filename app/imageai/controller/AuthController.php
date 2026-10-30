<?php

namespace app\imageai\controller;

use app\imageai\service\AccessService;
use app\imageai\service\AgreementService;
use app\imageai\service\TestAccountService;
use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Db;

class AuthController extends HomeBaseController
{
    public function register()
    {
        if (cmf_is_user_login()) {
            return redirect('/imageai/account');
        }
        return $this->fetch('/register');
    }

    public function registerPost()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $mobile = trim((string)$this->request->post('mobile'));
        $password = (string)$this->request->post('password');
        $code = trim((string)$this->request->post('code'));
        if (!cmf_check_mobile($mobile)) {
            $this->error('请输入正确的手机号码');
        }
        if (strlen($password) < 8 || strlen($password) > 32) {
            $this->error('密码长度应为8至32位');
        }
        if (!$this->request->post('agree_service') || !$this->request->post('agree_privacy')) {
            $this->error('请阅读并同意服务协议和隐私政策');
        }
        $captchaVerifiedAt = (int)session('imageai_sms_captcha_' . $mobile);
        if ($captchaVerifiedAt === 0 || time() - $captchaVerifiedAt > 600) {
            $this->error('请先完成滑块验证并获取短信验证码');
        }
        $codeError = cmf_check_verification_code($mobile, $code);
        if ($codeError) {
            $this->error($codeError);
        }
        if (Db::name('user')->where('mobile', $mobile)->count()) {
            $this->error('该手机号码已经注册');
        }

        Db::startTrans();
        try {
            $now = time();
            $serviceAgreement = AgreementService::get('service');
            $privacyAgreement = AgreementService::get('privacy');
            $userId = Db::name('user')->insertGetId([
                'user_type' => 2,
                'user_status' => 1,
                'mobile' => $mobile,
                'user_pass' => cmf_password($password),
                'user_login' => '',
                'user_email' => '',
                'user_nickname' => '',
                'last_login_ip' => get_client_ip(0, true),
                'create_time' => $now,
                'last_login_time' => $now,
            ]);
            Db::name('imageai_user_profile')->insert([
                'user_id' => $userId,
                'account_mode' => 'api_only',
                'register_source' => 'imageai',
                'status' => 1,
                'service_agreement_version' => $serviceAgreement['version'],
                'privacy_agreement_version' => $privacyAgreement['version'],
                'agreement_accepted_at' => $now,
                'agreement_ip' => get_client_ip(0, true),
                'create_time' => $now,
                'update_time' => $now,
            ]);
            Db::name('user_points')->insert(['userid' => $userId, 'points' => 0]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('注册失败，请稍后重试');
        }

        $user = Db::name('user')->where('id', $userId)->find();
        cmf_update_current_user($user);
        session('token', cmf_generate_user_token($userId, 'web'));
        session('imageai_sms_captcha_' . $mobile, null);
        $this->success('注册成功，请完成实名认证', '/imageai/account/real-name');
    }

    public function sendCode()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $mobile = trim((string)$this->request->post('mobile'));
        $type = (string)$this->request->post('type', 'register');
        if (!cmf_check_mobile($mobile)) {
            $this->error('请输入正确的手机号码');
        }
        if (!$this->consumeCaptchaVerification()) {
            $this->error('请重新完成滑块验证');
        }
        $exists = Db::name('user')->where('mobile', $mobile)->count() > 0;
        if ($type === 'register' && $exists) {
            $this->error('该手机号码已经注册');
        }
        if ($type === 'reset' && !$exists) {
            $this->error('该手机号码尚未注册');
        }
        $rateKey = 'imageai_sms_' . md5(get_client_ip(0, true) . '|' . $mobile);
        if (cache($rateKey)) {
            $this->error('验证码发送过于频繁，请稍后再试');
        }
        $code = cmf_get_verification_code($mobile);
        if (empty($code)) {
            $this->error('验证码发送过多，请稍后再试');
        }
        $smsParams = ['mobile' => $mobile, 'code' => $code];
        $result = hook_one('send_mobile_verification_code', $smsParams);
        if ($result === false || !empty($result['error'])) {
            $this->error($result['message'] ?? '验证码发送失败');
        }
        cmf_verification_code_log($mobile, $code, empty($result['expire_time']) ? 0 : $result['expire_time']);
        session('imageai_sms_captcha_' . $mobile, time());
        cache($rateKey, 1, 60);
        $this->success('验证码已经发送');
    }

    public function login()
    {
        if (cmf_is_user_login()) {
            return redirect('/imageai/account');
        }
        return $this->fetch('/login');
    }

    public function loginPost()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $account = trim((string)$this->request->post('account', $this->request->post('mobile', '')));
        $password = (string)$this->request->post('password');
        if (!$this->consumeCaptchaVerification()) {
            $this->error('请重新完成滑块验证');
        }
        if (cmf_check_mobile($account)) {
            $model = new UserModel();
            $result = $model->doMobile(['mobile' => $account, 'user_pass' => $password]);
            if ($result !== 0) {
                $this->error($result === 3 ? '账号已停用' : '手机号码或密码错误');
            }
        } else {
            $result = TestAccountService::login($account, $password);
            if (empty($result['ok'])) {
                $this->error($result['message'] ?? '测试账号或密码错误');
            }
        }
        $userId = cmf_get_current_user_id();
        if (!AccessService::isEnabled($userId)) {
            session('user', null);
            session('token', null);
            $this->error('ImageAI 账号已停用或已过期');
        }
        $this->success('登录成功', '/imageai/account');
    }

    public function logout()
    {
        session('user', null);
        session('token', null);
        return redirect('/imageai/login');
    }

    public function forgotPassword()
    {
        return $this->fetch('/forgot_password');
    }

    public function forgotPasswordPost()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }
        $mobile = trim((string)$this->request->post('mobile'));
        $password = (string)$this->request->post('password');
        $codeError = cmf_check_verification_code($mobile, trim((string)$this->request->post('code')));
        if (!cmf_check_mobile($mobile) || strlen($password) < 8 || strlen($password) > 32 || $codeError) {
            $this->error($codeError ?: '提交的信息不正确');
        }
        (new UserModel())->mobilePasswordReset($mobile, $password);
        $this->success('密码已重置', '/imageai/login');
    }

    public function verifyCaptcha()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误', 'data' => ['verifyResult' => false]]);
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $captchaVerifyParam = is_array($payload) && isset($payload['captchaVerifyParam'])
            ? $payload['captchaVerifyParam'] : '';
        $result = cmf_verify_aliyun_captcha($captchaVerifyParam);

        if (!empty($result['verifyResult'])) {
            session('imageai_captcha_verified_at', time());
            return json(['code' => 1, 'msg' => '验证成功', 'data' => $result]);
        }

        session('imageai_captcha_verified_at', null);
        return json(['code' => 0, 'msg' => '请重新完成滑块验证', 'data' => $result]);
    }

    private function consumeCaptchaVerification()
    {
        $verifiedAt = (int)session('imageai_captcha_verified_at');
        session('imageai_captcha_verified_at', null);
        return $verifiedAt > 0 && time() - $verifiedAt <= 300;
    }
}
