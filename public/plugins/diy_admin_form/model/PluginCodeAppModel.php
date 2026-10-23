<?php

namespace plugins\diy_admin_form\model;

use plugins\diy_admin_form\common\Common;
use think\Model;

class PluginCodeAppModel extends Model
{
    
    //按表名查找数据行
    public function getRows(){
        return self::order('id DESC')->select();
    }
    //按表名查找数据行
    public function getRowById($id){
        $find = self::where('id',$id)->find();
        return $find;
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
    
    //删除数据行
    public function deleteRows($ids){
        return self::whereIn('id',$ids)->delete();
    }
    //删除数据行
    public function deleteRow($id){
        return self::where('id',$id)->delete();
    }
    //列表树
    public function getAppTree($defaultId){
        $list = $this->getRows();
        if(count($list)>0){
            $appData = [];
            foreach($list as $k => $v){
                array_push($appData, ['id' => $v['id'], 'title' => $v['app_name']]);
            }
            $appTree = Common::getSelectItemsTree($appData, $defaultId);
            return $appTree;
        }
    }
}