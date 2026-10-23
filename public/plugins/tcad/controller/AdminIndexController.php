<?php
// +----------------------------------------------------------------------
// | TcAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
namespace plugins\tcad\controller;

use think\Db;
use cmf\controller\PluginBaseController;
use plugins\tcad\model\PluginTcadModel;
use plugins\tcad\validate\PluginTcadValidate;

class AdminIndexController extends PluginBaseController
{
    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            $this->error('未登录');
        }
    }

    function index()
    {
        $tcadPostModel = new PluginTcadModel();
        $tcad         = $tcadPostModel->where(['delete_time' => ['eq', 0]])->select();
        $this->assign('tcad', $tcad);
        return $this->fetch('/admin_index');
    }

    function add()
    {
        return $this->fetch('/admin_add');
    }

    public function addPost()
    {
        $data = $this->request->param();
        $result = $this->validate($data, 'Tcad');
        if ($result !== true) {
            $this->error($result);
        }
        $tcadPostModel = new PluginTcadModel();
        $tcadPostModel->allowField(true)->data($data, true)->save();
        $this->success("添加成功！", cmf_plugin_url('Tcad://AdminIndex/index'));
    }

    public function delete()
    {
        $id             = $this->request->param('id', 0, 'intval');
        $tcadPostModel = new PluginTcadModel();
        $tcadPostModel->save(['delete_time' => time()], ['id' => $id]);
        $this->success("删除成功！", cmf_plugin_url('Tcad://AdminIndex/index'));
    }

    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $tcadPostModel = new PluginTcadModel();
            $result = $tcadPostModel->save(['status' => 0], ['id' => $id]);
            if ($result) {
                $this->success("广告关闭成功！", cmf_plugin_url('Tcad://AdminIndex/index'));
            } else {
                $this->error('此广告已关闭');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $tcadPostModel = new PluginTcadModel();
            $result = $tcadPostModel->save(['status' => 1], ['id' => $id]);
            $this->success("广告位开启成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function edit()
    {
        $id             = $this->request->param('id');
        $tcadPostModel = new PluginTcadModel();
        $result         = $tcadPostModel->where('id', $id)->find();
        $this->assign('result', $result);
        return $this->fetch('/admin_edit');
    }

    public function editPost()
    {
        $data   = $this->request->param();
        $result = $this->validate($data, 'Tcad');
        if ($result !== true) {
            $this->error($result);
        }
        if($data['start_time']>$data['end_time']){
            $this->error('结束时间不能小于开始时间');
        }
        $data['start_time']=strtotime($data['start_time']);
        $data['end_time']=strtotime($data['end_time']);
        $tcadPostModel = new PluginTcadModel();
        $tcadPostModel->allowField(true)->isUpdate(true)->data($data, true)->save();
        $this->success("保存成功！", cmf_plugin_url('Tcad://AdminIndex/index'));
    }
}
