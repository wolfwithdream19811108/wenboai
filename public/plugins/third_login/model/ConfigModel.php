<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-03 22:30:22
// +----------------------------------------------
namespace plugins\third_login\model;

class ConfigModel extends BaseModel{
	protected $table = "thirdl_config";

    /**
     * 获取所有记录
     * @param  array    $where 条件
     * @param  string   $field 字段
     * @return 返回所有记录信息
     */
    public function selectAll($where = [], $field = true){
        if(empty($where)) {
            $list = $this->field($field)->select();
        } else {
            $list = $this->where($where)->field($field)->select();
        }

        if(empty($list)) 
        	return $list;

        return $this->convertConfig($list->toArray());

    }

    /**
     * 转换配置信息
     * @param  array $config_arr 配置信息
     * @return array
     */
    public function convertConfig($config_arr) {
    	$configs = [];

    	if(!is_array($config_arr) || empty($config_arr)) 
    		return [];

    	foreach ($config_arr as $value) {
    		$configs[$value['type']] = [
    			'id' 		 => $value['id'],
    			'app_id'	 => $value['app_id'],
    			'app_secret' => $value['app_secret'] 
    		];
    	}

    	return $configs;
    }
}