<?php
// +----------------------------------------------------------------------
// | DuanshiAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: duanshi <352534101@qq.com>
// +----------------------------------------------------------------------
namespace plugins\duanshi_ad;
use cmf\lib\Plugin;
use think\Db;
class DuanshiAdPlugin extends Plugin
{

    public $info = [
        'name'        => 'DuanshiAd',
        'title'       => '自定义广告',
        'description' => '自定义广告',
        'status'      => 1,
        'author'      => 'duanshi',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => ''
    ];

    public $hasAdmin = 0;

    public function install()
    {
        $sys_config = config();
        $dbConfig = $sys_config['database'];
        $dbSql = cmf_split_sql(WEB_ROOT . 'plugins/'  . 'duanshi_ad/data/duanshi_ad.sql', $dbConfig['prefix'], $dbConfig['charset']);
        //检查合法性
        if (empty($dbConfig) || empty($dbSql)) {
            $this->error("非法安装!");
        }
        $db = Db::connect($dbConfig);

        foreach ($dbSql as $key => $sql) {
            try {
                $db->execute($sql);

            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }

    public function uninstall()
    {
        //读取数据库配置内容
        $sys_config = Config();
        $dbConfig = $sys_config['database'];
        $sql = "DROP TABLE IF EXISTS " . $dbConfig['prefix'] . "plugin_duanshi_ad";

        //检查合法性
        if (empty($dbConfig) || empty($sql)) {
            $this->error("非法安装!");
        }
        $db = Db::connect($dbConfig);

        try {
            $db->execute($sql);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}