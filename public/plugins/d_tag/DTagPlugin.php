<?php
// +----------------------------------------------------------------------
// | d_tag [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 DaliyCode All rights reserved.
// +----------------------------------------------------------------------
// | Author: DaliyCode <3471677985@qq.com> <author_url:dalicode.com>
// +----------------------------------------------------------------------
namespace plugins\d_tag;

use cmf\lib\Plugin;
use think\Db;

/**
 * MobileCodeDemoPlugin
 */
class DTagPlugin extends Plugin {

    public $info = [
        'name'        => 'DTag',
        'title'       => '文章热门标签展示',
        'description' => '文章热门标签展示',
        'status'      => 1,
        'author'      => 'daliycode',
        'version'     => '1.0',
        'demo_url'    => 'http://www.daliycode.com',
        'author_url'  => 'http://www.daliycode.com',
    ];

    public $has_admin = 0; //插件是否有后台管理界面

    public function install() //安装方法必须实现
    {
        return true; //安装成功返回true，失败false
    }

    public function uninstall() //卸载方法必须实现
    {
        return true; //卸载成功返回true，失败false
    }

    //实现的before_right_sidebar_end钩子方法
    public function beforeRightSidebarEnd($param) {

        $config = $this->getConfig();
        if ($config['tag_type'] == 1) {
			
            $num  = (int) $config['tag_show_num'] > 0 ? (int) $config['tag_show_num'] : 30;
            $list = Db::name('portal_tag_post')->alias('p')
                ->join('__PORTAL_TAG__ t', 'p.tag_id=t.id', 'left')
                ->field('p.tag_id,t.name ,count(p.tag_id)as num')
                ->where('t.status=1')
                ->group('p.tag_id')
                ->order('num desc')
                ->limit($num)
                ->select();

            $this->assign('list', $list);
            echo $this->fetch('widget');
        }
    }

}