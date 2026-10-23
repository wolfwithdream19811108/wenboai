<?php

namespace plugins\simple_wxpay;
use \cmf\lib\Plugin;
use \app\order\service\OrderService;

class simple_wxpay
{
    protected $config = array(
        'mchid' => '1675718797',
        'appid' => 'wx18cda18a1accdf79',
        'apiKey' => 'a38c7bcaacd596401a201e7756ace664'
    );  
    protected $returnUrl;
    protected $orderService;
    protected $logpath = './plugins/simple_wxpay/notify_url.log';
    

    public $info = [
        'name'        => 'simple_wxpay',    //插件名字
        'title'       => '简单微信支付',   //标题
        'description' => '简单微信支付插件',   //描述
        'status'      => 0,          //关
        'author'      => '常哈哈',       //作者
        'version'     => '1.0'       //版本号
    ];

    public $hasAdmin = 0; //插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true; //安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }    
    
    
    
    public function __construct()
    {

        ///$this->config = json_decode(file_get_contents('./wxpay.json'), true);
        $this->orderService = new OrderService();
            

        
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function createJsBizPackage($totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp,$user)
    {

        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $this->config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $orderName,
            'mch_id' => $this->config['mchid'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($totalFee * 100),       //单位 转为分
            'trade_type' => 'NATIVE',
        );
       
        // 创建订单
        $order = $this->orderService->createOrder($outTradeNo,intval($totalFee * 100),$user);        
        
        

        $unified['sign'] = self::getSign($unified, $this->config['apiKey']);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }
        $codeUrl = (array)($unifiedOrder->code_url);
        if(!$codeUrl[0]) exit('get code_url error');
        $arr = array(
            "appId" => $this->config['appid'],
            "timeStamp" => $timestamp,
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
            "code_url" => $codeUrl[0],
        );
        $arr['paySign'] = self::getSign($arr, $this->config['apiKey']);
        return $arr;
    }

    public function orderquery($outTradeNo)
    {

        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mchid'],
            'out_trade_no' => $outTradeNo,
            'nonce_str' => self::createNonceStr(),
        );
        $unified['sign'] = self::getSign($unified, $this->config['apiKey']);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/orderquery', self::arrayToXml($unified));
        $queryResult = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($queryResult === false) {
            return "parse xml error";
        }
        if ($queryResult->return_code != 'SUCCESS') {
            return $queryResult->return_msg;
        }
        $trade_state = $queryResult->trade_state;
        $data['code'] = $trade_state=='SUCCESS' ? 0 : 1;
        $data['data'] = $trade_state;
        $data['msg'] = $this->getTradeSTate($trade_state);
        $data['time'] = date('Y-m-d H:i:s');
        return $data;
    }
    public function handleNotify()
    {
        $currentDate = date('Y-m-d H:i:s');
        file_put_contents($this->logpath, $currentDate . "\n", FILE_APPEND);
        // 接收微信支付通知数据
        $this->notifyData = file_get_contents("php://input");
        file_put_contents($this->logpath, "接收微信支付通知数据如下：\n" . $this->notifyData . "\n", FILE_APPEND);

        // 将接收到的XML数据转化为数组
        $this->notifyDataArr = json_decode(json_encode(simplexml_load_string($this->notifyData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        file_put_contents($this->logpath, "转换数组notifyDataArr如下：\n" . json_encode($this->notifyDataArr) . "\n", FILE_APPEND);
        
        
/*        $temp = '{"appid":"wx18cda18a1accdf79","attach":"pay","bank_type":"CMBC_CREDIT","cash_fee":"1","fee_type":"CNY","is_subscribe":"N","mch_id":"1675718797","nonce_str":"NrIrz9C4Oob2e2FK","openid":"oUcwp49ey2ODbMh9_lBc8cRZb0ow","out_trade_no":"6634be5e41919","result_code":"SUCCESS","return_code":"SUCCESS","sign":"3C26A9E89CBDC43A3A5B25BBC601275B","time_end":"20240503183729","total_fee":"1","trade_type":"NATIVE","transaction_id":"4200002168202405034500216854"}';
        
        $this->notifyDataArr = get_object_vars(json_decode($temp));
        dump($this->notifyDataArr);*/
        if ($this->notifyDataArr['return_code'] != 'SUCCESS') {
            // 通信失败，进行相应的处理
            return false;
        }
        $stringA = '';
        foreach ($this->notifyDataArr as $key => $value) {
            if ($key == 'sign') {
                continue;
            }
            // 拼装参数
            $stringA .= $key . '=' . $value . '&';
        }
        
        // 与商户API秘钥进行拼接
        $signTmp = $stringA . 'key=' . $this->config['apiKey'];
        // 签名后的32位字符
        $sign = strtoupper(md5($signTmp));
        file_put_contents($this->logpath, "拼接签名如下：\n" . $sign . "\n", FILE_APPEND);
        
        if ($sign != $this->notifyDataArr['sign']) {
            file_put_contents($this->logpath, "签名验证失败\n", FILE_APPEND);
            return false;
        }
        
        file_put_contents($this->logpath, "签名验证成功\n", FILE_APPEND);
        
        $order = $this->orderService->updateOrder($this->notifyDataArr['out_trade_no'],$this->notifyDataArr['transaction_id'],$this->notifyDataArr['openid'],$this->notifyDataArr['time_end']);  
        file_put_contents($this->logpath, $order, FILE_APPEND);
        return $this->notifyDataArr;
        
    }
    public function getTradeSTate($str)
    {
        switch ($str){
            case 'SUCCESS';
                return '支付成功';
            case 'REFUND';
                return '转入退款';
            case 'NOTPAY';
                return '未支付';
            case 'CLOSED';
                return '已关闭';
            case 'REVOKED';
                return '已撤销（刷卡支付）';
            case 'USERPAYING';
                return '用户支付中';
            case 'PAYERROR';
                return '支付失败';
        }
    }
    /**
     * curl get
     *
     * @param string $url
     * @param array $options
     * @return mixed
     */
    public static function curlGet($url = '', $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    public static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }
    /**
     * 获取签名
     */
    public static function getSign($params, $key)
    {

        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));

        return $signStr;
    }
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
