<?php

namespace plugins\diy_admin_form\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\diy_admin_form\common\CodeMaker;
use plugins\diy_admin_form\common\Common;
use plugins\diy_admin_form\model\PluginCodeAppModel;
use plugins\diy_admin_form\model\PluginCodeFieldsModel;
use plugins\diy_admin_form\model\PluginCodeTablesModel;
use think\Db;
use think\facade\Config;


class AdminIndexController extends PluginAdminBaseController
{
    protected function initialize()
    {
        parent::initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        $adminId = intval($adminId);
        if ($adminId === 1) {
            $this->assign("admin_id", $adminId);
        }
        else{
            echo "<h2 style='color: red;margin-top: 100px;margin-left: 100px'>:( 只有系统管理才能使用此功能！</h2>";
            die();
        }
    }
    
    /**
     * 代码生成器
     * @adminMenu(
     *     'name'   => '表单代码生成器',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 100,
     *     'icon'   => '',
     *     'remark' => '生成列表页添加页和编辑页',
     *     'param'  => ''
     * )
     */
    public function tableIndex()
    {
        //tablename
        $cm = new CodeMaker();
        $params  = $this->request->param();
        $reset   = isset($params['reset']) ? intval($params['reset']) : 0;//重置
        $keyword =isset($params['keyword']) ? trim($params['keyword']) : $cm->getCache('my_key');
        if($reset > 0){
            $cm->setCache('my_key','');
            $keyword = "";
        }
        $keyword = Common::clearHtmlStr($keyword);
        $this->assign('keyword',$keyword);
        $cm->setCache('my_key',$keyword);
        $pctm = new PluginCodeTablesModel();
        $list = Db::query('show tables');
        $tables = [];
        foreach($list as $k=>$v){
            if(is_array($v)){
                $temp = array_values($v);
                if(is_array($temp)){
                    //查询过滤
                    if(strlen($keyword)>0){
                        if(stristr($temp[0],$keyword)){
                            array_push($tables,['name'=>$temp[0],
                                'link'=>$pctm->getRowByTableName($temp[0])]);
                        }
                    }else{
                        array_push($tables,['name'=>$temp[0],
                            'link'=>$pctm->getRowByTableName($temp[0])]);
                    }
                }
            }
        }
        //var_dump($tables);
        $this->assign('tables',$tables);
        $this->assign('total',count($tables));
        return $this->fetch('/table_index');
    }
    public function tableReset(){
        $params     = $this->request->param();
        $tablename  = isset($params['tablename']) ? trim($params['tablename']) : '';
        if(strlen($tablename)<=0){
            $this->error("数据名错误!".$tablename);
        }
        $pctm = new PluginCodeTablesModel();
        $rs   = $pctm->deleteRows($tablename);
        //同时删除相关字段配置
        $pcfm = new PluginCodeFieldsModel();
        $pcfm->deleteRows($tablename);
        if($rs > 0){
            $this->success('表单重置成功!');
        }
        $this->error("表单重置失败!");
    }

    public function tableEdit(){
        $params = $this->request->param();
        $tableName = isset($params['tableName']) ? trim($params['tableName']) : '';
        $pctm = new PluginCodeTablesModel();
        $row = $pctm->getRowByTableName($tableName);
        if(empty($row)){
            $data = [
                'table_name'    => $tableName,
                'export_flag'   => 1,
                'page_flag'     => 1,
            ];
            $pctm->updateRow(0,$data);//数据不存在则添加数据行并返回
            $row = $pctm->getRowByTableName($tableName);
        }
        if(empty($row)){
            $this->error('无法获取数据!');
        }
        //应用列表树 app_id_tree
        $pcam = new PluginCodeAppModel();
        $app_tree = $pcam->getAppTree($row['app_id']);
        $this->assign('app_id_tree',$app_tree);
        //字段列表树  key_field_tree
        $keyFieldTree = $this->getFieldTree($tableName,$row['key_name']);
        $this->assign('key_field_tree',$keyFieldTree);
        //可批量置0或1的字段列表树  batch_field_tree
        $batchFieldTree = $this->getFieldTreeNoKey($tableName,trim($row['batch_setfield']),true);
        $this->assign('batch_field_tree',$batchFieldTree);


        $this->assign('row',$row);
        return $this->fetch('/table_edit');
    }
    //noKey:是否包含关键字
    public function getFieldTreeNoKey($tableName,$defaultFieldName,$showAll=false){
        //读取数据表所有字段信息,供用户选择关键字段
        $fields = Common::getFields($tableName);// Db::query("show COLUMNS FROM ".$tableName);
        if(count($fields)>0){
            $fieldsData = [];
            if($showAll){
                array_push($fieldsData,['id' => '', 'title' => '--选择字段--']);
            }
            foreach($fields as $k => $v){
                if(trim($v['Key']) !== "PRI"){
                    array_push($fieldsData, ['id' => $v['Field'], 'title' => $v['Field']]);
                }
            }
            $fieldsTree = Common::getSelectItemsTree($fieldsData, $defaultFieldName);
            return $fieldsTree;
        }
        return "";
    }
    //
    public function getFieldTree($tableName,$defaultFieldName){
        //读取数据表所有字段信息,供用户选择关键字段

        $fields = Common::getFields($tableName);// Db::query("show COLUMNS FROM ".$tableName);
        if(count($fields)>0){
            $fieldsData = [];
            foreach($fields as $k => $v){
                array_push($fieldsData, ['id' => $v['Field'], 'title' => $v['Field']]);
                //默认选中第一个关键字段
                if($defaultFieldName === "" && $v['Key'] === "PRI"){
                    $defaultFieldName = $v['Field'];
                }
            }
            $fieldsTree = Common::getSelectItemsTree($fieldsData, $defaultFieldName);
            return $fieldsTree;
        }
        return "";
    }
    public function tableEditPost(){
        $params = $this->request->param();
        //id:rs.id,export: rs.export,page:rs.page,tag:rs.tag,app:rs.app,key:rs.key
        //btdel:rs.btdel,btfield:rs.btfield,menu_name:rs.menu_name,menu_parent:rs.menu_parent,
        //menu_order:rs.menu_order,menu_icon:rs.menu_icon,
        //app_ctr_act:rs.app_ctr_act,menu_parent_url:rs.menu_parent_url
        $id     = isset($params['id']) ? intval($params['id']) : 0;
        $export = isset($params['export']) ? intval($params['export']) : 0;
        $page   = isset($params['page']) ? intval($params['page']) : 0;
        $tag    = isset($params['tag']) ? trim($params['tag']) : '';
        //$app    = isset($params['app']) ? trim($params['app']) : '';
        $appid  = isset($params['appid']) ? intval($params['appid']) : 1;
        $key    = isset($params['key']) ? trim($params['key']) : '';
        $btdel  = isset($params['btdel']) ? intval($params['btdel']) : 0;
        $btfield= isset($params['btfield']) ? trim($params['btfield']) : '';
        $menu_name= isset($params['menu_name']) ? trim($params['menu_name']) : '';
        $menu_parent= isset($params['menu_parent']) ? intval($params['menu_parent']) : 0;
        $menu_order= isset($params['menu_order']) ? intval($params['menu_order']) : 100;
        $menu_icon= isset($params['menu_icon']) ? trim($params['menu_icon']) : '';
        $menu_parent_url = isset($params['menu_parent_url']) ? trim($params['menu_parent_url']) : '';
        if($id <= 0){
            $this->error("ID错误,无法保存!");
        }
        $data = [
            'table_tag'     => $tag,
            'key_name'      => $key,
            //'app_name'      => $app,
            'app_id'        =>  $appid,
            'export_flag'   => $export,
            'page_flag'     => $page,
            'batch_delete'  => $btdel,
            'batch_setfield'=> $btfield,
            'menu_name'     => $menu_name,
            'menu_parent'   => $menu_parent,
            'menu_order'    => $menu_order,
            'menu_icon'     => $menu_icon,
            'menu_parent_url'=> $menu_parent_url
            
        ];
        
        $pcm = new PluginCodeTablesModel();
        $getId = $pcm->updateRow($id,$data);
        if($getId > 0){
            $this->success('数据保存成功!');
        }
        $this->error("数据保存失败!!");
    }

    //生成代码方法
    public function startMakeCode(){
        $params = $this->request->param();
        $targetstr = isset($params['target']) ? trim($params['target']) : '';
        $target    = explode('_',$targetstr);
        if(count($target)<=0){
            $this->error("没有指定目标任务!!");
        }

        $tableName = isset($params['tablename']) ? trim($params['tablename']) : '';
        if(strlen($tableName) <= 0){
            $this->error('数据表参数错误!');
        }

        $pctm = new PluginCodeTablesModel();
        $tbrw = $pctm->getRowByTableName($tableName);
        if(empty($tbrw)){
            $this->error('没有找到数据表的配置信息!'.$tableName);
        }
        $appName = trim($tbrw['app_name']);
        $appName = str_replace(' ','',$appName);//去除中间空格
        $appName = strtolower($appName);//应用名统一为小写字母
        if(strlen($appName)<=0){
            $this->error('应用名称未设置!');
        }
        $keyName = trim($tbrw['key_name']);
        if(strlen($keyName)<=0){
            $this->error('关键字段未设置!');
        }
        $tableTag = trim($tbrw['table_tag']);
        $tableTag = Common::clearHtmlStr($tableTag);
        $exportFlag = intval($tbrw['export_flag']);
        $pageFlag   = intval($tbrw['page_flag']);

        $myTableName = Common::getTableNameRemovePreFix($tableName);//表名去掉前缀
        //生成代码,把生成过程的消息保存到本地缓存里
        $cm = new CodeMaker();
        $cm->makeCode($target,$appName,$myTableName,$keyName,$tableTag,$exportFlag,$pageFlag);

        $this->assign('tablename',$tableName);
        $this->assign('targetIndex',in_array(2,$target) ? "checked" : '');//是否生成 列表页
        $this->assign('targetAdd',in_array(3,$target) ? "checked" : '');//是否生成 添加页
        $this->assign('targetEdit',in_array(4,$target) ? "checked" : '');//是否生成 编辑页
        $this->assign('message',cache('task_msg'));//从本地缓存读取消息
        return $this->fetch('/start_make_code');
    }


}
