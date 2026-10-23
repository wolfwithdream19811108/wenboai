<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;


use api\portal\model\UserOrderModel;
use cmf\controller\RestBaseController;
use think\Validate;
use plugins\we_chat\WeChatPlugin;
use think\Db;


class PayController extends RestBaseController
{
  

    public function __construct()
    {
        parent::__construct();
    }

   
    
    public function wxpay()
    {
    	
   		$open_id = $this->request->param('openid');
		$body_content = $this->request->param('content');
		$amount = $this->request->param('amount');
		
		$userOrderModel = new UserOrderModel();
		$orderTime = time();
		$uuid =  str_replace('.', '', trim(uniqid('yt', true), 'yt'));
		$orderId = $orderTime.$uuid;
        $findThirdPartyUser = Db::name("third_party_user")
        ->where('openid', $open_id)
        ->find();		
		
		if($findThirdPartyUser){
			$userid = $findThirdPartyUser['user_id'];
		}else{
			$this->error('用户不存在');
		}
		
		
	
        $param['open_id']  = $open_id;
        $param['body_content']  = $body_content;
        $param['order_sn']  = $orderId;
        $param['notify_url'] = 'http://www.weixin.qq.com/wxpay/pay.php';
        $param['amount'] = $amount;
        $wechatplugin = new \plugins\we_chat\WeChatPlugin();
        $result = $wechatplugin->jsapiGetOrderId($param);
        
        //成功调起统一订单后，将订单相关信息写入userorder表
        $userOrder = ["orderid" => $orderId,"ordertime" => $orderTime,"orderamount" => $amount*100, "orderstatus" => "1","userid" => $userid, "ordertype" => "4"];
		$userOrderModel->addOrder($userOrder);
		$result['orderid'] = $orderId;
        $this->success('success', $result);
	
	} 
	
	public function updateorder()
	{
   		$orderid = $this->request->param('orderid');
		$status= $this->request->param('status');
		$data['orderstatus'] = $status;
		$userOrderModel = new UserOrderModel();
		$result = $userOrderModel->editOrder($data,$orderid);
		$this->success('success', $result);

	
	}

}
