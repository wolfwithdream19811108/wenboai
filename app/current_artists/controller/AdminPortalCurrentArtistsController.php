<?php

namespace app\current_artists\controller;

use app\current_artists\model\PortalCurrentArtistsModel;
use plugins\diy_admin_form\common\MyExcel;
use cmf\controller\AdminBaseController;
use plugins\diy_admin_form\common\Common;

class AdminPortalCurrentArtistsController extends AdminBaseController
{
    /**
     * 人才管理
     * @adminMenu(
     *     'name'   => '人才管理',
     *     'parent' => 'current_artists/AdminCurrentArtists/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '人才管理',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Index(){
        $params = $this->request->param();
        //保持查询状态
        $fields = $this->getFieldsInfo($params);
        cache('index_fields',$fields);//把字段信息存入缓存,用于数据导出
        //分页数
        $pageNumber = isset($params['page_num']) ?  intval($params['page_num']) : $this->getPageCustomNumber();
        if($pageNumber<=0){$pageNumber = 10;}
        $this->assign("page_num",$pageNumber);
        $this->setPageCustomNumber($pageNumber);
        //获取数据
        $mydatamodel = new PortalCurrentArtistsModel();
        $listpage = $mydatamodel->getDataList($fields,'id',$pageNumber);
        
        $listpage->appends($params);
        $this->assign('list',$listpage);
        $this->assign('page', $listpage->render());//单独提取分页出来
        //汇总
        $this->assign('total',$listpage->total());
        
       
        return $this->fetch();
    }
    //字段设置
    private function getFieldsInfo($params){
        $fields = [];


        return $fields;
    }
    
    /**
     * 编辑
     * @adminMenu(
     *     'name'   => '编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(){
        $params = $this->request->param();
        $id   = isset($params['id']) ? intval($params['id']) : 0;
        if($id<=0){$this->error('ID错误!');}
        $mm = new PortalCurrentArtistsModel();
        $row = $mm->getDataRow($id);
        //填充列表项

        //填充复选框

        $this->assign('post',$row);
        return $this->fetch();
    }
    /**
     * 编辑提交
     * @adminMenu(
     *     'name'   => '编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editPost(){
        $params = $this->request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        if($id<=0){ $this->error('ID错误!');}
        unset($params['id']);
        $data = $this->checkInput($params,'edit');
        if(empty($data)){
            $this->error("数据错误!");
        }
        
        $mm = new PortalCurrentArtistsModel();
        $result = $mm->updateDataRow($id,$data);
    
        if($result){
            $this->success('保存成功!',
                url('AdminPortalCurrentArtists/edit', ['id' => $id]));
        }else{
            $this->error('保存失败!');
        }
    }
    /**
     * 添加
     * @adminMenu(
     *     'name'   => '添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(){
        //填充列表项

        //填充复选框

        return $this->fetch();
    }
    /**
     * 添加提交
     * @adminMenu(
     *     'name'   => '添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost(){
        $params = $this->request->param();
        $data = $this->checkInput($params,'add');
        if(empty($data)){
            $this->error("出现错误!");
        }

        $mm = new PortalCurrentArtistsModel();
        $id =  $mm->insertDataGetId($data);
    
        if($id){
            $this->success('添加成功!',
                url('AdminPortalCurrentArtists/edit', ['id' => $id]));
        }else{
            $this->error('添加失败!');
        }
    }
    //检测用户输入的数据
    private function checkInput($params,$type='add'){
        $data = [];
        //这里可对用户提交的数据进行判断,例如:
        //$title = isset($params['title']) ? trim($params['title']) : '';
        //if(strlen($title)<=0){
//            $this->error('标题必须填写!!');
//        }
        
        if($type === 'add'){
            //添加数据
			$data['id']		= isset($params['id']) ? $params['id'] : '';
			$data['name']		= isset($params['name']) ? $params['name'] : '';
			$data['artistid']		= isset($params['artistid']) ? $params['artistid'] : '';
			$data['avatar']		= isset($params['avatar']) ? $params['avatar'] : '';
			$data['works_num']		= isset($params['works_num']) ? $params['works_num'] : '';
			$data['hasavatar']		= isset($params['hasavatar']) ? $params['hasavatar'] : '';
			$data['ethnicity']		= isset($params['ethnicity']) ? $params['ethnicity'] : '';
			$data['category']		= isset($params['category']) ? $params['category'] : '';
			$data['works_name']		= isset($params['works_name']) ? $params['works_name'] : '';
			$data['title']		= isset($params['title']) ? $params['title'] : '';
			$data['honor']		= isset($params['honor']) ? $params['honor'] : '';
			$data['honor2']		= isset($params['honor2']) ? $params['honor2'] : '';
			$data['birth_date']		= isset($params['birth_date']) ? $params['birth_date'] : '';
			$data['birth_place']		= isset($params['birth_place']) ? $params['birth_place'] : '';
			$data['province']		= isset($params['province']) ? $params['province'] : '';
			$data['desc']		= isset($params['desc']) ? $params['desc'] : '';
			$data['life']		= isset($params['life']) ? $params['life'] : '';
			$data['character']		= isset($params['character']) ? $params['character'] : '';
			$data['achievement']		= isset($params['achievement']) ? $params['achievement'] : '';
			$data['comments']		= isset($params['comments']) ? $params['comments'] : '';
			$data['weight']		= isset($params['weight']) ? $params['weight'] : '';

        }else{
            //编辑数据
			$data['id']		= isset($params['id']) ? $params['id'] : '';
			$data['name']		= isset($params['name']) ? $params['name'] : '';
			$data['artistid']		= isset($params['artistid']) ? $params['artistid'] : '';
			$data['avatar']		= isset($params['avatar']) ? $params['avatar'] : '';
			$data['works_num']		= isset($params['works_num']) ? $params['works_num'] : '';
			$data['hasavatar']		= isset($params['hasavatar']) ? $params['hasavatar'] : '';
			$data['ethnicity']		= isset($params['ethnicity']) ? $params['ethnicity'] : '';
			$data['category']		= isset($params['category']) ? $params['category'] : '';
			$data['works_name']		= isset($params['works_name']) ? $params['works_name'] : '';
			$data['title']		= isset($params['title']) ? $params['title'] : '';
			$data['honor']		= isset($params['honor']) ? $params['honor'] : '';
			$data['honor2']		= isset($params['honor2']) ? $params['honor2'] : '';
			$data['birth_date']		= isset($params['birth_date']) ? $params['birth_date'] : '';
			$data['birth_place']		= isset($params['birth_place']) ? $params['birth_place'] : '';
			$data['province']		= isset($params['province']) ? $params['province'] : '';
			$data['desc']		= isset($params['desc']) ? $params['desc'] : '';
			$data['life']		= isset($params['life']) ? $params['life'] : '';
			$data['character']		= isset($params['character']) ? $params['character'] : '';
			$data['achievement']		= isset($params['achievement']) ? $params['achievement'] : '';
			$data['comments']		= isset($params['comments']) ? $params['comments'] : '';
			$data['weight']		= isset($params['weight']) ? $params['weight'] : '';

        }
        
        return $data;
    }
    /**
     * 数据导出
     * @adminMenu(
     *     'name'   => '数据导出',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '数据导出',
     *     'param'  => ''
     * )
     */
    public function export(){
        //获取数据
        $fields = cache("index_fields");//读取缓存中的字段信息
        $mydatamodel = new PortalCurrentArtistsModel();
        $data = $mydatamodel->getDataList($fields,'id',0);
        if(empty($data)){
            $this->error('没有可导出的数据!');
        }
        $this->exportData($data);
    }
    //数据导出,请首先在public\upload\excel_model\文件夹建立demo.xlsx文件模板
    private function exportData($data){
        $modelfilename = 'demo.xlsx';
        $outFileName = '示例表_'.date("Y-m-d-His").".xlsx";
        //在固定位置输出数据
        $fixData  = [];//['A3'=>100,'D6'=>"没有一个"];
        $dataRows = [];//循环输出的数据
        $mergeCell= [];//合并单元格,指定区域,例如 ['G4:J6','K10:R11'];
        $startRow = 3;//指定循环输出的起始行
        $startIndex =  0;//序号
        foreach ($data as $k=>$v){
            $startRow +=1;
            $startIndex +=1;
            //人员信息
            $dr = [
                'A'.$startRow    => "示例数据".$startRow,//$data['field_name'],
                'B'.$startRow    => "示例数据".$startRow,
                'C'.$startRow    => "示例数据".$startRow,
                'D'.$startRow    => "示例数据".$startRow,
            ];
            array_push($dataRows,$dr);
        }
        
        $result = MyExcel::export2excelFile($modelfilename,$outFileName,
            $fixData,$dataRows,'A4:H'.$startRow,$mergeCell);
        if(!empty($result)){
            $this->error($result);
        }
    }
    
    /**
     * 禁用或启用
     * @adminMenu(
     *     'name'   => '禁用或启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '禁用或启用',
     *     'param'  => ''
     * )
     */
    public function setflag(){
        $fieldName = "";
        $params = $this->request->param();
        $f = isset($params['f']) ? intval($params['f']) : -1;
        if($f>=0 && $f<=1){
            $mm = new PortalCurrentArtistsModel();
            $ids = $this->request->param('ids/a');
            $mm->updateDataRows($ids,[$fieldName => $f]);
            $this->success("操作成功！");
        }
        $this->error('操作失败');
    }
    
    /**
     * 批量删除删除
     * @adminMenu(
     *     'name'   => '批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '批量删除',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function deleteRows(){
        $mm = new PortalCurrentArtistsModel();
        $ids = $this->request->param('ids/a');
        //$result = $mm->updateDataRows($ids,['field_name' => 1]);//更新数据行,逻辑删除
        $result = $mm->deleteDataRows($ids);//物理删除数据行,无法恢复
        if($result){
            $this->success("删除成功！");
        }
        $this->error('删除失败');
    }
    //获取缓存的页行数
    private function getPageCustomNumber(){
        //自定义分页数,从缓存中读取
        $page_cache = intval(cache('page_custom_number'));
        if($page_cache <= 0){
            $this->setPageCustomNumber(10);//保存
            return 10 ;//默认每页显示10行数据
        }
        return $page_cache;
    }
    //保存页行数到缓存
    private function setPageCustomNumber($pageNumber){
        $pageNumber = intval($pageNumber);
        if($pageNumber <= 0){
            $pageNumber = 10;
        }
        cache('page_custom_number',$pageNumber);
    }

    
    
    
}