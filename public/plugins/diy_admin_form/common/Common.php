<?php


namespace plugins\diy_admin_form\common;

use think\Db;
use think\facade\Config;

class Common
{
    public static function getFlagTree($defaultSelectId){
        $t= '';
        $defaultSelectId = intval($defaultSelectId);
        $t .= "<option value='-1' ".($defaultSelectId == -1 ? 'selected' : '').">所有状态</option>";
        $t .= "<option value='0'  ".($defaultSelectId ==  0 ? 'selected' : '').">已启用</option>";
        $t .= "<option value='1'  ".($defaultSelectId ==  1 ? 'selected' : '').">已禁用</option>";
        return $t;
    }
    
    public static function getCheckStatusTree($defaultSelectId=0){
        $t= '';
        $defaultSelectId = intval($defaultSelectId);
        $t .= "<option value='0'  ".($defaultSelectId ==  0 ? 'selected' : '').">
            [&nbsp;&nbsp;]&nbsp;未选状态</option>";
        $t .= "<option value='1'  ".($defaultSelectId ==  1 ? 'selected' : '').">
            [√]&nbsp;选中状态</option>";
        return $t;
    }
    
    // 计算身份证校验码，根据国家标准GB 11643-1999
    public static function getVerifyBit($idcard_base){
        if(strlen($idcard_base) != 17){
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4','3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++){
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }
    //根据身体证号计算性别 1男,0女
    public static function  getSexIdFromCard($idcard) {
        if(empty($idcard)) return 1;//
        if(strlen($idcard)<18){
            return 1;
        }
        $sexint = (int) substr($idcard, 16, 1);
        return $sexint % 2 === 0 ? 0 : 1;
    }
    //当前年度
    public static function getCurrentYear(){
        return intval(date('Y'));
    }
    //当日起始时间戳
    public static function getTodayTime_00_00_00(){
        return strtotime(date('Y-m-d',time()).' 00:00:00');
    }
    //当日结束时间戳
    public static function getTodayTime_23_59_59(){
        return strtotime(date('Y-m-d',time()).' 23:59:59');
    }
    
    //获取数据库数据表的统一前缀
    public static function getDbPrefix(){
        $dbConfig = Config::pull('database');
        return $dbConfig['prefix'];
    }
    //获取数据库名称
    public static function getDataBaseName(){
        $dbConfig = Config::pull('database');
        return $dbConfig['database'];
    }
    //获取后台模板文件路径
    public static function getAdminTheme(){
        $tmpConfig = Config::pull('template');
        return $tmpConfig['cmf_admin_default_theme'];
    }
    
    //时间戳  => yyyy-mm-dd
    public static function timestamp2yyyy_mm_dd($timestamp){
        return date('Y-m-d',$timestamp);
    }
    public static function yyyy_mm_dd2timestamp($yyyy_mm_dd){
        $s = trim($yyyy_mm_dd);
        if(strlen($s) != 10){
            return strtotime(date('Y-m-d',time()).' 00:00:00');
        }
        $result = strtotime($yyyy_mm_dd.' 00:00:00');
        if(is_bool($result)){
            return strtotime(date('Y-m-d',time()).' 00:00:00');
        }
        return $result;
    }
   
    
    //清理用户输入的字符串，防止非法字符
    public  static  function clearHtmlStr($content){
        $str=strip_tags($content);
        $str = preg_replace("/\t/","",$str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
        $str = preg_replace("/\r\n/","",$str);
        $str = preg_replace("/\r/","",$str);
        $str = preg_replace("/\n/","",$str);
        $str = preg_replace("/ /","",$str);
        $str = preg_replace("/  /","",$str);  //匹配html中的空格
        $str = str_replace(' ','',$str);//匹配的空格
        $str = str_replace('<','',$str);
        $str = str_replace('>','',$str);
        $str = str_replace('=','',$str);
        $str = str_replace('!','',$str);
        $str = str_replace('+','',$str);
        $str = str_replace('-','',$str);
        $str = str_replace('*','',$str);
        $str = str_replace('/','',$str);
        $str = str_replace('\\','',$str);
        
        return trim($str); //返回字符串
    }
    //公共方法:清理用户输入的字符串,只保留数字0-9
    public static function clearHtmlStr_getNumberOnly($content){
        $s = trim($content);
        $newstr = '';
        $l = strlen($s);
        for($i=0;$i<$l;$i++){
            if(ord($s[$i]) >=48 && ord($s[$i]) <=57){//0-9
                $newstr .=$s[$i];
            }
        }
        return $newstr;
    }
    
    /**
     * @desc 根据两点间的经纬度计算距离
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @return float
     */
    public static function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000;
        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;
        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }
    
    //获取字段信息
    public static function getFields($tableName){
        $fields = Db::query("show COLUMNS FROM ".$tableName);
        return $fields;
    }
    //获取指定数据表字段的名称,和字符类型时的长度
    public static function getFieldsInfo($tableName){
        $sql = 'SELECT COLUMN_NAME as field,CHARACTER_MAXIMUM_LENGTH as length ';
        $sql .=' FROM information_schema.columns WHERE TABLE_SCHEMA = "'.self::getDataBaseName().'"';
        $sql .= ' AND TABLE_NAME="'.$tableName.'"';
        $fields = Db::query($sql);
        $info = [];
        foreach ($fields as $k=>$v){
            $info[$v['field']] = intval($v['length']);
        }
        return $info;
    }
    //获取指定数据表字段的字符类型长度
    public static function getFieldLength($tableName,$fieldName){
        $fields = self::getFieldsInfo(self::getDbPrefix().$tableName);
        foreach ($fields as $k=>$v){
            if($k == $fieldName){
                return intval($v);
            }
        }
        return 0;
    }
    

    //生成select元素列表项
    //第一个参数为数组(包含id,title)
    //第二个参数设置选中项ID
    //第三个参数如果不为空,则列表顶部添加提示项
    //第四个参数代表顶部提示项ID
    public static function getSelectItemsTree($dataArray,$selectedItemValue,$topTitle='',$topId=-1){
        $t = "";
        if(strlen($topTitle)>0){
            $t = "<option value='".$topId."'>".$topTitle."</option>";
        }
        
        foreach($dataArray as $k => $v){
            $t .= "<option value='".$v['id']."' ";
            $t .= ($selectedItemValue === $v['id'] ? 'selected' : '').">";
            $t .= trim($v['title'])."</option>";
        }
        return $t;
    }
    
    //根据数据表字段类型,返回三类数据类型 字符型:text  数值型:number  日期时间型:datetime
    public static function getFieldDataTypeName($fieldDataType){
        $pos = strpos($fieldDataType,'(');//找括号位置
        if($pos === false){
            //没找到(
            $key = $fieldDataType;
        }else{
            $key = substr($fieldDataType,0,$pos);
        }
        $pos = strtolower($pos);
        
        $text = [
            'char','varchar','tinyblob','tinytext','blob','text','mediumblob','mediumtext','longblob','longtext'
        ];
        $number = [
            'tinyint','smallint','mediumint','int','integer','bigint','float','double','decimal','numeric','real'
        ];
        $datetime = [
            'date','time','year','datetime','timestamp'
        ];
        
        //binary   enum  set  varbinary   point linestring polygon geometry multipoint multilinestring
        //multipolygon  geometrycollection bit
        
        
        if(in_array($key,$text)){
            return 'text';
        }elseif(in_array($key,$number)){
            return 'number';
        }elseif(in_array($key,$datetime)){
            return 'datetime';
        }else{
            return 'text';//未定义的类型按文本类型处理
        }
    }
    
    private static function getControlTypeData($key=''){
        $data = [
            ['id'=>'text',          'title'=>'普通文本框'],
            ['id'=>'text-year',     'title'=>'年度选择文本框'],
            ['id'=>'text-date',     'title'=>'日期选择文本框'],
            ['id'=>'text-datetime', 'title'=>'日期时间文本框'],
            ['id'=>'list',          'title'=>'列表框'],
            //['id'=>'radio',         'title'=>'单选框'],
            ['id'=>'check',         'title'=>'复选框'],
        ];
        
        if(strlen($key)>0){
            $filter = [];
            for($i=0;$i<count($data);$i++){
                $pos = strpos($data[$i]['id'],$key);
                if($pos !== false){
                    array_push($filter,$data[$i]);
                }
            }
            return $filter;
        }
        return $data;
    }
    public static function compareMethodData($id = ''){
        $data = [
            ['id'=>'text-like',         'title'=>'全模糊----- field like "%key%"'],
            ['id'=>'text-like-right',   'title'=>'右模糊----- field like "key%"'],
            ['id'=>'text-like-left',    'title'=>'左模糊----- field like "%key"'],
            ['id'=>'text-equal',        'title'=>'完全相同--- field = "key"'],
            ['id'=>'text-notequal',     'title'=>'不相同----- field <> "key"'],
            ['id'=>'number-equal',      'title'=>'等于------- field =  val'],
            ['id'=>'number-notequal',   'title'=>'不等于----- field <> val'],
            ['id'=>'number-gt',         'title'=>'大于------- field >  val'],
            ['id'=>'number-egt',        'title'=>'大于或等于-- field >= val'],
            ['id'=>'number-lt',         'title'=>'小于------- field <  val'],
            ['id'=>'number-elt',        'title'=>'小于或等于-- field <= val'],
            ['id'=>'datetime-equal',    'title'=>'等于------- field =  "val"'],
            ['id'=>'datetime-notequal', 'title'=>'不等于----- field <> "val"'],

    
        ];
        if(strlen($id)<=0){
            return $data;
        }
        for($i=0;$i<count($data);$i++){
            if($data[$i]['id'] === $id){
                $str = $data[$i]['title'];
                $pos = strpos($str,'field');
                if($pos !== false){
                    return substr($str,$pos);
                }
            }
        }
        return "";
    }
    private static function getCompareMethodData($filter=''){
        //按字段类型分三类:字符型,日期时间型,数值型
        //可按要求返回某种类型数据
        $data = self::compareMethodData();
        $filterData = [];
        $filter = trim($filter);
        if(strlen($filter)>0){
            foreach($data as $k=>$v){
                $key = substr($v['id'],0,strlen($filter));
                if($key === $filter){
                    array_push($filterData,$v);
                }
            }
            return $filterData;
        }
        return $data;
    }
    //控件类型列表
    public static function getControlTypeListTree($selectedItemValue = '',$typekey=''){
        $data = self::getControlTypeData($typekey);
        $t = "";
        foreach($data as $k => $v){
            $t .= "<option value='".$v['id']."' ";
            $t .= ($selectedItemValue === $v['id'] ? 'selected' : '').">";
            $t .= trim($v['title'])."</option>";
        }
        return $t;
    }
    
    //数据匹配方法列表
    public static function getCompareMethodTree($fieldDatatype='',$selectedItemValue = ''){
        $data = self::getCompareMethodData($fieldDatatype);
        $t = "";
        foreach($data as $k => $v){
            $t .= "<option value='".$v['id']."' ";
            $t .= ($selectedItemValue === $v['id'] ? 'selected' : '').">";
            $t .= trim($v['title'])."</option>";
        }
        return $t;
    }
    //去掉表的前缀
    public static function getTableNameRemovePreFix($tableName){
        $preFix = self::getDbPrefix();//表前缀
        $pos = strpos($tableName,$preFix);
        if($pos === 0){
            return substr($tableName,strlen($preFix));
        }else{
            return $tableName;
        }
    }
    
    
    
}