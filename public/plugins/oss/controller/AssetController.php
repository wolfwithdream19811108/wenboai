<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\oss\controller;

use cmf\controller\PluginBaseController;
use think\Validate;
use plugins\oss\lib\Oss;
use think\Db;
use think\Log;
use DateTime;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Sts\Sts;

class AssetController extends PluginBaseController
{
    public function getUrl()
    {

        $oss = new Oss([]);

        $fileHash = $this->request->param('file_hash');
        $filname  = $this->request->param('filename');
        $fileType = $this->request->param('filetype');

        $suffix = cmf_get_file_extension($filname);

        $file = $fileHash.".{$suffix}";

        $previewUrl = $fileType == 'image' ? $oss->getPreviewUrl($file) : $oss->getFileDownloadUrl($file);
        $url        = $fileType == 'image' ? $oss->getImageUrl($file, 'watermark') : $oss->getFileDownloadUrl($file);

        return $this->success('success', null, [
            'url'         => $url,
            'preview_url' => $previewUrl,
            'filepath'    => $file
        ]);
    }

    protected function gmt_iso8601($time)
    {
        $dtStr      = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos        = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    public function getConfig()
    {
         $logType = 'webuploader_getConfig';
        Log::init([
            'type'  =>  'File',
            'path'  =>  RUNTIME_PATH.'/log/webuploader_getConfig/',
            'apart_level'   =>  [$logType,'exception'],
            'file_size'     =>2097152,
            'max_files'     => 30
        ]);       

        
        
        
        $app = $this->request->param('app','');
        $filename = $this->request->param('filename','');
        $userId = cmf_get_current_admin_id();
        $userId = $userId ? $userId : cmf_get_current_user_id();
        if (empty($userId)) {
            $userId = 1;
            //$this->error('必须先登录');
        }
        $pluginClass = cmf_get_plugin_class('Oss');

        $oss         = new $pluginClass();
        $ossConfig   = $oss->getConfig();
        $id          = $ossConfig['accessKeyId'];
        $key         = $ossConfig['accessKeySecret'];
        /*$host        = $this->request->scheme().'://'.$ossConfig['bucket'].'.'.$ossConfig['endpoint'].'/';*/
        $host        = 'https://'.$ossConfig['bucket'].'.'.$ossConfig['endpoint'];
 /*       $host        = 'https://aiimage2.hongmengyishu.com';*/
        $callbackUrl = cmf_get_domain().'/plugin/oss/asset/callback';

        $callback_param  = array(
            'callbackUrl'      => $callbackUrl,
            'callbackBody'     => 'etag=${etag}&filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}'.'&uid='.$userId.'&localname='.$filename,
            'callbackBodyType' => "application/x-www-form-urlencoded"
        );
        
        

        Log::write(var_export($callback_param,true),'debug',true);
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now                  = time();
        $expire               = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end                  = $now + $expire;
        $expiration           = $this->gmt_iso8601($end);
        $dir = $app?$app.'/':'';
        $dir .= date('Ymd').'/';
        Log::info('dir: '.$dir);
        //最大文件大小.用户可以自己设置
        $condition    = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start        = array(0 => 'starts-with', 1 => '$key', 2 =>$dir);
        $conditions[] = $start;


        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        $policy         = json_encode($arr);
        $base64_policy  = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature      = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));
        Log::info('$signature : '.$signature);
        $response              = array();
        $response['accessid']  = $id;
        $response['host']      = $host;
        $response['policy']    = $base64_policy;
        $response['signature'] = $signature;
        $response['expire']    = $end;
        $response['callback']  = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        return json($response);
    }
    public function getOssSignature(){
        
        $app = $this->request->param('app','');
        $filename = $this->request->param('filename','');        
        
        $pluginClass = cmf_get_plugin_class('Oss');
        $region_id = 'cn-shanghai'; 
        $oss         = new $pluginClass();
        $ossConfig   = $oss->getConfig();        
        $bucket = $ossConfig['bucket'];
        $host = $this->request->scheme().'://'.$ossConfig['bucket'].'.'.$ossConfig['endpoint'];
        $expire_time = 3600; // 过期时间，单位为秒
        $upload_dir = $app?$app.'/':'';
        $upload_dir .= date('Ymd').'/';        
        $callbackUrl = cmf_get_domain().'/plugin/oss/asset/callback';
        function hmacsha256($key, $data) {
            return hash_hmac('sha256', $data, $key, true);
        }
        $accessKeyId = $ossConfig['accessKeyId'];
        $accessKeySecret = $ossConfig['accessKeySecret'];       

        
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId($region_id)
            ->asDefaultClient();
        // 创建STS请求。
        $request = Sts::v20150401()->assumeRole();
        // 发起STS请求并获取结果。
        // 将<YOUR_ROLE_SESSION_NAME>设置为自定义的会话名称，例如oss-role-session。
        // 将<YOUR_ROLE_ARN>替换为拥有上传文件到指定OSS Bucket权限的RAM角色的ARN。
        dump($request);
        $result = $request
            ->withRoleSessionName('oss-role-session')
            ->withDurationSeconds(3600)
            ->withRoleArn(getenv('OSS_STS_ROLE_ARN')) // 替换为您的RAM角色ARN
            ->request();
        // 获取STS请求结果中的凭证信息。
        $tokenData = $result->get('Credentials');
        // 构建返回的JSON数据。
        $tempAccessKeyId = $tokenData['AccessKeyId'];
        $tempAccessKeySecret = $tokenData['AccessKeySecret'];
        $securityToken = $tokenData['SecurityToken'];
    
        $now = time();
        $dtObj = gmdate('Ymd\THis\Z', $now);
        $dtObj1 = gmdate('Ymd', $now);
        $dtObjPlus3h = gmdate('Y-m-d\TH:i:s.u\Z', strtotime('+3 hours', $now));
    
        // 构建Policy
        $policy = [
            "expiration" => $dtObjPlus3h,
            "conditions" => [
                ["x-oss-signature-version" => "OSS4-HMAC-SHA256"],
                ["x-oss-credential" => "{$tempAccessKeyId}/{$dtObj1}/cn-shanghai/oss/aliyun_v4_request"],
                ["x-oss-security-token" => $securityToken],
                ["x-oss-date" => $dtObj],
            ]
        ];
    
        $policyStr = json_encode($policy);
    
        // 构造待签名字符串
        $stringToSign = base64_encode($policyStr);
    
        // 计算SigningKey
        $dateKey = hmacsha256(('aliyun_v4' . $tempAccessKeySecret), $dtObj1);
        $dateRegionKey = hmacsha256($dateKey, 'cn-hangzhou');
        $dateRegionServiceKey = hmacsha256($dateRegionKey, 'oss');
        $signingKey = hmacsha256($dateRegionServiceKey, 'aliyun_v4_request');
    
        // 计算Signature
        $result = hmacsha256($signingKey, $stringToSign);
        $signature = bin2hex($result);
        
        $callback_param = array(
        'callbackUrl' => $callbackUrl,
        'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
        'callbackBodyType' => "application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);
    
        $base64_callback_body = base64_encode($callback_string);
    
        // 返回签名数据
        $responseData = [
            'policy' => $stringToSign,
            'x_oss_signature_version' => "OSS4-HMAC-SHA256",
            'x_oss_credential' => "{$tempAccessKeyId}/{$dtObj1}/cn-hangzhou/oss/aliyun_v4_request",
            'x_oss_date' => $dtObj,
            'signature' => $signature,
            'host' => $host,
            'dir' => $upload_dir,
            'security_token' => $securityToken,
            'callback' => $base64_callback_body,
        ];
        return json($responseData);
/*        header('Content-Type: application/json');
        echo json_encode($responseData);   */     
    }

    public function callback()
    {
        
        $logType = 'webuploader_callback';
        Log::init([
            'type'  =>  'File',
            'path'  =>  RUNTIME_PATH.'/log/webuploader_callback/',
            'apart_level'   =>  [$logType,'exception'],
            'file_size'     =>2097152,
            'max_files'     => 30
        ]);       
        
        Log::write('执行callback: line '.__LINE__, 'debug',true);
        
        
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = "";
        $pubKeyUrlBase64     = "";
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            Log::write('callback: line '.__LINE__.' error: 签名信息不存在', 'debug',true);
            header("http/1.1 404 Forbidden");

            exit();
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if ($pubKey == "") {
            Log::write('callback: line '.__LINE__.' error: 公钥请求失败', 'debug',true);
            header("http/1.1 403 Forbidden");
            exit();
        }

        // 4.获取回调body:
        $body = file_get_contents('php://input');
        $data = $this->request->param();
        // 5.拼接待签名字符串
        $authStr = '';
        $path    = $_SERVER['REQUEST_URI'];
        $pos     = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path)."\n".$body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($ok == 1) {
            Log::write('callback Data: '.var_export($data,true),'debug',true);
            $findAsset = Db::name('asset')->where('file_key', $data['etag'])->find();
            if (empty($findAsset)) {
                $arr      = explode('/', $data['filename']);
                $filename = $data['localname']?$data['localname']:array_pop($arr);
                $suffix   = cmf_get_file_extension($filename);
                Db::name('asset')->insert([
                    'user_id'     => $data['uid'],
                    'file_size'   => $data['size'],
                    'filename'    => $data['localname'],
                    'create_time' => time(),
                    'file_key'    => $data['etag'],
                    'file_path'   => $data['filename'],
                    'suffix'      => $suffix
                ]);
                $findAsset = Db::name('asset')->where('file_key', $data['etag'])->find();
            } else {
                $suffix   = cmf_get_file_extension($findAsset['file_path']);
                $arr              = explode('/', $findAsset['file_path']);
                $filename         = $findAsset['filename']?$findAsset['filename']:array_pop($arr);
            }
            $new_data = [
                'image_url' => in_array($suffix,['jpg','jpeg','gif','png','bmp'])?cmf_get_image_preview_url($findAsset['file_path']):cmf_get_asset_url($findAsset['file_path']),
                'url'       => cmf_get_file_download_url($findAsset['file_path']),
                'path'      => $findAsset['file_path'],
                'name'      => $filename,
                'preview_url'=>cmf_get_image_preview_url($findAsset['file_path']),
                'filepath'=>$findAsset['file_path'],
            ];
            return json(['Status'=>'Ok','code' => 1, 'msg' => '', 'data' => $new_data]);
        } else {
            Log::write('callback: line '.__LINE__.' error: 验证签名失败', 'debug',true);
            header("http/1.1 403 Forbidden");
            exit();
        }
    }

}
