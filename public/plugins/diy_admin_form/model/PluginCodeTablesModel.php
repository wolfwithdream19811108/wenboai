<?php

namespace plugins\diy_admin_form\model;
use think\Model;

//插件数据表最好加个plugin前缀再加表名,这个类就是对应“表前缀+plugin_demo”表
class PluginCodeTablesModel extends Model
{
    //按表名查找数据行
    public function getRowByTableName($tableName){
        $find = self::where('table_name',trim($tableName))->find();
        $this->appendAppName($find);//追加appname字段
        return $find;
    }
    
    //追加appname字段
    private  function appendAppName(&$find){
        if(!empty($find)){
            $pcam = new PluginCodeAppModel();
            $row = $pcam->getRowById(intval($find['app_id']));
            if(!empty($row)){
                $find['appname'] = $row['app_name'];
                $find['app_name'] = $row['app_name'];
            }else{
                $find['appname'] = '----';
                $find['app_name'] = '----';
            }
        }
    }

    //按表名查找数据行
    public function getRowById($id){
        $find = self::where('id',$id)->find();
        $this->appendAppName($find);//追加appname字段
        return $find;
    }
    //删除数据行
    public function deleteRows($tableName){
        return self::where('table_name',$tableName)->delete();
    }

    //添加或更新行,返回Id
    public function updateRow($id,$data){
        if($id>0){
            $find = self::where('id',$id)->find();
            if($find){
                self::where('id',$id)->update($data);
                return $id;
            }else{
                return $this->insertGetId($data);
            }
        }else{
            return $this->insertGetId($data);
        }
    }

    //是否允许导出按钮
    public function allowExport($id){
        $row = $this->getRowById($id);
        $exp = isset($row['export_function']) ? intval($row['export_function']) : 0;
        if($exp > 0){
            return true;
        }
        return false;
    }
    //是否允许自定义分页行数
    public function allowPageNum($id){
        $row = $this->getRowById($id);
        $exp = isset($row['pageSeting']) ? intval($row['pageSeting']) : 0;
        if($exp > 0){
            return true;
        }
        return false;
    }
    
    
}