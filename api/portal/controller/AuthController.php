<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use cmf\controller\RestBaseController;

class AuthController extends RestBaseController
{


    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 会员文章列表
     */
    public function weixinIsopen()
    {
        
        $this->success('请求成功!',  ['status' => 1]);

    }
   
	
}
