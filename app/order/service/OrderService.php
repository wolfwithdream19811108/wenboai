<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\order\service;


use think\Db;


class OrderService
{
  

    function createOrder($out_trade_no, $fee,$user,$status = 0) {
        $data = [
            'out_trade_no' => $out_trade_no,
            'fee'          => $fee,
            'status'       => $status,
            'create_time'  => time(),
            'time_start'  => time(),
            'time_expire'  =>time()+60*5,
            'userid'  =>$user['id']
        ];
        $result = Db::name('orders')->insert($data);
        return $result;
    }
    function updateOrder($out_trade_no,$transaction_id,$open_id,$time_end) {
        $update = ['update_time' => time()];
        
        if ($transaction_id !== null) {
            $update['transaction_id'] = $transaction_id;
        }
        if ($open_id !== null) {
            $update['openid'] = $open_id;
        }
        if ($time_end !== null) {
            $update['time_end'] = $time_end;
        }
        
        $result = Db::name('orders')->where('out_trade_no', $out_trade_no)->update($update);
        return $result;
    }


}