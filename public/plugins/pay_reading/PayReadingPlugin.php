<?php
// +----------------------------------------------------------------------
// | www.umeng123.com 2018
// +----------------------------------------------------------------------
// | Author: SL <admin@umeng123.com>
// +----------------------------------------------------------------------
namespace plugins\pay_reading;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;
use plugins\pay_reading\model\PluginPayReadingModel;
use \think\Db;
//Demo插件英文名，改成你的插件英文就行了
class PayReadingPlugin extends Plugin
{

    public $info = [
        'name'        => 'PayReading',
        'title'       => '付费阅读插件',
        'description' => '付费阅读插件',
        'status'      => 1,
        'author'      => 'umeng123.com',
        'version'     => '1.0',
        'demo_url'    => 'https://www.2bigboy.com',
        'author_url'  => 'https://www.umeng123.com'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $model = new PluginPayReadingModel();
        $prefix = config('database.prefix');
        $sql = <<<sql
        CREATE TABLE  IF NOT EXISTS `{$prefix}plugin_payreading_info` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `postid` int(11) NOT NULL,
            `__pay_is_pay` int(1) NOT NULL,
            `__pay_money` float DEFAULT 0,
            `__pay_content` text,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
sql;
        $model->execute($sql);
        $sql = <<<sql
          CREATE TABLE IF NOT EXISTS `{$prefix}plugin_payreading_payedlist` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `postid` int(11) NOT NULL,
            `uid` int(11) NOT NULL,
            `payed_money` float NOT NULL,
            `payed_time` int(11) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
sql;
        $model->execute($sql);        
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
//         $model = new PluginPayModel();
//         $prefix = config('database.prefix');
//         $sql = <<<sql
//         DROP TABLE IF EXISTS `{$prefix}plugin_payreading_info`;
// sql;
//         $model->execute($sql);
//         $sql = <<<sql
//         DROP TABLE IF EXISTS `{$prefix}plugin_payreading_payedlist`;
// sql;
//         $model->execute($sql);

        return true;//卸载成功返回true，失败false
    }

    /**
     * before_head_end
     */
    function beforeHeadEnd(){
        echo $this->fetch('head');
    }
    /**
     * portal_admin_article_edit_view_main
     */
     public function portalAdminArticleEditViewMain(){
         $request = \think\Request::instance();
         if(!empty($request->param()['id'])){
             $info = Db::name('plugin_payreading_info')->where('postid', $request->param()['id'])->find();
             $this->assign('__info', $info);
             $this->assign('__is_edit', true);
         }else $this->assign('__is_edit', false);
         echo $this->fetch('edit_widget');
     }

    /**
     * portal_admin_after_save_article
     */
    public function portalAdminAfterSaveArticle($params){
        //namespace app\portal\model PortalPostModel getPostContentAttr setPostContentAttr
        $postModel = new \app\portal\model\PortalPostModel;
        $data = $params['article'];
        $data['postid'] = $data['id'];
        unset($data['id']);
        $info = Db::name('plugin_payreading_info')->where('postid',$data['postid'])->find();
        if(empty($data['__pay_content'])) $data['__pay_content']='';
        if(empty($data['__pay_money'])) $data['__pay_money']=0;
        $data = [
            'postid'        => $data['postid'],
            '__pay_is_pay'  => $data['__pay_is_pay'],
            '__pay_content' => $postModel->getPostContentAttr($data['__pay_content']),
            '__pay_money'   => $data['__pay_money']
        ];
        if(empty($info)){            
            Db::name('plugin_payreading_info')->insert($data);
        }else{            
            Db::name('plugin_payreading_info')->where('postid',$data['postid'])->update($data);
        }
    }
    /**
     * after_content
     */
    public function afterContent($params){
        $postid = $params['object']['id'];
        //是否需要付费
        $is_pay_reading = Db::name('plugin_payreading_info')
        ->where('__pay_is_pay',1)
        ->where('__pay_money > 0')
        ->where('postid', $postid)
        ->find();
        if(empty($is_pay_reading)) $is_pay_reading = false; //不需要付费
        else $is_pay_reading = true;
        $user = cmf_get_current_user_id();
        //当前用户是否已付费
        $is_pay = Db::name('plugin_payreading_payedlist')
        ->where('uid',$user)
        ->where('postid', $postid)
        ->find();
        if(empty($is_pay) || get_current_user() == '') $is_pay = false;
        else $is_pay = true;
        $show = false;
        if($is_pay) $show = true;
        if(!$is_pay_reading) $show = true;

        $info = Db::name('plugin_payreading_info')->alias('a')
        ->join('__PORTAL_POST__ b', 'a.postid=b.id')
        ->where('a.postid',$postid)
        ->find();
        $this->assign('_show', $show);
        $this->assign('_is_pay_reading', $is_pay_reading);
        $this->assign('_info', $info);
        echo $this->fetch('after_content');
    }


}