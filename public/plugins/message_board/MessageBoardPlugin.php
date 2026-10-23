<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\message_board;

use cmf\lib\Plugin;
use think\Db;

class MessageBoardPlugin extends Plugin
{
    public $info = [
        'name'        => 'MessageBoard',
        'title'       => '右侧菜单留言板',
        'description' => '右侧菜单留言板，PC、移动端切换',
        'status'      => 1,
        'author'      => 'Sam',
        'version'     => '1.0'
    ];
    public $hasAdmin = 1;
    public function install()
    {
		$dbConfig = \think\facade\Config::pull('database');
		$dbSql = cmf_split_sql(WEB_ROOT . 'plugins/message_board/data/data.sql', $dbConfig['prefix'], $dbConfig['charset']);
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
        $dbConfig = \think\facade\Config::pull('database');
        $sql = "DROP TABLE IF EXISTS " . $dbConfig['prefix'] . "plugin_message_board";
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
    public function beforeBodyEnd()
    {
		$config = $this->getConfig();
        $this->assign($config);
        $this->assign('isMobile',cmf_is_mobile());
        echo $this->fetch('widget');
    }
    //before_content
        public function beforeContent($param)
    {
        if ($param=="widget_contact") {
			$datalist = Db::name('plugin_message_board')->where(["cid" => "999"])->order("id ASC")->select();
			$this->assign("datalist", $datalist);
			echo $this->fetch('widget_contact');
        };
    }

}