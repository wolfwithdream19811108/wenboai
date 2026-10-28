<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

if (file_exists(CMF_ROOT . "data/conf/route.php")) {
    $runtimeRoutes = include CMF_ROOT . "data/conf/route.php";
} else {
    $runtimeRoutes = [];
}

$imageAiRoutes = [
    'imageai$'                       => 'imageai/index/index',
    'imageai/register$'              => 'imageai/auth/register',
    'imageai/register/submit$'       => 'imageai/auth/registerPost',
    'imageai/captcha/verify$'        => 'imageai/auth/verifyCaptcha',
    'imageai/send-code$'             => 'imageai/auth/sendCode',
    'imageai/login$'                 => 'imageai/auth/login',
    'imageai/login/submit$'          => 'imageai/auth/loginPost',
    'imageai/logout$'                => 'imageai/auth/logout',
    'imageai/forgot-password$'       => 'imageai/auth/forgotPassword',
    'imageai/forgot-password/submit$'=> 'imageai/auth/forgotPasswordPost',
    'imageai/account$'               => 'imageai/account/index',
    'imageai/account/api-keys$'      => 'imageai/account/apiKeys',
    'imageai/account/api-keys/create$'=> 'imageai/account/createApiKey',
    'imageai/account/api-keys/revoke$'=> 'imageai/account/revokeApiKey',
    'imageai/account/works$'         => 'imageai/account/works',
    'imageai/account/requests$'      => 'imageai/account/requests',
    'imageai/account/security$'      => 'imageai/account/security',
    'imageai/docs$'                  => 'imageai/index/docs',
    'imageai/service$'               => 'imageai/index/service',
    'imageai/privacy$'               => 'imageai/index/privacy',
    'imageai/create$'                => 'imageai/create/index',
];

return array_merge($imageAiRoutes, $runtimeRoutes);
