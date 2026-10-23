<?php

namespace plugins\diy_admin_form\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\diy_admin_form\common\Common;
use plugins\diy_admin_form\model\PluginCodeFieldsModel;


class AdminFieldsController extends PluginAdminBaseController
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

    //字段设置页面
    public function fieldIndex(){
        $params    = $this->request->param();
        $tableName = isset($params['tablename']) ? trim($params['tablename']) : '';
        $keyword   = isset($params['keyword']) ? trim($params['keyword']) : '';
        //获取字段配置
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows($tableName);
        //如果没有字段信息,则重新配置字段
        if(empty($fields) || count($fields)<=0){
            $fields = $pcfm->fieldReset($tableName);
        }

        $this->assign('list',$fields);
        $this->assign('tablename',$tableName);
        $this->assign('keyword',$keyword);
        return $this->fetch('/field_index');

    }

    //重置字段信息,删除后重新建立
    public function fieldReset(){
        $params = $this->request->param();
        $tablename  = isset($params['tablename'])  ? trim($params['tablename']) : '';
        if(strlen($tablename)<=0){
            $this->error('数据表名错误!!');
        }
        $pcfm = new PluginCodeFieldsModel();
        $pcfm->fieldReset($tablename);
        $this->success("字段重置完成!!");
        
    }
    //字段信息排序
    public function saveSort(){
        $params = $this->request->param();
        $order = $params['order'];//array(4){[61]=>"10",[62]=>"20",[63]=>"0",[64]=>"100"}
        
        $pcfm = new PluginCodeFieldsModel();
        foreach($order as $k=>$v){
            $pcfm->updateRowGetId(intval($k),['order'=>intval($v)]);
        }
        $this->success("排序完成!");
    }
    //批量保存标识信息
    public function saveTag(){
        $params = $this->request->param();
        $tags = $params['tags'];//array(4){[61]=>"ID",[62]=>"ROLE_ID",[63]=>"RULE_NAME"}
        $pcfm = new PluginCodeFieldsModel();
        foreach($tags as $k=>$v){
            $pcfm->updateRowGetId(intval($k),['field_tag'=>trim($v)]);
        }
        $this->success("标签保存完成!");
    }
    //批量保存'列表页字段'标识信息
    public function saveUsedList(){
        $params     = $this->request->param();
        $myids      = $params['myids'];//所有IDs
        $usedList   = isset($params['used_list']) ? $params['used_list'] : [];
        //array(2){[0]=>"62",[1]=>"64"}
    
        $this->setFields($myids,$usedList,'used_list');
        $this->success("列表字段保存完成!");
    }
    //批量保存'列表页汇总字段'信息
    public function saveCountInfo(){
        $params     = $this->request->param();
        $myids      = $params['myids'];//所有IDs
        $sum        = isset($params['count_sum']) ? $params['count_sum'] : [];
        $avg        = isset($params['count_avg']) ? $params['count_avg'] : [];
        $max        = isset($params['count_max']) ? $params['count_max'] : [];
        $min        = isset($params['count_min']) ? $params['count_min'] : [];
        
        $this->setFields($myids,$sum,'used_count_sum');
        $this->setFields($myids,$avg,'used_count_avg');
        $this->setFields($myids,$max,'used_count_max');
        $this->setFields($myids,$min,'used_count_min');
        
        $this->success("汇总字段保存完成!");
    }
    //批量保存添加或编辑页字段信息
    public function saveAddEdit(){
        $params     = $this->request->param();
        $myids      = $params['myids'];//所有IDs
        $add        = isset($params['row_add']) ? $params['row_add'] : [];
        $edit       = isset($params['row_edit']) ? $params['row_edit'] : [];
    
        $this->setFields($myids,$add,'used_add');
        $this->setFields($myids,$edit,'used_edit');
       
        $this->success("添加或编辑字段保存完成!");
    }
    
    private function setFields($myids,$list,$fieldName){
        $ids0 = [];
        $ids1 = [];
        //置0的IDs
        foreach($myids as $k=>$v){
            array_push($ids0,intval($v));
        }
        //置1的IDs
        foreach($list as $k=>$v){
            array_push($ids1,intval($v));
            unset($ids0[intval($v)]);
        }
        $pcfm = new PluginCodeFieldsModel();
        $pcfm->updateRows($ids0,[$fieldName=>0]);//set to 0
        $pcfm->updateRows($ids1,[$fieldName=>1]);//set to 1
    }
    //删除列表页查询字段选项 control_type  compare_type
    public function deleteFieldControl(){
        $params = $this->request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $pcfm = new PluginCodeFieldsModel();
        $rc = $pcfm->updateRowGetId($id,['control_type'=>'','compare_type'=>'']);
        if($rc>0){
            $this->success('操作成功!');
        }else{
            $this->error('操作失败!');
        }
    }
    //选择文本框-选项   修改列表页查询字段选项 control_type  compare_type
    public function fieldControlEdit(){
        $params = $this->request->param();
        $this->fieldControl($params,'text');
        return $this->fetch('/field_control_edit');
    }
    //选择列表框-选项
    public function fieldControlList(){
        $params = $this->request->param();
        $this->fieldControl($params,'list');
        return $this->fetch('/field_control_edit_list');
    }
    //选择复选框-选项
    public function fieldControlCheck(){
        $params = $this->request->param();
        $this->fieldControl($params,'check');
        return $this->fetch('/field_control_edit_check');
    }
    private function fieldControl($params,$controlKey){
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $pcfm = new PluginCodeFieldsModel();
        $row = $pcfm->getRow($id);
        if(empty($row)){
            echo "没有找到数据!";
            die();
        }
        //控件类型列表,并设置默认选项
        $controlTypeList = Common::getControlTypeListTree($row['control_type'],$controlKey);
        $this->assign('controlType_tree',$controlTypeList);
        //复选框是否选中状态列表
        $checkStatusTree = Common::getCheckStatusTree(intval($row['ctr_check_default']));
        $this->assign('check_default_tree',$checkStatusTree);
        //字段数据匹配方式列表 第一个参数 text  datetime  number,第二个是默认选项
        $compareTypeList = Common::getCompareMethodTree($row['field_typename'],$row['compare_type']);
        $this->assign('comparetype_tree',$compareTypeList);
        $this->assign('id',$id);
        $this->assign('row',$row);
    }
    
    
    
    //保存
    public function fieldControlEditPost(){
        $params = $this->request->param();
        $id             = isset($params['id']) ? intval($params['id']) : 0;
        $control_type   = isset($params['tt']) ? trim($params['tt']) : '';
        $compare_type   = isset($params['mt']) ? trim($params['mt']) : '';
        $ctrSelectItem  = isset($params['si']) ? trim($params['si']) : '';
        $ctrSelectTableInfo = isset($params['ci']) ? trim($params['ci']) : '';
        $control_default = isset($params['cd']) ? trim($params['cd']) : '';
        $ctr_min_val     = isset($params['cm']) ? intval($params['cm']) : 0;
        $ctr_check_v1    = isset($params['c1']) ? intval($params['c1']) : 1;
        $ctr_check_v0    = isset($params['c0']) ? intval($params['c0']) : 0;
        $ctr_check_default = isset($params['cv']) ? intval($params['cv']) : 0;

        //复选框  最小值为未选中状态值
        if($control_type == 'check'){
            $ctr_min_val = $ctr_check_v0;
        }
        //文本框  默认值为空
        if(substr($control_type,0,4) == 'text'){
            $control_default = '';
        }
        //列表框  默认值为小最小效值减1
        if($control_type == 'list'){
            $control_default = $ctr_min_val-1;
        }
        


        $pcfm = new PluginCodeFieldsModel();
        $data = [
            'control_type'          => $control_type,
            'compare_type'          => $compare_type,
            'ctr_select_item'       => $ctrSelectItem,
            'ctr_select_table_info' => $ctrSelectTableInfo,
            'control_default'       => $control_default,
            'ctr_min_val'           => $ctr_min_val,
            'ctr_check_v0'          => $ctr_check_v0,
            'ctr_check_v1'          => $ctr_check_v1,
            'ctr_check_default'     => $ctr_check_default
        ];
       
        $rc = $pcfm->updateRowGetId($id,$data);
        if($rc>0){
            $this->success('操作成功!');
        }else{
            $this->error('操作失败!');
        }
    }
}
