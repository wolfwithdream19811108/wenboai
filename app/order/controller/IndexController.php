<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\order\controller;


use cmf\controller\HomeBaseController;
use think\Db;

class IndexController extends HomeBaseController
{
    

    public function index()
    {
        
    }
 

    function createOrder($out_trade_no, $fee,$status = 0) {
        $data = [
            'out_trade_no' => $out_trade_no,
            'fee'          => $fee,
            'status'       => $status,
            'create_time'  => time(),
            'time_expire'  =>time()+60*5
        ];
        $result = Db::name('cmf_orders')->insert($data);
        return $result;
    }
    
    function updateOrder($id, $status, $transaction_id = null, $refund_fee = null, $refund_time = null) {
        $update = ['status' => $status, 'update_time' => time()];
        
        if ($transaction_id !== null) {
            $update['transaction_id'] = $transaction_id;
        }
        if ($refund_fee !== null) {
            $update['refund_fee'] = $refund_fee;
        }
        if ($refund_time !== null) {
            $update['refund_time'] = $refund_time;
        }
        
        $result = Db::name('cmf_orders')->where('id', $id)->update($update);
        return $result;
    }
    
    function getOrder($id) {
    $result = Db::name('cmf_orders')->where('id', $id)->find();
    return $result ? $result->toArray() : [];
    }
}
