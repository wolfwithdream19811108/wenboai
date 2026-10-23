<?php

namespace plugins\diy_admin_form\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\diy_admin_form\model\PluginCodeAppModel;

class AdminAppController extends PluginAdminBaseController
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
    
    public function Index()
    {
        $pcam = new PluginCodeAppModel();
        
        $this->assign('list',$pcam->getRows());
        return $this->fetch('/app_index');
    }
    

    public function edit(){
        $params = $this->request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $pcam = new PluginCodeAppModel();
        $row = $pcam->getRowById($id);
        
        if(empty($row)){
            $this->error('ID错误!无法获取数据!');
        }
        
        $this->assign('row',$row);
        return $this->fetch('/app_edit');
    }
    
    public function editPost(){
        $params = $this->request->param();
        
        $id     = isset($params['id']) ? intval($params['id']) : 0;
        $app_name= isset($params['app_name']) ? trim($params['app_name']):'';
        $menu_name= isset($params['menu_name']) ? trim($params['menu_name']):'我的菜单名称';
        $menu_order= isset($params['menu_order']) ? intval($params['menu_order']) : 50;
        $menu_icon= isset($params['menu_icon']) ? trim($params['menu_icon']) : '';
        
        if($id <= 0){
            $this->error("ID错误,无法保存!");
        }
        if(strlen($app_name)<=0){
            $this->error("应用名称必须填写!");
        }
        
        $data = [
            'app_name'      => $app_name,
            'menu_name'     => $menu_name,
            'menu_order'    => $menu_order,
            'menu_icon'     => $menu_icon,
        ];
        
        $pcam = new PluginCodeAppModel();
        $getId = $pcam->updateRow($id,$data);
        if($getId > 0){
            $this->success('数据保存成功!');
        }
        $this->error("数据保存失败!!");
    }
    
    public function delete(){
        $params = $this->request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $pcam = new PluginCodeAppModel();
        $pcam->deleteRow($id);
        $this->success("已删除:".$id);
    }
    
    public function add(){
        $params = $this->request->param();
        
        return $this->fetch('/app_add');
    }
    
    public function addPost(){
        $params = $this->request->param();
        $app_name= isset($params['app_name']) ? trim($params['app_name']):'';
        $menu_name= isset($params['menu_name']) ? trim($params['menu_name']):'我的菜单名称';
        $menu_order= isset($params['menu_order']) ? intval($params['menu_order']) : 50;
        $menu_icon= isset($params['menu_icon']) ? trim($params['menu_icon']) : '';
        
        if(strlen($app_name)<=0){
            $this->error("应用名称必须填写!");
        }
        $app_name = strtolower($app_name);
        
        $data = [
            'app_name'      => $app_name,
            'menu_name'     => $menu_name,
            'menu_order'    => $menu_order,
            'menu_icon'     => $menu_icon,
        ];
    
        $pcam = new PluginCodeAppModel();
        $getId = $pcam->updateRow(0,$data);
        if($getId > 0){
            $this->success('数据添加成功!',
                cmf_plugin_url('DiyAdminForm://AdminApp/edit',['id'=>$getId]));
        }
        $this->error("数据添加失败!!");
    }
    


}
