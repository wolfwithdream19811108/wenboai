<?php
// +----------------------------------------------------------------------
// | NotFound [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <admin@ytecn.com>
// +----------------------------------------------------------------------
namespace plugins\not_found;
use cmf\lib\Plugin;
class NotFoundPlugin extends Plugin
{

    public $info = [
        'name'        => 'NotFound',
        'title'       => '404插件',
        'description' => '404插件',
        'status'      => 1,
        'author'      => 'Tangchao',
        'version'     => '1.1',
        'demo_url'    => 'https://www.ytecn.com',
        'author_url'  => 'https://www.ytecn.com'
    ];

    public $hasAdmin = 0;

    public function install()
    {
        $nofound = PLUGINS_PATH."not_found/404.tpl";
        cmf_set_dynamic_config(['exception_tmpl' => $nofound]);
        return true;
    }

    public function uninstall()
    {
        $nofound = THINK_PATH . 'tpl' . DS . 'think_exception.tpl';
        cmf_set_dynamic_config(['exception_tmpl' => $nofound]);
        return true;
    }
}