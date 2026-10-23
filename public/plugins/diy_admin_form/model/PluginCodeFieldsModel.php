<?php

namespace plugins\diy_admin_form\model;
use plugins\diy_admin_form\common\Common;
use think\Model;

//插件数据表最好加个plugin前缀再加表名,这个类就是对应“表前缀+plugin_demo”表
class PluginCodeFieldsModel extends Model
{
    //删除数据行
    public function deleteRows($tableName){
        return self::where('table_name',$tableName)->delete();
    }
    //删除行
    public function deleteRow($id){
        return self::where('id',$id)->delete();
    }
    //按表ID查找数据行
    public function getRow($id){
        $row = self::where('id',$id)->find();
        return $row;
    }
   
    
    //按表ID及字段名称查找数据行
    public function getRows($tableName,$fieldName=""){
        if(strlen($tableName)<=0){
            return null;
        }
        $db = self::where('table_name',$tableName);
        if(strlen($fieldName)>0){
            $db->where('field_name',trim($fieldName));
        }
        $db->order('order DESC');//排序
        $rows =  $db->select();
        $rows->each(function($item, $key){
            //添加一个字段,用于显示字段匹配说明
            if(strlen(trim($item['compare_type']))>0){
                $item['compare_desc'] = Common::compareMethodData(trim($item['compare_type']));
            }else{
                $item['compare_desc'] = '';
            }
            return $item;
        });

        return $rows;
    }

    //按表名查找数据行
    public function getRowById($id){
        $find = self::where('id',$id)->find();
        return $find;
    }

    //添加或更新行,返回Id
    public function updateRowGetId($id,$data){
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
    
    public function addRows($data){
        return $this->insertAll($data);
    }
    
    //重新添加所有字段信息,删除原有字段
    public function fieldReset($tableName){
        //删除所有字段
        $this->deleteRows($tableName);
        //重新添加字段
        $data = [];
        $fields = Common::getFields($tableName);//获取数据表的所有字段信息
        foreach($fields as $k=>$f){
            $row = [
                'table_name'        => $tableName,
                'field_name'        => $f['Field'],
                'field_datatype'    => $f['Type'],
                'field_typename'    => Common::getFieldDataTypeName($f['Type']),
                'field_tag'         => substr(strtoupper($f['Field']),0,20),
                'field_null'        => $f['Null'],
                'field_key'         => $f['Key'],
                'field_default'     => $f['Default'],
                'field_extra'       => $f['Extra'],
            ];
            array_push($data,$row);
        }
        //var_dump($data);
        $this->addRows($data);//批量添加字段
        return $this->getRows($tableName);//读取
    }
    
    //批量更新字段值
    public function updateRows($ids,$data){
        return self::whereIn('id',$ids)->update($data);
    }
    
    
    
    
}