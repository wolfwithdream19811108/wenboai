<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------

namespace plugins\message_board\controller;
use plugins\message_board\model\PluginMessageBoardModel;
use cmf\controller\PluginAdminBaseController;
/**
 * Class AdminIndexController
 * @package plugins\message_board\controller
 * @adminMenuRoot(
 *     'name'   =>'右侧菜单留言板',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'wpforms',
 *     'remark' =>'右侧菜单留言板入口'
 * )
 */
class AdminIndexController extends PluginAdminBaseController
{

    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            header('HTTP/1.1 404 Not Found');
            header('Status:404 Not Found');
            $this->error('非法登录！！');
        }
    }
    /**
     * 全部留言
     * @adminMenu(
     *     'name'   => '全部留言',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '全部留言',
     *     'param'  => ''
     * )
     */
    //留言列表
    function index()
    {   
        $MessageBoardModel = new PluginMessageBoardModel();
        $datalist = $MessageBoardModel->where('cid','<',10)->order("id DESC")->paginate(50);
        $page = $datalist->render();
        $this->assign("datalist", $datalist);
        $this->assign("page", $page);
        return $this->fetch('/admin_index');
    }
    /**
     * 售后反馈
     * @adminMenu(
     *     'name'   => '售后反馈',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '售后反馈',
     *     'param'  => ''
     * )
     */
    function othermsg()
    {   
        $MessageBoardModel = new PluginMessageBoardModel();
        $datalist = $MessageBoardModel->where('cid','=',0)->order("id DESC")->paginate(50);
        $page = $datalist->render();
        $this->assign("datalist", $datalist);
        $this->assign("page", $page);
        return $this->fetch('/admin_othermsg');
    }
    /**
     * 通讯录设置
     * @adminMenu(
     *     'name'   => '通讯录设置',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '通讯录设置',
     *     'param'  => ''
     * )
     */
   function contact()
    {   
        $MessageBoardModel = new PluginMessageBoardModel();
        $datalist = $MessageBoardModel->where('cid','>=',998)->order("id ASC")->paginate(50);
        // 获取分页显示
        $page = $datalist->render();
        $this->assign("datalist", $datalist);
        $this->assign("page", $page);
        return $this->fetch('/admin_contact');
    }
    //查看留言详细信息
    function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $MessageBoardModel = new PluginMessageBoardModel();
        $detail = $MessageBoardModel->where(["id" => $id])->find();

        $MessageBoardModel->allowField(true)->save(["isread" => 1],["id" => $id]);//修改查看状态

        $this->assign("detail", $detail);
        return $this->fetch('/admin_detail');
    }

    //删除留言
    function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $MessageBoardModel = new PluginMessageBoardModel();
        $MessageBoardModel::destroy(['id' => $id]);
        $this->success('删除成功！');
    }
    function deleteAllData()
    {        
		$MessageBoardModel = new PluginMessageBoardModel();
		$MessageBoardModel::destroy(['cid' => '999']);
        $this->success('删除成功！');
    }
    //查看通讯录
    function msg()
    {
        $id = $this->request->param('id', 0, 'intval');
        $MessageBoardModel = new PluginMessageBoardModel();
        $detail = $MessageBoardModel->where(["id" => $id])->find();
        $this->assign("detail", $detail);
        return $this->fetch('/admin_msg');
    }

     //保存通讯录
    function msg_save()
    {
        $_POST = $this->request->param();
        $MessageBoardModel = new PluginMessageBoardModel();
		if(empty($_POST['id'])){
			$MessageBoardModel->allowField(true)->data($_POST, true)->save();
		}else{
			$MessageBoardModel->allowField(true)->save($_POST,['id' => $_POST['id']]);
		}
        $this->success("编辑成功！",cmf_plugin_url('MessageBoard://AdminIndex/contact'));
    }
	//CSV通讯录文件导入
    function updown(){
        // 获取表单上传文件
        $file = request()->file('namefile');
        if(empty($file)) {
            $this->error('请选择上传文件',cmf_plugin_url('MessageBoard://AdminIndex/index'));
        }
        // 移动到框架应用根目录/public/upload/ 目录下
        $info = $file->move(WEB_ROOT .'upload/default');
        //获取文件（日期/文件），$info->getFilename();
        $filename = WEB_ROOT .'upload/default/'.$info->getSaveName();
        $handle = fopen($filename,'r');
        $result_arr = array ();
        $i = 0;
        while($data_line = fgetcsv($handle ,10000)) {
            if ($i == 0) {
                $GLOBALS ['csv_key_name_arr'] = $data_line;
                $i ++;
                continue;
            }
            foreach($GLOBALS['csv_key_name_arr'] as $csv_key_num => $csv_key_name ) {
/* 				if (empty($data_line[$csv_key_num])) {
					$this->error('错误！请检查CSV格式文档是否有空行或其他错误。',cmf_plugin_url('MessageBoard://AdminIndex/contact'));
				} */
                $result_arr[$i][$csv_key_name] = $data_line[$csv_key_num];
            }
            $i++;
        }
		//csv处理
        $result = $result_arr; // 解析csv
        $len_result = count($result);
        if($len_result == 0){
            $this->error('此文件中没有数据！');
        }
		$data_arr 		= [];
        $data_values 	= [];
        for($i = 1;$i < $len_result+1;$i ++) { // 循环获取各字段值
            $arr		= array_values($result[$i]);
			$data_values = [
				'cid'		=> '999',
				'area' 		=> iconv('GBK','utf-8',$arr[1]),
				'name'		=> iconv('GBK','utf-8',$arr[2]),
				'mobile'	=> iconv('GBK','utf-8',$arr[3]),
				'tel' 		=> iconv('GBK','utf-8',$arr[4]),
				'fax' 		=> iconv('GBK','utf-8',$arr[5]),
				'email' 	=> iconv('GBK','utf-8',$arr[6]),
				'company' 	=> iconv('GBK','utf-8',$arr[7]),
				'address' 	=> iconv('GBK','utf-8',$arr[8]),
			];
			array_push($data_arr,$data_values);
        }
        fclose($handle); // 关闭指针
        // 批量插入数据表中
		$MessageBoardModel = new PluginMessageBoardModel();
		$result = $MessageBoardModel->saveAll($data_arr, false);
        if($result){
            $this->success('文件上传成功，数据已经导入！',cmf_plugin_url('MessageBoard://AdminIndex/contact'));
        }else{
            // 上传失败获取错误信息
            $this->error('上传失败');
        }
    }

}
