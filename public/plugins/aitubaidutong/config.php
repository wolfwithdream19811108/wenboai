<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.aitu666.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <673011635@qq.com>
// +----------------------------------------------------------------------
return [
    'username' => [
        'title' => '登录账户', 
        'type'  => 'text', 
        'value' => '', 
        'tip'   => '百度统计登录账户', 
    ],
    'password' => [
        'title' => '用户密码', 
        'type'  => 'password', 
        'value' => '', 
        'tip'   => '百度统计账户密码', 
    ],
    'token' => [
        'title' => 'Token值', 
        'type'  => 'text', 
        'value' => '', 
        'tip'   => '当昨天的PV大于100后就可以获的token值使用了', 
    ],
    'siteid' => [
        'title' => '网站的ID值', 
        'type'  => 'text', 
        'value' => '', 
        'tip'   => '一般在百度统计打开对应网站后的网站地址栏里面的siteId=*，*就是你的ID值', 
    ],  
    'baiduuser'        => [// 在后台插件配置表单中的键名 ,会是config[baitutime]
        'title'   => '百度账号类别',
        'type'    => 'select',
        'options' => [//baitutime 和radio,checkbox的子选项
            '1' => '百度商业账号',// 值=>显示
            '2' => '百度站长账号',
    ],
        'value'   => '1',
        'tip'     => '默认为百度商业账号（原百度统计站长/客户账号）'
    ],    
];
