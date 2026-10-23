<?php




namespace plugins\simple_wxpay;

use \cmf\lib\Plugin;
use \app\order\service\OrderService;


class WechatPayNotify
{
    private $config;
    private $notifyData;
    private $notifyDataArr;
    private $orderService;

    public function __construct()
    {
        // 加载配置
        $this->config = json_decode(file_get_contents('./wxpay.json'), true);
        $this->orderService = new OrderService();
    }

    public function handleNotify()
    {
        $currentDate = date('Y-m-d H:i:s');
        file_put_contents("notify_url.log", $currentDate . "\n", FILE_APPEND);
        // 接收微信支付通知数据
        $this->notifyData = file_get_contents("php://input");
        file_put_contents("notify_url.log", "接收微信支付通知数据如下：\n" . $this->notifyData . "\n", FILE_APPEND);

        // 将接收到的XML数据转化为数组
        $this->notifyDataArr = json_decode(json_encode(simplexml_load_string($this->notifyData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        file_put_contents("notify_url.log", "转换数组notifyDataArr如下：\n" . json_encode($this->notifyDataArr) . "\n", FILE_APPEND);
        
        if ($this->notifyDataArr['return_code'] != 'SUCCESS') {
            // 通信失败，进行相应的处理
            echo 'FAIL';
            exit(0);
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
        file_put_contents("notify_url.log", "拼接签名如下：\n" . $sign . "\n", FILE_APPEND);
        
        if ($sign != $this->notifyDataArr['sign']) {
            file_put_contents("notify_url.log", "签名验证失败\n", FILE_APPEND);
            exit(0); // 签名错误，终止程序
        }
        
        file_put_contents("notify_url.log", "签名验证成功\n", FILE_APPEND);
        // 业务逻辑处理...
        echo 'SUCCESS';
        $order = $this->orderService->updateOrder($outTradeNo,$this->notifyDataArr['transaction_id'],$this->notifyDataArr['openid'],$this->notifyDataArr['time_end']);  
    }


}


?>