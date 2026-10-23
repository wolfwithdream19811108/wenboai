<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\portal\model;

use think\Db;
use api\common\model\CommonModel;

class UserOrderModel extends CommonModel
{
    //可查询字段
    protected $visible = [
        'orderid', 'ordertime', 'orderamount', 'orderstatus', 'userid','ordertype'
    ];

    //设置只读字段
    protected $readonly = ['id'];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 基础查询
     */
    protected function base($query)
    {
        $query->where('id','gt', 0);
    }

  
    /**
     * 关联 user表
     * @return $this
     */
    public function orderUser()
    {
        return $this->belongsTo('api\portal\model\UserModel', 'userid')->field('id,user_nickname');
    }


    /**
     * 增加订单记录
     * @param array $data 订单数据
     * @return $this
     */
    public function addOrder($data)
    {

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();
        return $this;
    }
    
    
    /**
     * 更新订单记录
     * @param array $data 订单数据
     * @return $this
     */
    public function editOrder($data,$orderid)
    {

        $this->isUpdate(true)->allowField(true)->save($data, ['orderid' => $orderid]);
        return $this;
    }


   

}
