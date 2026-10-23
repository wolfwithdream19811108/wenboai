<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: xiaoying <360963804@qq.com>
// +----------------------------------------------------------------------
namespace plugins\wx_pay\controller;

use cmf\controller\PluginBaseController;
// use think\Db;
use plugins\wx_pay\model\{
    Recharge,
    User
};
// use app\user\model\UserModel;
// use think\Exception;

class IndexController extends PluginBaseController
{

    //pc扫码支付
    public function qrcode()
    {
        //充值金额
        $money = $this->request->param('money', 0, 'floatval');

        require __DIR__ . '/../lib/WxPay.Api.php'; //引入微信支付

        $input        = new \WxPayUnifiedOrder();  //统一下单
        $config       = new \WxPayConfig();        //配置参数
        $out_trade_no = 'WXPAY' . date("YmdHis");  //商户订单号(自定义)
        $goods_name   = '扫码支付' . $money . '元';     //商品名称(自定义)

        $input->SetBody($goods_name);
        $input->SetAttach($goods_name);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($money * 100); //金额乘以100
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url(cmf_get_plugin_config("WxPay")['callback']); //回调地址
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456789"); //商品id

        $result = \WxPayApi::unifiedOrder($config, $input);

        if ($result['return_code'] == 'SUCCESS') {
            $url = $result["code_url"];
            $data = [
                'uid'          => cmf_get_current_user_id(),
                'total_fee'    => $money,                      //金额
                'out_trade_no' => $out_trade_no,               //微信交易单号
                'create_time'  => time(),                      //创建时间
                'type'         => 1                            //微信支付
            ];
            //创建订单
            Recharge::create($data);
            return [
                'code'         => 1,
                'url'          => $url,            //微信支付跳转链接
                'out_trade_no' => $out_trade_no,   //微信交易单号
            ];
        } else {
            return ['code' => 0, 'msg' => $result['return_msg']];
        }
    }


    //微信H5下单付款
    public function h5()
    {
        $money = $this->request->param('money', 0, 'floatval');

        require __DIR__ . '/../lib/WxPay.Api.php'; //引入微信支付

        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder"; //微信传参地址

        //1.获取调用统一下单接口所需必备参数
        $WxPayConfig      = new \WxPayConfig();
        $appid            = $WxPayConfig->GetAppId();       //微信公众号appid
        $mch_id           = $WxPayConfig->GetMerchantId();  //微信支付商户号
        $key              = $WxPayConfig->GetKey();         //自己设置的微信商家key
        $out_trade_no     = 'WXH5PAY' . date("YmdHis");     //平台内部订单号(自定义)
        $nonce_str        = MD5($out_trade_no);             //随机字符串
        $body             = 'H5支付' . $money . '元';                         //(自定义)
        $total_fee        = $money * 100;                   //付款金额，单位为分
        $spbill_create_ip = get_client_ip(0, true);         //获得用户设备IP
        $attach           = 'weixinh5';                     //附加数据（自定义，在支付通知中原样返回）
        $notify_url       = $WxPayConfig->GetNotifyUrl();   //异步回调地址，需外网可以直接访问
        $trade_type       = 'MWEB';                         //交易类型，微信H5支付时固定为MWEB

        //2.将参数按照key=value的格式，并按照参数名ASCII字典序排序生成字符串
        $signA = "appid=$appid&attach=$attach&body=$body&mch_id=$mch_id&nonce_str=$nonce_str&notify_url=$notify_url&out_trade_no=$out_trade_no&spbill_create_ip=$spbill_create_ip&total_fee=$total_fee&trade_type=$trade_type";
        //3.拼接字符串
        $strSignTmp = $signA . "&key=$key";
        //4.MD5加密后转换成大写
        $sign = strtoupper(MD5($strSignTmp));
        //5.拼接成所需XML格式
        $post_data = "<xml> 
                <appid>$appid</appid> 
                <attach>$attach</attach> 
                <body>$body</body> 
                <mch_id>$mch_id</mch_id> 
                <nonce_str>$nonce_str</nonce_str> 
                <notify_url>$notify_url</notify_url> 
                <out_trade_no>$out_trade_no</out_trade_no> 
                <spbill_create_ip>$spbill_create_ip</spbill_create_ip> 
                <total_fee>$total_fee</total_fee> 
                <trade_type>$trade_type</trade_type>
                <sign>$sign</sign> 
                </xml>";
        //6.以POST方式向微信传参，并取得微信返回的支付参数
        $dataxml = $this->httpRequest($url, 'POST', $post_data);
        $objectxml = (array) simplexml_load_string($dataxml, 'SimpleXMLElement', LIBXML_NOCDATA); //将微信返回的XML转换成数组

        // dump($objectxml);

        if ($objectxml['return_code'] == 'SUCCESS') {
            if ($objectxml['result_code'] == 'SUCCESS') //如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                $return_url = "http://www.zindex.club";
            $urls = $objectxml['mweb_url'] . '&redirect_url=' . urlencode($return_url);

            $data = [
                'uid'          => cmf_get_current_user_id(),
                'total_fee'    => $money,                      //金额
                'out_trade_no' => $out_trade_no,               //微信交易单号
                'create_time'  => time(),                      //创建时间
                'type'         => 1                            //微信支付
            ];
            //创建订单
            Recharge::create($data);

            //访问这个url  但是我在用下面的方法访问是 报错商家信息有误 所以我把url 放到视图中 跳转
            header("Location:$urls");
        }
    }





    //jsapi支付(公众号支付)
    public function jsapi()
    {
        //充值金额
        $money = $this->request->param('money', 0, 'floatval');

        require __DIR__ . '/../lib/WxPay.Api.php'; //引入微信支付
        require __DIR__ . '/../lib/WxPay.JsApiPay.php';

        //①、获取用户openid
        $config = new \WxPayConfig();   //配置参数
        $tools  = new \JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $input        = new \WxPayUnifiedOrder();
        $out_trade_no = 'WXPAY' . date("YmdHis");   //商户订单号(自定义)
        $goods_name   = 'jsapi支付' . $money . '元';  //商品名称(自定义)

        $input->SetBody($goods_name);
        $input->SetAttach($goods_name);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($money * 100); //金额乘以100
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url(cmf_get_plugin_config("WxPay")['callback']); //回调地址
        $input->SetProduct_id("123456789"); //商品id
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);


        $result = \WxPayApi::unifiedOrder($config, $input);
        // dump($result);
        //array(9) {
        //   ["appid"] => string(18) "XXXX"
        //   ["mch_id"] => string(10) "XXXX"
        //   ["nonce_str"] => string(16) "XXX"
        //   ["prepay_id"] => string(36) "XXX"
        //   ["result_code"] => string(7) "SUCCESS"
        //   ["return_code"] => string(7) "SUCCESS"
        //   ["return_msg"] => string(2) "OK"
        //   ["sign"] => string(32) "XXX"
        //   ["trade_type"] => string(5) "JSAPI"
        // }

        $jsApiParameters = $tools->GetJsApiParameters($result);
        // dump($jsApiParameters);
        // "{"appId":"XXX","nonceStr":"XXX","package":"prepay_id=XXX","signType":"MD5","timeStamp":"1592142156","paySign":"XXX"}"

        if ($result['return_code'] == 'SUCCESS') {
            // $url = $result["code_url"];
            $data = [
                'uid'          => cmf_get_current_user_id(),
                'total_fee'    => $money,                      //金额
                'out_trade_no' => $out_trade_no,               //微信交易单号
                'create_time'  => time(),                      //创建时间
                'type'         => 1                            //微信支付
            ];
            //创建订单
            Recharge::create($data);
        } else {
            dump($result);
        }

        $jsApiParameters = json_decode($jsApiParameters, "true");
        $this->assign("jsApiParameters", $jsApiParameters);
        echo $this->fetch('jsapi');
    }




    //发送http请求
    public function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false)
    {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);
        return $response;
    }




    //轮询订单
    public function payFind()
    {
        //商家订单号
        $out_trade_no = $this->request->param('out_trade_no');

        $status = (new Recharge())
            ->where('out_trade_no', $out_trade_no) //商户自定义订单号
            ->value('status');

        if ($status == 2) {
            $this->success("支付成功");
        } else {
            $this->error('支付失败');
        }
    }


    /**
     * wx支付回调处理
     */
    //$xmlObj
    // Array
    // (
    //     [appid] => XXXX
    //     [attach] => 扫码支付0.01元
    //     [bank_type] => OTHERS
    //     [cash_fee] => 1
    //     [fee_type] => CNY
    //     [is_subscribe] => Y
    //     [mch_id] => 1574582441
    //     [nonce_str] => XXXXX
    //     [openid] => XXXX
    //     [out_trade_no] => WXPAY20200124125542
    //     [result_code] => SUCCESS
    //     [return_code] => SUCCESS
    //     [sign] => 6AB5996E8983385E5575FB57B11352B034C2BFB5D544A63A5253E14778685B6E
    //     [time_end] => 20200124125552
    //     [total_fee] => 1
    //     [trade_type] => NATIVE
    //     [transaction_id] => 4200000508202001243417030127
    // )
    public function callback()
    {
        //获取微信回调的数据
        $notifiedData = file_get_contents('php://input');
        //XML格式转换
        $xmlObj = simplexml_load_string($notifiedData, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlObj = json_decode(json_encode($xmlObj), true);

        //cmh_log('pay', print_r($xmlObj, true));

        //支付成功
        if ($xmlObj['return_code'] == "SUCCESS" && $xmlObj['result_code'] == "SUCCESS") {
            foreach ($xmlObj as $k => $v) {
                if ($k == 'sign') {
                    $xmlSign = $xmlObj[$k];
                    unset($xmlObj[$k]);
                };
            }
            $sign = $this->WxSign($xmlObj);
            if ($sign === $xmlSign) {

                //改变订单状态
                (new Recharge())
                    ->where('out_trade_no', $xmlObj['out_trade_no']) //商户自定义订单号
                    ->update([
                        'transaction_id' => $xmlObj['transaction_id'],   //微信交易单号
                        'status'         => 2,                           //已支付
                        'end_time'       => time()                       //完成时间
                    ]);

                $recharge = (new Recharge())
                    ->where('out_trade_no', $xmlObj['out_trade_no']) //商户自定义订单号
                    ->field('uid,total_fee')
                    ->find();

                //增加用户余额
                (new User())
                    ->where('id', $recharge['uid'])
                    ->inc('balance', $recharge['total_fee'])
                    ->update();
                    // ->setInc('balance', $recharge['total_fee']);

                //告诉微信已经收到了
                return sprintf("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");;
            }
        }
    }


    //微信签名算法
    private function WxSign($param)
    {
        $signkey = cmf_get_plugin_config("WxPay")['key']; //秘钥
        $sign = '';
        foreach ($param as $key => $val) {
            $sign .= $key . '=' . $val . '&';
        }
        $sign .= 'key=' . $signkey;
        $sign = strtoupper(MD5($sign));
        return $sign;
    }


    //微信公众号消息模板（订单通知）（使用时自行调用）
    public function templatesms($first = "订单生成通知", $keyword1 = '', $keyword2 = '商品名称', $keyword3 = '订单号', $remark = '支付成功')
    {

        //通过公众号授权登录里的openid(必须关注过此公众号)(接收人)
        $openid = "oIbDTwaK3qHsxyZqkvYoF26qQgMw";

        //模板id
        $template_id = cmf_get_plugin_config("WxPay")['templateId'];

        $smsData = array(
            'first'    => $first,
            'keyword1' => $keyword1 ?: date("YmdHis"),
            'keyword2' => $keyword2,
            'keyword3' => $keyword3,
            'remark'   => $remark
        );

        $r = $this->sendSmsNewOrder($openid, $template_id, $smsData, "http://www.baidu.com");

        dump($r);
    }

    //获取access_token
    public function get_access_token()
    {
        $appid     = cmf_get_plugin_config("WxPay")['appid'];
        $appsecret = cmf_get_plugin_config("WxPay")['appsecret'];
        $get_access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $return = cmf_curl_get($get_access_token_url);
        if (!empty($return)) {
            $content = json_decode($return, true);
            if (is_array($content) && !empty($content) && isset($content['access_token']) && !empty($content['access_token'])) {
                $accessToken = $content['access_token'];
            }
        }
        return $accessToken;
    }


    //模板001
    public function sendSmsNewOrder($openId, $templateId, $data = array(), $url = '')
    {

        $accessToken = $this->get_access_token();

        $openId = trim($openId);
        $templateId = trim($templateId);
        // $data = $this->arrayToUtf8($data);
        $url = trim($url);

        $templateData = <<<EOF
        {
           "touser":"{$openId}",
           "template_id":"{$templateId}",
           "url":"{$url}",
           "topcolor":"#FF0000",
           "data":{
                   "first": {
                       "value":"{$data['first']}",
                       "color":"#173177"
                   },
                   "keyword1": {
                       "value":"{$data['keyword1']}",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"{$data['keyword2']}",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"{$data['keyword3']}",
                       "color":"#173177"
                   },
                   "remark": {
                       "value":"{$data['remark']}",
                       "color":"#173177"
                   }
           }
       }
EOF;

        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;

        $response = $this->httpRequest($url, 'post', $templateData);
        $returnData = json_decode($response, true);

        if (isset($returnData['errmsg']) && $returnData['errmsg'] == 'ok') {
            return true;
        } else {
            return $returnData;
        }
    }



    // 现金红包（必须先有权限）
    // array(11) {
    //     ["return_code"] => string(7) "SUCCESS"
    //     ["return_msg"] => string(12) "发放成功"
    //     ["result_code"] => string(7) "SUCCESS"
    //     ["err_code"] => string(7) "SUCCESS"
    //     ["err_code_des"] => string(12) "发放成功"
    //     ["mch_billno"] => string(13) "XXX"
    //     ["mch_id"] => string(10) "XXX"
    //     ["wxappid"] => string(18) "XXX"
    //     ["re_openid"] => string(28) "XXX"
    //     ["total_amount"] => string(3) "200"
    //     ["send_listid"] => string(31) "XXXX"
    //   }
    public function hongBao()
    {
        require __DIR__ . '/../lib/WxPay.Api.php'; //引入微信支付
        $money = 200; //最低1元，单位分
        $sender = "现金红包";

        $input        = new \WxPayHongbao();       //现金红包
        $config       = new \WxPayConfig();        //配置参数

        $input->SetMchBillno(uniqid());
        $input->SetClientIp($_SERVER['REMOTE_ADDR']);
        $input->SetReOpenid("oDBaa0lBHyLFLclvIB5ElJc-O1mE"); //更换成用户openid 不同公众号不同
        $input->SetTotalAmount($money);
        $input->SetMinValue($money);
        $input->SetMaxValue($money);
        $input->SetTotalNum(1);
        $input->SetNickName($sender);
        $input->SetSendName($sender);
        $input->SetWishing("恭喜发财");
        $input->SetActName($sender);
        $input->SetRemark($sender);
        $result = \WxPayApi::hongBao($config, $input);

        //数据自行处理
        // dump($result);

    }


    // 企业付款（必须先有权限）
    //array(9) {
    //   ["return_code"] => string(7) "SUCCESS"
    //   ["return_msg"] => array(0) {
    //   }
    //   ["mch_appid"] => string(18) "XXXX"
    //   ["mchid"] => string(10) "XXXX"
    //   ["nonce_str"] => string(32) "XXXX"
    //   ["result_code"] => string(7) "SUCCESS"
    //   ["partner_trade_no"] => string(13) "XXXX"
    //   ["payment_no"] => string(32) "XXXX"
    //   ["payment_time"] => string(19) "2020-07-08 14:01:49"
    // }
    public function qiYe()
    {
        require __DIR__ . '/../lib/WxPay.Api.php'; //引入微信支付
        $money = 200; //最低1元，单位分
        $sender = "现金红包";

        $input  = new \WxPayQiYe();    //企业付款
        $config = new \WxPayConfig();  //配置参数

        $input->SetPartnerTradeNo(uniqid());
        $input->SetSpbillCreateIp($_SERVER['REMOTE_ADDR']);
        $input->SetOpenid("oDBaa0lBHyLFLclvIB5ElJc-O1mE"); //更换成用户openid 不同公众号不同
        $input->SetCheckName("NO_CHECK");
        $input->SetAmount($money);

        $input->SetDesc($sender);
        $result = \WxPayApi::qiYe($config, $input);

        //数据自行处理
        // dump($result);

    }
}
