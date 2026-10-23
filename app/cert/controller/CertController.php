<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\cert\controller;


use cmf\controller\HomeBaseController;
use app\user\model\UserPointsModel;
use app\portal\model\UserModel;
use app\portal\service\PostService;
use app\cert\model\CertPostModel;
use Qcloud\Cos\Exception\ServiceResponseException;
use TCPDF;

class CertController extends HomeBaseController
{
    
    //0表示待加密，1表示待解密，2表示已加密，3表示已解密
    const POST_CERT_TYPE_WAIT_ENCRYPT = 0;
    const POST_CERT_TYPE_WAIT_DECRYPT = 1;
    const POST_CERT_TYPE_ENCRYPTED = 2;
    const POST_CERT_TYPE_DECRYPTED = 3;
    const WATER_TYPE_HALFBLIND_IMAGE = 1;
    const WATER_TYPE_FULLBLIND_IMAGE = 2;
    const WATER_TYPE_TEXT = 3;
    const REGION = "ap-shanghai"; //替换为用户的 region，已创建桶归属的region可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket  
    const IMAGE_URL_PREFIX = "dianzicunzheng-1256990416.picsh.myqcloud.com/";
     
    public function encrypt()
    {


        /*$waterText  = $this->request->param('text', 0, 'strval');*/
        $articleId  = $this->request->param('id',0, 'intval');
        $categoryId = $this->request->param('cid', null, 'intval');
        $certPostModel = new CertPostModel();
        $article    = $certPostModel->where('id', '=', $articleId)->find()->getData();
        if (empty($article)) {
            abort(404, '图片资产不存在或者分类不存在或者相对应的图片资产关系不匹配!');
        }	
/*        if($article['post_cert_type']!=self::POST_CERT_TYPE_WAIT_ENCRYPT){
             $this->error('类型必须为待加密类型');
        }*/

        $waterText = $article['water_text'];
        $picture = $article['picture'];
        $addedWaterPicUrl = $this->addTextBlindWater($picture,$waterText,3);
        
        
        $dataList = [
            [
                'id' => $articleId, // 指定要更新的记录ID
                'added_water_picture' => $addedWaterPicUrl,
                'water_type' => self::WATER_TYPE_TEXT,
                'post_cert_type' => self::POST_CERT_TYPE_ENCRYPTED,
                'water_text' => $waterText,
                'update_time' => time(),
                
                // 其他字段...
            ]
        ];
        $updateStatus = $certPostModel->saveAll($dataList);
        
        // 检查更新状态
        if ($updateStatus) {
            $picture = "http://".self::IMAGE_URL_PREFIX.$picture;
            $this->createCertinfoAndPdf($articleId,$picture,$addedWaterPicUrl);
            $this->subtractPoints(1,1);
            $this->success('加密成功!');
        } else {
            $this->error('加密失败');
        }        
  

    }
    public function subtractPoints($picNum,$type)
    {
        $UserPointsModel = new UserPointsModel();
        if($type==1){
            //加密存证图片，一张1000
            $points = 1000;
        }else if($type==2){
            //解密存证图片，一张200
            $points = 200;
        }
        $subtractPoints = $picNum *$points*(-1);
        $userid = cmf_get_current_user_id();
        $result = $UserPointsModel->addPoints($userid,$subtractPoints);
        

    }
    public function decrypt()
    {

        $articleId  = $this->request->param('id',0, 'intval');
        $categoryId = $this->request->param('cid', null, 'intval');
        $certPostModel = new CertPostModel();
        $article    = $certPostModel->where('id', '=', $articleId)->find()->getData(); 
        if (empty($article)) {
            abort(404, '图片资产不存在或者分类不存在或者相对应的图片资产关系不匹配!');
        }	
        $extractWaterPicUrl = $article['picture'];
        $extractWaterPicUrl = self::IMAGE_URL_PREFIX.$extractWaterPicUrl;
        $extractedWaterText = $this->extractTextBlindWater($extractWaterPicUrl);
        $dataList = [
            [
                'id' => $articleId, // 指定要更新的记录ID
                'post_cert_type' => self::POST_CERT_TYPE_DECRYPTED,
                'extracted_water_text' => $extractedWaterText
            ]
        ];
  
        $updateStatus = $certPostModel->saveAll($dataList);
        if ($updateStatus) {
             $this->subtractPoints(1,2);
             $this->success('解密成功!');
        } else {
            $this->error('解密失败');
        }  

    }
    public function addTextBlindWater($picture,$waterText,$level){
            try {
           
                $cosClient = new \Qcloud\Cos\Client(
                            array(
                            'region' => self::REGION,
                            'scheme' => 'https', //协议头部，默认为http
                            'credentials'=> array(
                                'secretId'  => $this->envValue('TENCENT_COS_SECRET_ID'),
                                'secretKey' => $this->envValue('TENCENT_COS_SECRET_KEY'))));
                        
                        
                        
                $blindWatermarkTemplate = new \Qcloud\Cos\ImageParamTemplate\BlindWatermarkTemplate();//创建盲水印参数模版实例
                $blindWatermarkTemplate->setType(3);//盲水印类型，有效值：1 半盲；2 全盲；3 文字
                $blindWatermarkTemplate->setText($waterText);//设置盲水印文字
                $blindWatermarkTemplate->setLevel($level);//只对全盲水印（type=2）有效。level 的取值范围为{1,2,3}，默认值为1，level 值越大则图片受影响程度越大、盲水印效果越好。
                //2.0仅支持数字[0 - 9]及英文大小写[A - Z,a - z]作为水印内容；建议原图质量大于90以上，盲水印效果会更好，也更容易提取；要求原图宽高大于512 x 512像素；可抗裁剪、缩放、压缩。
                //3.0支持英文、数字、中文等任意UTF8编码的字符作为水印内容，长度不可超过256；要求原图宽高大于64 x 64像素；抗攻击性更强，可抗384 x 384分辨率以上的任意裁剪、压缩；暂不支持下载时添加该版本
                $blindWatermarkTemplate->setVersion("3.0");
                
                $picOperationsTemplate = new \Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation();
                $picOperationsTemplate->setIsPicInfo(0);
                $addedWaterPic = '/water/'.$picture;
                $picOperationsTemplate->addRule($blindWatermarkTemplate, $addedWaterPic);
                
                
                
                // -------------------- 3. 云上数据处理 -------------------- //
                $picOperationsString = $picOperationsTemplate->queryString();
                $result = $cosClient->ImageProcess(array(
                    'Bucket' => 'dianzicunzheng-1256990416', //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                    'Key' => $picture,
                    'PicOperations' => $picOperationsString,
                ));
                $addedWaterPicUrl = $result['ProcessResults']["Object"][0]["Location"];
                return $addedWaterPicUrl;           
            } catch (ServiceResponseException $e) { // 捕获 ServiceResponseException
                return false;
            } catch (Exception $e) {
                return false;
            }       
 
        
    }
    
    public function extractTextBlindWater($addedWaterPicUrl){
        $secretId = $this->envValue('TENCENT_COS_SECRET_ID');
        $secretKey = $this->envValue('TENCENT_COS_SECRET_KEY');
        $region = "ap-shanghai"; //替换为用户的 region，已创建桶归属的region可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket        
        
        
        $cosClient = new \Qcloud\Cos\Client(
            array(
            'region' => $region,
            'scheme' => 'https', //协议头部，默认为http
            'credentials'=> array(
                'secretId'  => $secretId ,
                'secretKey' => $secretKey)));
        
        
        
        $blindWatermarkTemplate = new \Qcloud\Cos\ImageParamTemplate\BlindWatermarkTemplate();//创建盲水印参数模版实例
        $blindWatermarkTemplate->setPick();//提取水印
        $blindWatermarkTemplate->setType(3);//盲水印类型，有效值：1 半盲；2 全盲；3 文字
        //仅当水印类型为文字盲水印，且version版本号为3.0时返回该节点，表示嵌入的文字水印，
        $blindWatermarkTemplate->setVersion("3.0");//文字水印貌似暂时只支持2.0
        
        $picOperationsTemplate = new \Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation();
        $picOperationsTemplate->setIsPicInfo(0);
        
        // 找到第一个斜杠 '/' 的位置
        $slashPosition = strpos($addedWaterPicUrl, '/');
        
        // 如果找到了斜杠，去掉前面的部分
        if ($slashPosition !== false) {
            $addedWaterPic = substr($addedWaterPicUrl, $slashPosition);
        } else {
            // 如果没有找到斜杠，说明没有路径部分
            $addedWaterPic = $url;
        }

        $extractedWater = '/extracted'.$addedWaterPic;
        $picOperationsTemplate->addRule($blindWatermarkTemplate, $extractedWater);
        
        
        
        // -------------------- 3. 云上数据处理 -------------------- //
        $picOperationsString = $picOperationsTemplate->queryString();
        $result = $cosClient->ImageProcess(array(
            'Bucket' => 'dianzicunzheng-1256990416', //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
            'Key' => $addedWaterPic,
            'PicOperations' => $picOperationsString,
        ));
        //dump($result['ProcessResults']);
        $extractedWaterText = $result['ProcessResults']["Text"];
        return $extractedWaterText;

    }

    private function envValue($name, $default = '')
    {
        return cmf_service_config($name, $default);
    }
    public function uploadCertfile(){
   
       return $this->fetch("/uploadcertfile");
    }
    public function uploadExtractedFile(){
   
        return $this->fetch("/uploadextractfile");
    }
    public function uploadMultiEncrypt(){
   
        return $this->fetch("/uploadMultiEncryptFile");
    }
    
    public function uploadMultiDecrypt(){
   
        return $this->fetch("/uploadMultiDecryptFile");
    }
    public function submitCertfile(){
   
        $data = $this->request->param();
        $data['user_id'] = cmf_get_current_user_id();
        if (empty($data['user_id'])) {
            $this->error('用户未登录');
        } 
        //状态只能设置默认值。未发布、未置顶、未推荐
        $data['post']['post_status'] = 1;
        $data['post']['is_top']      = 0;
        $data['post']['recommended'] = 0;
        $data['post']['user_id'] = $data['user_id'];
        $data['post']['published_time'] = time();
        $data['post']['post_cert_type'] = self::POST_CERT_TYPE_ENCRYPTED;

        $post = $data['post'];

        $result = $this->validate($post, 'Article');
        if ($result !== true) {
            $this->error($result);
        }

        $certPostModel = new CertPostModel();

        if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
            $data['post']['more']['photos'] = [];
            foreach ($data['photo_urls'] as $key => $url) {
                $photoUrl = cmf_asset_relative_url($url);
                array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                $data['post']['picture'] = $photoUrl;
            }
        }


        $certPostModel->addArticle($data['post'], $data['post']['categories']);
     
        $waterText  = $data['post']['water_text'];
        $articleId  = $certPostModel->getArticleId();
        $categoryId = $data['post']['categories'];
        $picture = $data['post']['picture'];
        $addedWaterPicUrl = $this->addTextBlindWater($picture,$waterText,3);
        
        $dataList = [
            [
                'id' => $articleId, // 指定要更新的记录ID
                'added_water_picture' => $addedWaterPicUrl,
                'water_type' => self::WATER_TYPE_TEXT,
                'water_text' => $waterText,
            ]
        ];
        $updateStatus = $certPostModel->saveAll($dataList);
        // 检查更新状态
        if ($updateStatus) {
             $this->success('添加图片并加密成功!');
        } else {
            $this->error('加密失败');
        }                
    
   
    }    
    public function submitMultiCertfile(){
   
        $param = $this->request->param();
        $waterText = $param["post"]['water_text'];
        /*$categories= $param["post"]['categories'];*/
        $title = $param["post"]['post_title'];
        if (empty(cmf_get_current_user_id())) {
            $this->error('用户未登录');
        }        
        $uid = cmf_get_current_user_id();
        
        if (!empty($param['photo_names']) && !empty($param['photo_urls'])) {
            
            foreach ($param['photo_urls'] as $key => $url) {
                
                $post = [];
                $post['user_id'] = $uid;
                $post['post_title'] = $title;
                $post['post_status'] = 1;
                $post['published_time'] = time();
                $post['post_cert_type'] = self::POST_CERT_TYPE_WAIT_ENCRYPT;                
                $post['more']['photos'] = [];
                $post['water_type'] = self::WATER_TYPE_TEXT;
                $post['water_text'] = $waterText;
                $post['post_status'] = 1;
                
                $result = $this->validate($post, 'Article');
                if ($result !== true) {
                    $this->error($result);
                }               
                $photoUrl = cmf_asset_relative_url($url);
                $post['picture'] = $photoUrl;
                array_push($post['more']['photos'], ["url" => $photoUrl, "name" => $param['photo_names'][$key]]);
                $certPostModel = new CertPostModel();
                $certPostModel->save($post);
                
            }
        }        
        
        $this->success('批量添加待加密图片成功!');
   
    }        
    public function submitMultiDecryptfile(){
   
        $param = $this->request->param();
        $title = $param["post"]['post_title'];
        if (empty(cmf_get_current_user_id())) {
            $this->error('用户未登录');
        }        
        $uid = cmf_get_current_user_id();
        
        if (!empty($param['photo_names']) && !empty($param['photo_urls'])) {
            
            foreach ($param['photo_urls'] as $key => $url) {
                
                $post = [];
                $post['user_id'] = $uid;
                $post['post_title'] = $title;
                $post['post_status'] = 1;
                $post['published_time'] = time();
                $post['post_cert_type'] = self::POST_CERT_TYPE_WAIT_DECRYPT;                
                $post['more']['photos'] = [];
                $post['water_type'] = self::WATER_TYPE_TEXT;
                $post['post_status'] = 1;
              
                
                $result = $this->validate($post, 'Article');
                if ($result !== true) {
                    $this->error($result);
                }               
                $photoUrl = cmf_asset_relative_url($url);
                $post['picture'] = $photoUrl;
                array_push($post['more']['photos'], ["url" => $photoUrl, "name" => $param['photo_names'][$key]]);
                $certPostModel = new CertPostModel();
                $certPostModel->save($post);
                
            }
        }        
        
        $this->success('批量添加待解密图片成功!');
   
    }        
    
    public function submitExtractedfile(){
   
        $data = $this->request->param();
        $data['user_id'] = cmf_get_current_user_id();
        if (empty($data['user_id'])) {
            $this->error('用户未登录');
        } 
        //状态只能设置默认值。未发布、未置顶、未推荐
        $data['post']['post_status'] = 1;
        $data['post']['is_top']      = 0;
        $data['post']['recommended'] = 0;
        $data['post']['user_id'] = $data['user_id'];
        $data['post']['published_time'] = time();
        $data['post']['post_cert_type'] = self::POST_CERT_TYPE_DECRYPTED;

        $post = $data['post'];

        $result = $this->validate($post, 'Article');
        if ($result !== true) {
            $this->error($result);
        }

        $certPostModel = new CertPostModel();

        if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
            $data['post']['more']['photos'] = [];
            foreach ($data['photo_urls'] as $key => $url) {
                $photoUrl = cmf_asset_relative_url($url);
                array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                $data['post']['picture'] = $photoUrl;
                $data['post']['added_water_picture'] = $photoUrl;
            }
        }


        $certPostModel->addArticle($data['post'], $data['post']['categories']);
     

        $articleId  = $certPostModel->getArticleId();
        $addedWaterPicUrl = self::IMAGE_URL_PREFIX.$data['post']['added_water_picture'];
        $extractedWaterText = $this->extractTextBlindWater($addedWaterPicUrl);
  
        $dataList = [
            [
                'id' => $articleId, // 指定要更新的记录ID
                'extracted_water_text' => $extractedWaterText
            ]
        ];
        $updateStatus = $certPostModel->saveAll($dataList);
       
        if ($updateStatus) {
             $this->success('解密图片并解密成功!');
        } else {
            $this->error('解密失败');
        }                
    
   
    }    
    //批量加密
    public function submitMultiEncrypt(){
        
        $param = $this->request->param();
        $selectedIds= $param["selectedIds"];
 
        $data['user_id'] = cmf_get_current_user_id();
        if (empty($data['user_id'])) {
            $this->error('用户未登录,请先登陆');
        } 
        
        
        $certPostModel = new CertPostModel();
        $statusArray = []; // 定义状态数组
        foreach ($selectedIds as $key => $articleId) {
            $article    = $certPostModel->where('id', '=', $articleId)->find()->getData();
            if (empty($article)) {
                $statusArray[$articleId] = false; 
                continue; 
            }
            $addedWaterPicUrl = $this->addTextBlindWater($article['picture'],$article['water_text'],3);
            if (!$addedWaterPicUrl) {
                $statusArray[$articleId] = false; 
                continue; 
            }            
            $dataList = [
                [
                    'id' => $articleId, // 指定要更新的记录ID
                    'added_water_picture' => $addedWaterPicUrl,
                    'post_cert_type' => self::POST_CERT_TYPE_ENCRYPTED,
                ]
            ];            
            $updateStatus = $certPostModel->saveAll($dataList);
            if ($updateStatus) {
                 $this->subtractPoints(1,1);
                 $statusArray[$articleId] = true; 
                 $picture = "http://".self::IMAGE_URL_PREFIX.$article['picture'];
                 $this->createCertinfoAndPdf($articleId,$picture,$addedWaterPicUrl);
            } else {
               $statusArray[$articleId] = false; 
            } 
            
        } 
        return json_encode($statusArray);
        
    }            
    //批量解密
    public function submitMultiDecrypt(){
        
        $param = $this->request->param();
        $selectedIds= $param["selectedIds"];
 
        $data['user_id'] = cmf_get_current_user_id();
        if (empty($data['user_id'])) {
            $this->error('用户未登录,请先登陆');
        } 
        
        
        $certPostModel = new CertPostModel();
        $statusArray = []; // 定义状态数组
        foreach ($selectedIds as $key => $articleId) {
            $article    = $certPostModel->where('id', '=', $articleId)->find()->getData();
            if (empty($article)) {
                $statusArray[$articleId] = false; 
                $this->error('无此作品信息');
            }
            
            $extractWaterPicUrl = $article['picture'];
            $extractWaterPicUrl = self::IMAGE_URL_PREFIX.$extractWaterPicUrl;
            $extractedWaterText = $this->extractTextBlindWater($extractWaterPicUrl);
            $dataList = [
                [
                    'id' => $articleId, // 指定要更新的记录ID
                    'post_cert_type' => self::POST_CERT_TYPE_DECRYPTED,
                    'extracted_water_text' => $extractedWaterText
                ]
            ];            
            

            $updateStatus = $certPostModel->saveAll($dataList);
            if ($updateStatus) {
                $this->subtractPoints(1,2);
                 $statusArray[$articleId] = true; 
            } else {
               $statusArray[$articleId] = false; 
            } 
            
        } 
        
        
        
        return json_encode($statusArray);
        
    }   

    
    
    public function createCertinfoAndPdf($articleId,$picurl,$addedWaterPicUrl){

        $certPostModel = new CertPostModel();
        $article    = $certPostModel->where('id', '=', $articleId)->find()->getData();
        if (empty($article)) {
            $this->error('无此作品信息');
            return false;
        }    
        
        $userinfo = UserModel::where("id", $article['user_id'])->find();
      
        $imageData = cmf_get_image_from_url($picurl);
        
        
        // 检查是否成功获取了图片内容
        if ($imageData !== false) {
            // 计算文件大小（字节）
            $fileSize = strlen($imageData);
            $certTime = time();
        
            // 计算 SHA256 值
            $worksha = hash('sha256', $imageData);
            $workSize = round($fileSize / 1024,2);
            $formattedId = str_pad($articleId, 8, '0', STR_PAD_LEFT);
            $worksign = hash_hmac('sha256', $formattedId, $formattedId);
            $certNo = $certTime.$formattedId;

        } else {
            return false;
            
        }      
      
      
        

        $workTitle = $article['post_title'];
        $author = $userinfo['user_nickname'];
        


        
        
        $pageWidth = 240;
        $pageHeight = 300;
        
        // 实例化TCPDF，设置页面尺寸
        $pdf = new TCPDF('P', 'mm', array($pageWidth, $pageHeight), true, 'UTF-8', false);       
        
        
        // 实例化TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
        // 设置文档信息
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('电子数据区块链存证证书');
    
    
        $pdf->SetFont('stsongstdlight', '', 20);
    
        // 添加一个页面
        $pdf->AddPage();
    
        
     // 添加边框图片
        $img_file = WEB_ROOT . '/static/images/pdfborder.jpg';
        // 图片的x和y坐标（居中时为页面宽度和高度的一半减去图片宽度和高度的一半）
        $x = ($pageWidth - $pageWidth) / 2;
        $y = ($pageHeight - $pageHeight) / 2;
    
        // 图片的宽度和高度设置为页面的宽度和高度
        $w = $pageWidth;
        $h = $pageHeight;
    
        // 添加图片，并确保图片居中且铺满整个页面
        $pdf->Image($img_file, $x, $y, $w, $h, '', '', 'C', false, 300, '', false, false, 0);
    
        // 添加印章图片
         $img_file = WEB_ROOT . '/static/images/seal.png';
        // 图片的x和y坐标（居中时为页面宽度和高度的一半减去图片宽度和高度的一半）
        $x = 100;
        $y = 100;
    
        // 图片的宽度和高度设置为页面的宽度和高度
        $w = 100;
        $h = 50;
    
        // 添加图片，并确保图片居中且铺满整个页面
        $pdf->Image($img_file, $x, $y, $w, $h, '', '', 'R', false, 300, '', false, false, 0);       
        
        
        
        
        $pdf->Ln(30);
        // 设置字体样式
          $pdf->SetFont('stsongstdlight', '', 20);
    
        // 写入标题
        $pdf->Cell(0, 10, '电子数据区块链存证证书', 0, 1, 'C');
    
 
        // 设置字体样式为普通
        $pdf->SetFont('stsongstdlight', '', 14);
        // 写入内容
        $content = '谛听号：'.$certNo;
        $pdf->Cell(0, 10, $content, 0, 1, 'C');
        
   
        $pdf->Ln(5);
        $pdf->Write(0, '                 以下电子数据数字指纹已通过谛听护艺节点同步至上海互联网公证处，兹证');
        $pdf->Ln(5);
        $pdf->Write(0, '         明该证书所对应电子数据自申请该证书起已经存在并且数据完整，未被篡改。');       
 
        $pdf->Ln(5);
       
        
        
        // 写入更多的内容
        $pdf->Ln(5);
        $pdf->SetFont('stsongstdlight', '', 12);
        $content = '电子证据sha256值：'.$worksha;
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(5);
        $content = '谛听链唯一标识：'.$worksign;
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(20);
        $pdf->SetFont('stsongstdlight', '', 14);
        $content = '申请人：'.$author;
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(5);
        $dateTime = date('Y-m-d H:i:s', $certTime);
        $content = '申请时间：'.$dateTime;
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(5);
        $content = '文件名称：'.$workTitle;
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(5);
        $content = '文件大小：'.$workSize.'KB';
        $pdf->Write(0, '                  '.$content);
        $pdf->Ln(50);
    
           // 添加上分隔线
        $pdf->SetLineStyle(array('dash' => 2, 'gap' => 2));
        $pdf->Line(25, 78, 190, 78);
       // 下分隔线
        $pdf->Line(25, 90, 190, 90);
        //左框
        $pdf->Line(25, 78, 25, 90); 
        $pdf->Line(190, 78, 190, 90); 
        
    
     // 证书说明
        $pdf->Write(0, '              谛听链是谛听护艺网建设的第三方通用区块链底层基础设施。主节点包括多');
        $pdf->Ln(5);
        $pdf->Write(0, '              家公证处、司法鉴定中心、行业协会、科技公司。验证证书真实性及电子数');
        $pdf->Ln(5);        
        $pdf->Write(0, '              据一致性，可通过扫描证书内二维码或前往谛听护艺网官网“证据查验”模');
        $pdf->Ln(5);   
        $pdf->Write(0, '              块进行验证。');
        
        
       /* $explanation = "谛听链是谛听护艺网建设的第三方通用区块链底层基础设施。主节点包括多家公证处、司法鉴定中心、行业协会、科技公司等。\n\n"
            . "验证证书真实性及电子数据一致性，可通过扫描证书内二维码或前往谛听护艺网官网“证据查验”模块进行验证。";
    
        // 写入证书说明
        $pdf->MultiCell(180, 0, $explanation, 0, 'L', 0, 1, '', '', true);*/
        
        
         // 添加一个页面
        $pdf->AddPage();      
        
        
        $imageData = cmf_get_image_from_url($picurl);
        
        $fileName = "certpdf/pdfimage.jpg";
        // 指定PDF文件的保存路径
        $pdfFilePath = WEB_ROOT . $fileName;
        file_put_contents($pdfFilePath, $imageData);
    
        
      // 确保图片路径正确
        if (file_exists($pdfFilePath)) {
            // 图片的宽度和高度
            list($width, $height) = getimagesize($pdfFilePath);
    
            // 计算缩放比例，确保图片能够适应页面尺寸
            $scaleWidth = $pageWidth / $width;
            $scaleHeight = $pageHeight / $height;
            $scale = min($scaleWidth, $scaleHeight);
    
            // 新的图片宽度和高度
            $newWidth = $width * $scale;
            $newHeight = $height * $scale;
    
            // 图片的x和y坐标（居中）
            $x = ($pageWidth - $newWidth) / 2;
            $y = ($pageHeight - $newHeight) / 2;
    
            // 添加图片，并确保图片居中
            $pdf->Image($pdfFilePath, $x, $y, $newWidth, $newHeight, '', '', 'C', false, 300, '', false, false, 0);
        } else {
            // 如果图片路径不正确，输出错误信息
            $pdf->SetFontSize(20);
            $pdf->Cell(0, 10, '图片路径不正确，无法显示图片', 0, 1, 'C');
        }        
   
/*     // 添加加密过图片
        // 图片的x和y坐标（居中时为页面宽度和高度的一半减去图片宽度和高度的一半）
        $x = ($pageWidth - $pageWidth) / 2;
        $y = ($pageHeight - $pageHeight) / 2;
    
        // 图片的宽度和高度设置为页面的宽度和高度
        $w = $pageWidth;
        $h = $pageHeight;
        dump($addedWaterPicUrl);
        // 添加图片，并确保图片居中且铺满整个页面
        $pdf->Image($addedWaterPicUrl, $x, $y, $w, $h, '', '', 'C', false, 300, '', false, false, 0);        
        
        
        */
        
        
        
        
        
    
        $fileName = "certpdf/存证证书_".$workTitle."_".$certNo.".pdf";
        // 指定PDF文件的保存路径
        $pdfFilePath = WEB_ROOT . $fileName;
    
        // 确保目录存在，如果不存在则创建
        $dir = dirname($pdfFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    
        // 输出PDF文件到指定路径
        $pdf->Output($pdfFilePath, 'F');
        
        $fileUrl = "www.hongmengyishu.com/".$fileName;
        $dataList = [
            [
                'id' => $articleId, // 指定要更新的记录ID
                'cert_no' => $certNo,
                'cert_time' => $certTime,
                'size' => $workSize,
                'sha256' => $worksha,
                'sign' => $worksign,
                'cert_file_url' => $fileUrl
            ]
        ];            
        

        $updateStatus = $certPostModel->saveAll($dataList);
    

    }
     public function userFileList()
    {
        $certType                  = $this->request->param('type', 1, 'intval');

        if (cmf_is_user_login()) {
            $userId = cmf_get_current_user_id();
    		$where = array();
    		$where['user_id'] = $userId;
    		$where['post_cert_type'] = $certType;
    		$where['post.delete_time'] = 0;
            $page=[
            'list_rows'=>20,
            'next'=>'下一页',
            'prev'=>'上一页'
            ];   		
    	
    		
  
            $limit       = 20;
    		
    		
    		
    		$certPostModel = new CertPostModel();
    		
            $articles = $certPostModel->alias('post')
                ->where($where)
                ->order('update_time desc')
                ->group('post.id');
    
            $return = [];
    
            if (empty($page)) {
                $articles = $articles->limit($limit)->select();
                $return['articles'] = $articles;
            } else {
    
                if (is_array($page)) {
                    if (empty($page['list_rows'])) {
                        $page['list_rows'] = 10;
                    }
                    
                    $articles = $articles->paginate($page);
                } else {
                    $articles = $articles->paginate(intval($page));
                }
    
    
    			
                $return['articles']    = $articles->items();
                $return['page']        = $articles->render();
                $return['total']       = $articles->total();
                $return['total_pages'] = $articles->lastPage();
            }
           
    		
 		
 
    		$this->assign('page', $return['page']);
    		$this->assign('total', $return['total']);
    		$this->assign('total_pages', $return['total_pages']);
    		$this->assign('articles', $return['articles']);
    		$this->assign('type', $certType);
    		
    		
    		
        } else {
            $this->error("请先登陆!");
        }
  
        return $this->fetch('/userfilelist2');
    }

}
