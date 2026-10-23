<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/19
 * Time: 11:44
 */

namespace plugins\site_analysis;


use cmf\lib\Plugin;
use think\Cache;
use think\Config;
use think\Db;

class SiteAnalysisPlugin extends Plugin
{
    public $info = [
        'name'        => 'SiteAnalysis',
        'title'       => '站点统计',
        'description' => '站点统计',
        'status'      => 1,
        'author'      => 'Byk',
        'version'     => '1.0.0'
    ];
    public function install()
    {
        // TODO: Implement install() method.
        $prefix = Config::get('database.prefix');
        $charset = Config::get('database.charset');
        $sqls = [
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_site`;",
                "CREATE TABLE `{$prefix}site_analysis_site`(`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,`domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '域名',`time` int(11) NOT NULL COMMENT '开始统计时间',`count` int(11) NOT NULL DEFAULT 0 COMMENT '访客数', `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览量', PRIMARY KEY (`id`) USING BTREE)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = {$charset} COMMENT = 'Site Analysis 统计站点表';"
            ],
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_cookie`;",
                "CREATE TABLE `{$prefix}site_analysis_cookie`( `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,`time` int(11) NOT NULL COMMENT '时间',`domain_id` int(11) NOT NULL COMMENT '域名id', `cookie` varchar(255) CHARACTER SET {$charset} NOT NULL COMMENT '用户标识',`ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ip',  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '用户时间内来访次数', PRIMARY KEY (`id`) USING BTREE,INDEX `domain_id`(`domain_id`) USING BTREE) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = 'Site Analysis 统计cookie表';"
            ],
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_page`;",
                "CREATE TABLE `{$prefix}site_analysis_page`  (`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, `domain_id` int(11) NOT NULL COMMENT '域名id',`page` varchar(255) CHARACTER SET {$charset} NOT NULL COMMENT '页面地址',`title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '网页标题',`time` int(11) NOT NULL COMMENT '时间',`count` int(11) NOT NULL DEFAULT 0 COMMENT '访客数', `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览量', PRIMARY KEY (`id`) USING BTREE,INDEX `domain_id`(`domain_id`) USING BTREE) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = {$charset}  COMMENT = 'Site Analysis 统计页面表';",
            ],
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_day_data`;",
                "CREATE TABLE `{$prefix}site_analysis_day_data`  (  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,  `time` int(11) NOT NULL COMMENT '时间，年月日00:00:00',  `domain_id` int(11) NOT NULL COMMENT '域名id',  `count` int(11) NOT NULL DEFAULT 0 COMMENT '访客数',  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览量',  PRIMARY KEY (`id`) USING BTREE,INDEX `domain_id`(`domain_id`) USING BTREE) ENGINE = InnoDB  AUTO_INCREMENT = 1 CHARACTER SET = {$charset}  COMMENT = 'Site Analysis 统计每天数据表';"
            ],
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_device`;",
                "CREATE TABLE `{$prefix}site_analysis_device`  (  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,  `domain_id` int(11) NOT NULL DEFAULT 0 COMMENT '站点域名id',  `time` int(11) NOT NULL COMMENT '时间',  `device_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备类型',  `os_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '系统名称',  `browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '浏览器名称',  `browser_version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '浏览器版本',  `count` int(11) NOT NULL DEFAULT 0 COMMENT '访客数',  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览量',  PRIMARY KEY (`id`) USING BTREE,INDEX `domain_id`(`domain_id`) USING BTREE) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = {$charset}  COMMENT = 'Site Analysis 统计设备数据表';"
            ],
            [
                "DROP TABLE IF EXISTS `{$prefix}site_analysis_area`;",
                "CREATE TABLE `cmf_site_analysis_area`  (  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,  `domain_id` int(11) NOT NULL COMMENT '域名id',  `time` int(11) NOT NULL,  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '国家',  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省份',  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '城市',  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '地区',  `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '访客数',  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',  PRIMARY KEY (`id`) USING BTREE) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = {$charset} COMMENT = 'Site Analysis 地区统计'"
            ]
        ];
        foreach ($sqls as $sql){
            foreach ($sql as $sql1){
                Db::execute($sql1);
            }
        }
        return true;
    }
    public function uninstall()
    {
        // TODO: Implement uninstall() method.
        $prefix = Config::get('database.prefix');
        $sqls = [
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_site`;",
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_cookie`;",
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_page`;",
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_day_data`;",
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_device`;",
            "DROP TABLE IF EXISTS `{$prefix}site_analysis_area`;"
        ];
        foreach ($sqls as $sql){
            Db::execute($sql);
        }
        Cache::clear('site_analysis');
        return true;
    }

    public function beforeBodyEnd()
    {
        $token = session('site_analysis_token');
        if (empty($token)){
            $token          = md5(uniqid()) . md5(uniqid());
            session('site_analysis_token',$token);
        }
        $ip = get_client_ip();
        $image = cmf_plugin_url('SiteAnalysis://Image/make');
        $this->assign('ip',$ip);
        $this->assign('image',$image);
        $this->assign('site_analysis_token',$token);
        echo $this->fetch('widget');
    }
}