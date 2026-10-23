<?php

namespace plugins\diy_admin_form;
use cmf\lib\Plugin;
use think\Db;
use think\facade\Config;

class DiyAdminFormPlugin extends Plugin
{

    public $info = [
        'name'        => 'DiyAdminForm',//插件英文名
        'title'       => '表单代码生成器',
        'description' => '按要求自动生成控制器、表模型、列表页、添加页和编辑页代码',
        'status'      => 1,
        'author'      => 'zzh',
        'version'     => '1.0',
        'demo_url'    => '#',
        'author_url'  => '#'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $dbConfig = Config::pull('database');

        $dbSql    = cmf_split_sql(WEB_ROOT . 'plugins/' . 'diy_admin_form/data/mysql.sql', $dbConfig['prefix'], $dbConfig['charset']);

        //检查合法性
        if (empty($dbConfig) || empty($dbSql)) {
            return false;
        }
        $db = Db::connect($dbConfig);
        foreach ($dbSql as $key => $sql) {
            try {
                $db->execute($sql);
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        $dbConfig = Config::pull('database');
        $prefix   = $dbConfig['prefix'];
        $t1 = $prefix.'plugin_code_fields';
        $t2 = $prefix.'plugin_code_tables';
        $t3 = $prefix.'plugin_code_app';

        $db = Db::connect($dbConfig);
    
        
        try {
            $sql = "DROP TABLE `".$t1."`;";
            $db->execute($sql);
        } catch (\Exception $e) {}
    
        try {
            $sql = "DROP TABLE `".$t2."`;";
            $db->execute($sql);
        } catch (\Exception $e) {}
    
        try {
            $sql = "DROP TABLE `".$t3."`;";
            $db->execute($sql);
        } catch (\Exception $e) {}
        
        
        return true;//卸载成功返回true，失败false
    }
}