<?php
// +----------------------------------------------------------------------
// | DuanshiAd [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: duanshi <352534101@qq.com>
// +----------------------------------------------------------------------
namespace plugins\duanshi_ad\controller;

use think\Db;
use cmf\controller\PluginBaseController;
use plugins\duanshi_ad\model\PluginDuanshiAdModel;
use plugins\duanshi_ad\validate\PluginDuanshiAdValidate;

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
    /**
     * 广告插件
     * @adminMenu(
     *     'name'   => '广告管理',
     *     'parent' => 'admin/plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '广告管理',
     *     'param'  => ''
     * )
     */

    function index()
    {
        $DuanshiAdPostModel = new PluginDuanshiAdModel();
        $DuanshiAd         = $DuanshiAdPostModel->where(['delete_time' => ['eq', 0]])->select();
        $this->assign('DuanshiAd', $DuanshiAd);
        return $this->fetch('/admin_index');
    }

    function add()
    {
        return $this->fetch('/admin_add');
    }

    public function addPost()
    {
        $data = $this->request->param();
        $result = $this->validate($data, 'DuanshiAd');
        if ($result !== true) {
            $this->error($result);
        }
        $data['start_time']=strtotime($data['start_time']);
        $data['end_time']=strtotime($data['end_time']);
        $DuanshiAdPostModel = new PluginDuanshiAdModel();
        $DuanshiAdPostModel->allowField(true)->data($data, true)->save();
        $this->success("添加成功！", cmf_plugin_url('DuanshiAd://AdminIndex/index'));
    }

    public function delete()
    {
        $id             = $this->request->param('id', 0, 'intval');
        $DuanshiAdPostModel = new PluginDuanshiAdModel();
        $DuanshiAdPostModel->save(['delete_time' => time()], ['id' => $id]);
        $this->success("删除成功！", cmf_plugin_url('DuanshiAd://AdminIndex/index'));
    }

    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $DuanshiAdPostModel = new PluginDuanshiAdModel();
            $result = $DuanshiAdPostModel->save(['status' => 0], ['id' => $id]);
            if ($result) {
                $this->success("广告关闭成功！", cmf_plugin_url('DuanshiAd://AdminIndex/index'));
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
            $DuanshiAdPostModel = new PluginDuanshiAdModel();
            $result = $DuanshiAdPostModel->save(['status' => 1], ['id' => $id]);
            $this->success("广告位开启成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function edit()
    {
        $id             = $this->request->param('id');
        $DuanshiAdPostModel = new PluginDuanshiAdModel();
        $result         = $DuanshiAdPostModel->where('id', $id)->find();
        $this->assign('result', $result);
        return $this->fetch('/admin_edit');
    }

    public function editPost()
    {
        $data   = $this->request->param();
        $result = $this->validate($data, 'DuanshiAd');
        if ($result !== true) {
            $this->error($result);
        }
        if($data['start_time']>$data['end_time']){
            $this->error('结束时间不能小于开始时间');
        }
        $data['start_time']=strtotime($data['start_time']);
        $data['end_time']=strtotime($data['end_time']);
        $DuanshiAdPostModel = new PluginDuanshiAdModel();
        $DuanshiAdPostModel->allowField(true)->isUpdate(true)->data($data, true)->save();
        $this->success("保存成功！", cmf_plugin_url('DuanshiAd://AdminIndex/index'));
    }
}
