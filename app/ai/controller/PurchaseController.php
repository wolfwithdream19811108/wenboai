<?php

namespace app\ai\controller;


use cmf\controller\HomeBaseController;
use \plugins\simple_wxpay\simple_wxpay;
use app\user\model\UserPointsModel;
use think\Db;

class PurchaseController extends HomeBaseController
{
    /**
     * 文章详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */





    public function index()
    {

        if (cmf_is_user_login()) {
            $user = cmf_get_current_user();
            $userid = cmf_get_current_user_id();
        } else {
            $this->error("此用户未登录!请先登陆再支付");
        }
        $userid = cmf_get_current_user_id();
        $UserPointsModel = new UserPointsModel();
        $currentPoints = $UserPointsModel->getUserPoints($userid); 
        $this->assign('currentPoints', $currentPoints);
        return $this->fetch("/purchase");
    }
    public function wxPay()
    {

        if (cmf_is_user_login()) {
            $user = cmf_get_current_user();
            $userid = cmf_get_current_user_id();
        } else {
            $this->error("此用户未登录!请先登陆再支付");
        }
 
        
        $params = $this->request->get();
        $payAmount = isset($params['payAmount']) ? strval($params['payAmount']) : null;
        $payAmount = floatval($payAmount);          //付款金额，单位:元
        $wxPay = new simple_wxpay();
        $outTradeNo = $userid."_".time()."_".uniqid();     //你自己的商品订单号
        
        $orderName = "CART_AIGC平台积分充值：";//订单标题
        $notifyUrl = 'https://www.hongmengyishu.com/ai/purchase/notify';     //付款成功后的异步回调地址(不要有问号)
        $returnUrl = 'https://ai.hongmengyishu.com';     //付款成功后页面跳转的地址
        $payTime = time();      //付款时间
        
        
        $wxPay->setReturnUrl($returnUrl);
        $arr = $wxPay->createJsBizPackage($payAmount,$outTradeNo,$orderName,$notifyUrl,$payTime,$user);
        //生成二维码
        $url = 'https://api.qrserver.com/v1/create-qr-code/?data='.$arr['code_url'];


        $this->assign('outTradeNo', $outTradeNo);
        $this->assign('payAmount', $payAmount);
        $this->assign('url', $url);
        $this->assign('returnUrl', $returnUrl);
        return $this->fetch("/wxpay");
    }
 
    public function queryorder()
    {
        $params = $this->request->get();
        $outTradeNo = isset($params['tradeno']) ? strval($params['tradeno']) : null;
        $wxPay = new simple_wxpay();
        $return = $wxPay->orderquery($outTradeNo);
        return(json_encode($return));

    }
    public function notify()
    {
        $wxPay = new simple_wxpay();
        $notifyData = $wxPay->handleNotify();
        $total_fee = $notifyData['total_fee'];
        $outTradeNo = $notifyData['out_trade_no'];
        $resultCode = $notifyData['result_code'];
        $transaction_id = $notifyData['transaction_id'];
        file_put_contents('notify.log', 'outTradeNo:'.$outTradeNo."\n", FILE_APPEND);
        $parts = explode("_", $outTradeNo);

        // $userid是第一个下划线以前的字符串
        $userid = $parts[0];
        
        $result = Db::name("orders")->field('handle_notify')->where('transaction_id', $transaction_id)->find();
        if($result&&$result['handle_notify']){
            //已经处理过了，不再重复处理
        }else{
            if($resultCode=='SUCCESS'){
                //充值成功
                
                file_put_contents('notify.log', 'userid:'.$userid."\n", FILE_APPEND);
                $currentPoints = Db::name("user_points")->field('points')->where('userid', $userid)->find();
                if(!$currentPoints){
                    //没有查询到
                    $result = Db::name("user_points")->insert([
                    'userid'     => $userid,
                    'points' => $total_fee
                ]);
                }else{
                    $currentPoints = $currentPoints['points'] + $total_fee;
                    $update['points'] = $currentPoints;
                    file_put_contents('notify.log', '$currentPoints:'.$currentPoints."\n", FILE_APPEND);
                    $result = Db::name('user_points')->where('userid', $userid)->update($update);
                }
                
    
                
            }else{
                //充值不成功
                
            }  
            //更新notify状态
            $notifyStatus['handle_notify'] = 1;
            Db::name('orders')->where('transaction_id', $transaction_id)->update($notifyStatus);
        }

 

    }

   
}


?>

