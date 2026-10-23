<?php

//**namespace app\appName\model;
namespace app\current_artists\model;

use plugins\diy_admin_form\common\Common;
use think\Model;

class UserLoginLogModel extends Model
{
    //按查询条件获取数据
    public function getDataList($fieldList,$keyName,$pageNumber = 0,$summary='',$summaryFieldName='')
    {
        $prefix = Common::getDbPrefix();
        $db = self::alias('a');
//        $db->leftJoin([
//            [$prefix.'test_sex b','b.id = a.sex_id'],
//        ]);
        $db->order("a.".trim($keyName)." DESC"); //a.id
        $fields = "a.*";
        $db->field($fields);
        //根据字段类型和匹配方法确定执行SQL
        foreach ($fieldList as $k=>$fl){
            //['fieldName'=>'field_name','fieldValue'=>'abc']
            $name   = $fl['fieldName'];//字段名称
            $value  = $fl['fieldValue'];//接收到的数据

            switch ($name){

                default:
            }
        }

        //根据分页数字标记,确定返回结果是否分页
        if($pageNumber > 0){
            $list = $db->paginate($pageNumber);//返回分页数据
        }else{
            $summary = strtolower(trim($summary));
            $summaryFieldName = trim($summaryFieldName);
            if(strlen($summary)>0 && strlen($summaryFieldName)>0){
                switch($summary){
                    case 'sum':
                        return $db->sum($summaryFieldName);
                        break;
                    case 'avg':
                        return $db->avg($summaryFieldName);
                        break;
                    case 'max':
                        return $db->max($summaryFieldName);
                        break;
                    case 'min':
                        return $db->min($summaryFieldName);
                        break;
                    default:
                        return 0;
                }
            }else{
                $list = $db->select();//反回所有数据,用于导出
            }
        }
        
        $list->each(function($item, $key){
            //$item['test'] = 0;//可以自定义输出字符
            return $item;
        });
        //var_dump($this->getLastSql());
        return $list;
    }
    
    
    //获取数据行
    public function getDataRow($Id)
    {
        return self::where('id', $Id)->find();
    }
    
    //更新一条数据行
    public function updateDataRow($Id, $data)
    {
        return self::where('id', $Id)->update($data);
    }
    
    //批量更新数据行
    public function updateDataRows($Ids, $data)
    {
        return self::where('id', 'in', $Ids)->update($data);
    }
    
    //删除一条数据行
    public function deleteDataRow($Id)
    {
        return self::where('id', $Id)->delete();
    }
    
    //批量删除数据行
    public function deleteDataRows($Ids)
    {
        return self::where('id', 'in', $Ids)->delete();
    }
    
    //添加数据行并返回ID
    public function insertDataGetId($data)
    {
        return self::insertGetId($data);
    }
    
}