<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-04-28 22:39:25
// +----------------------------------------------
namespace plugins\third_login\model;

use think\Model;

class BaseModel extends Model{
    /**
     * 获取列表(分页)
     * @param  array   $where    条件
     * @param  string  $order    排序
     * @param  mixed   $field    字段
     * @param  integer $listRows 每页数量
     * @param  array   $config   配置参数
     * @param  boolean $simple   是否简洁模式或者总记录数
     * @return array
     */
    public function getList($where = [], $order = 'id DESC', $field = true, $listRows = 20, $config = [], $simple = false){
        $config = array_merge($config, ['prev' => '上一页','next' => '下一页']);

        if(empty($where)) {
            return $this->order($order)->field($field)->paginate($listRows, $simple, $config);
        } else {
            return $this->where($where)->order($order)->field($field)->paginate($listRows, $simple, $config);
        }
    }

	/**
	* 查询一条记录信息
	* @param  array   $where 条件
	* @param  boolean $field 查询字段
	* @return 记录信息
	*/
	public function findOne($where = [], $field = true){
		if(empty($where)) {
            return $this->field($field)->find();
        } else {
            return $this->where($where)->field($field)->find();
        }
	}

    /**
     * 获取所有记录
     * @param  array    $where 条件
     * @param  string   $field 字段
     * @return 返回所有记录信息
     */
    public function selectAll($where = [], $field = true){
        if(empty($where)) {
            return $this->field($field)->select();
        } else {
            return $this->where($where)->field($field)->select();
        }
    }

    /**
    * 更新单条记录
    * @param  array $where         条件
    * @param  array $update_info   更新信息
    * @return
    */
    public function updateOne($where, $update_info) {
        $result = $this->save($update_info, $where);
        return $result;
    }

    /**
     * 查询记录条目
     * @param  array $where 条件
     * @return integer
     */
    public function findCount($where){
        return $this->where($where)->count();
    }

    /**
     * 获取字段的值
     * @param  array  $where 条件
     * @param  string $field 字段
     * @return 返回字段值
     */
    public function getValue($where, $field = '') {
        return $this->where($where)->value($field);
    }

    /**
     * 获取记录数
     * @param  array  $where 条件
     * @return integer
     */
    public function getCount($where = []) {
        return $this->where($where)->count();
    }
}