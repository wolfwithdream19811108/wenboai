<?php
// +----------------------------------------------------------------------
// | TcAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\tcad;
use cmf\lib\Plugin;
use think\Db;
class TcadPlugin extends Plugin
{

    public $info = [
        'name'        => 'Tcad',
        'title'       => '自定义广告',
        'description' => '自定义广告',
        'status'      => 1,
        'author'      => 'Tangchao',
        'version'     => '1.0',
        'demo_url'    => 'http://www.songzhenjiang.cn',
        'author_url'  => 'http://www.songzhenjiang.cn'
    ];

    public $hasAdmin = 1;

    public function install()
    {
        if (tcad_is_installed()) {
            return true;
        }
        $config=config('database');
        $sql = cmf_split_sql(PLUGINS_PATH . 'tcad/data/tcad.sql', $config['prefix'], $config['charset']);
        foreach ($sql as &$value) {Db::query($value);}
        @touch(PLUGINS_PATH . 'tcad/data/install.lock');
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}

function tcad_is_installed()
{
    static $cmfIsInstalled;
    if (empty($cmfIsInstalled)) {
        $cmfIsInstalled = file_exists(PLUGINS_PATH . 'tcad/data/install.lock');
    }
    return $cmfIsInstalled;
}