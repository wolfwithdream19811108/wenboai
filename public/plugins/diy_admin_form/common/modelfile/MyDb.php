<?php


//namespace plugins\diy_admin_form\common\modelfile;
//{NAMESPACE}

use think\Db;

class MyDb
{
    //获取数据列表树,用于填充select列表
    public static function getListTree($defaultSelectId,$showAll=true){
        //优先使用自定义数据,其次是数据表数据,最后是示例数据
        $data = self::getJsonData();
        if(is_null($data) || empty($data)){
            $data = self::getTableData();
            if(is_null($data) || empty($data)){
                $data = self::getDemoData();
            }
        }
        
        $t= '';
        if($showAll){
            $t .= "<option value='{DEFAULT}' "
                .($defaultSelectId == '{DEFAULT}' ? 'selected' : '').">所有{TITLE}</option>";
        }
        foreach ($data as $k=>$v){
            if(isset($v['id']) && isset($v['title'])){
                $t .= "<option value='".$v['id']."'  "
                    .($defaultSelectId ==  $v['id'] ? 'selected' : '')
                    .">".trim($v['title'])."</option>";
            }
        }
        return $t;
    }


    //01 自定义数据源
    private static function getJsonData(){
        $json = <<<DB
//{DBLIST}
DB;
        if(strlen(trim($json))<=20){
            return null;
        }
        return json_decode($json,true);
    }
    
    //02 数据表源
    private static function getTableData(){
        $tableInfo = <<<DB
//{TABLEDATA}
DB;
        if(strlen(trim($tableInfo))<50){
            return null;
        }
        $arr = json_decode($tableInfo,true);
        $tablename = isset($arr['tablename']) ? trim($arr['tablename']) : "";
        $valField  = isset($arr['valField'])  ? trim($arr['valField'])  : "";
        $txtField  = isset($arr['txtField'])  ? trim($arr['txtField'])  : "";
        $where     = isset($arr['where'])  ? trim($arr['where'])  : "";
        if(strlen($tablename)>0 && strlen($valField)>0 && strlen($txtField)>0){
            return self::getTableRows($tablename,$valField,$txtField,$where);
        }else{
            return null;
        }
        
    }
    
    //03 示例数据源
    private static function getDemoData(){
        $json = <<<DB
[{"id":"001","title":"示例选项1"},{"id":"002","title":"示例选项2"},{"id":"003","title":"示例选项3"}]
DB;
        return json_decode($json,true);
    }
    
    //获取数据表数据
    private static function getTableRows($tablename,$valfield,$txtfield,$where){
        $db = Db::name($tablename);
        if(strlen($where)>0){
            $db->where($where);
        }
        $rows =  $db->field($valfield.','.$txtfield)->select();
        $newArray = [];
        foreach($rows as $k=>$v){
            array_push($newArray,[
                'id'    => $v[$valfield],
                'title' => $v[$txtfield]
            ]);
        }
        return $newArray;
    }
}