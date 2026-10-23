<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-03-29 10:47:42
// +----------------------------------------------
namespace plugins\third_login;
use cmf\lib\Plugin;
use think\Db;
use plugins\third_login\lib\Util;

class ThirdLoginPlugin extends Plugin{
    public $info = [
        'name'        => 'ThirdLogin',
        'title'       => '第三方登录',
        'description' => '第三方登录(v1.0.0)',
        'status'      => 1,
        'author'      => 'llr',
        'version'     => '1.0.0',
    ];

    public $hasAdmin = 1;

    /**
     * 插件安装
     */
    public function install(){
        if(!file_exists(__DIR__.'/sql/thirdl_config.sql')) {
            return false;
        }

        $sql = cmf_split_sql(__DIR__.'/sql/thirdl_config.sql', 'thirdl_');
        array_map(function($s){
            Db::execute($s);
        },$sql);
        
        return true;
    }

    /**
     * 插件卸载
     */
    public function uninstall(){
        try{
            Db::execute("DROP TABLE IF EXISTS `thirdl_config`;");
            Util::delDirAndFile('./upload/avatar', true);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }
}