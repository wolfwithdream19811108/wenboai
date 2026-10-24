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
namespace app\threed\controller;
require_once VENDOR_PATH. 'upyun/vendor/autoload.php';

use cmf\controller\HomeBaseController;
use phpseclib\Crypt\RSA;
use app\user\model\UserPointsModel;
use app\ai\model\AiUserThreedworksModel;
use app\ai\service\ImageGenerationConfig;
use think\Db;
use Upyun\Upyun;
use Upyun\Config;

class IndexController extends HomeBaseController
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
        $models = Db::name('portal_aiimage_model')->where('model_type', '=', "model")->select()->toArray();
        $params = $this->request->get();
        $modelPic = isset($params['modelpic']) ? strval($params['modelpic']) : null;
        $userWorkId = isset($params['userWorkId']) ? strval($params['userWorkId']) : null;
        $isLogin = cmf_is_user_login();
        if ($isLogin) {
            $userid = cmf_get_current_user_id();
            $aiUserPointsModel = new UserPointsModel();
            $currentPoints = $aiUserPointsModel->getUserPoints($userid);
            $this->assign('points', $currentPoints);
        }
        if(!$modelPic){
            $this->error("图片地址为空");
        }
        //$modelPic = "https://cdn.wujiebantu.com/ai/855778DDE9EF66594B909B4429BC3A88-01.jpg";


		$this->assign('models', $models);
		$this->assign('modelPic', $modelPic);
		$this->assign('userWorkId', $userWorkId);
		$this->assign('isLogin', $isLogin);



        return $this->fetch("/index");
        //return $this->fetch("/display");
    }
    public function view()
    {
        $models = Db::name('portal_aiimage_model')->where('model_type', '=', "model")->select()->toArray();
        $params = $this->request->get();
        $modelPic = isset($params['modelpic']) ? strval($params['modelpic']) : null;
        $userWorkId = isset($params['userWorkId']) ? strval($params['userWorkId']) : null;
        $isLogin = cmf_is_user_login();
        if ($isLogin) {
            $userid = cmf_get_current_user_id();
            $aiUserPointsModel = new UserPointsModel();
            $currentPoints = $aiUserPointsModel->getUserPoints($userid);
            $this->assign('points', $currentPoints);
        }
        if(!$modelPic){
            $this->error("图片地址为空");
        }
        //$modelPic = "https://cdn.wujiebantu.com/ai/855778DDE9EF66594B909B4429BC3A88-01.jpg";


		$this->assign('models', $models);
		$this->assign('modelPic', $modelPic);
		$this->assign('userWorkId', $userWorkId);
		$this->assign('isLogin', $isLogin);



        return $this->fetch("/view");
        //return $this->fetch("/display");
    }
    public function getGlbResource()
    {

        $params = $this->request->post();
        $objectUrl = isset($params['objecturl']) ? strval($params['objecturl']) : null;
        $objectUrl = htmlspecialchars_decode($objectUrl);


     // 验证objecturl参数
        if (empty($objectUrl)) {
            return json(['error' => 'No objecturl parameter provided.'], 400);
        }

        // 目标文件夹
        $destinationFolder = 'upload/glb/';

        // 确保目标文件夹存在
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0777, true);
        }

        // 创建目标文件路径
        $timestamp = time();
        $destinationFile = $destinationFolder . $timestamp . '.glb';

        // 初始化 cURL 会话
        $ch = curl_init($objectUrl);
        if ($ch === false) {
            return json(['error' => 'Failed to initialize cURL'], 500);
        }
        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);


        // 执行 cURL 会话
        $data = curl_exec($ch);

        // 检查是否有错误发生
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch); // 先获取错误信息
            curl_close($ch);             // 再关闭句柄
            return json(['error' => 'Failed to download file: ' . $errorMsg], 500);
        } else {
            $upyunObjectUrl = $this->uploadContentToUpyun($objectUrl,$data);

            if($upyunObjectUrl){
                return json(['filePath' => $upyunObjectUrl]);

            }



            if (file_put_contents($destinationFile, $data)) {
                curl_close($ch);
                return json(['filePath' => $destinationFile]);
            } else {
                curl_close($ch);
                return json(['error' => 'Failed to save the file.'], 500);
            }
        }

    }

    public function uploadUpyun($glbUrl){
        $keys = ImageGenerationConfig::keys();
        $settings = ImageGenerationConfig::settings();
        if (empty($keys['upyun_service']) || empty($keys['upyun_operator']) || empty($keys['upyun_password'])) {
            return null;
        }
        $serviceConfig = new Config($keys['upyun_service'], $keys['upyun_operator'], $keys['upyun_password']);
        $client = new Upyun($serviceConfig);
        // 3. 直接下载内容并上传
        $remotePath = '/3d/glb/' . basename($glbUrl).'.glb'; // 又拍云保存路径
        $fileContent = file_get_contents($glbUrl); // 直接读取远程文件
        $uploadInfo = $client->write($remotePath, $fileContent); // 上传字符串内容
        if($uploadInfo){
            return rtrim($settings['storage']['upyun_domain'], '/').$remotePath;
        }else{
            return null;
        }

    }

     public function uploadContentToUpyun($glbUrl,$fileContent){

        $remotePath = '/3d/glb/' . md5($glbUrl).'.glb'; // 又拍云保存路径
        $keys = ImageGenerationConfig::keys();
        $settings = ImageGenerationConfig::settings();
        if (empty($keys['upyun_service']) || empty($keys['upyun_operator']) || empty($keys['upyun_password'])) {
            return null;
        }
        $serviceConfig = new Config($keys['upyun_service'], $keys['upyun_operator'], $keys['upyun_password']);
        $client = new Upyun($serviceConfig);
        $uploadInfo = $client->write($remotePath, $fileContent); // 上传字符串内容
        if($uploadInfo){
            return rtrim($settings['storage']['upyun_domain'], '/').$remotePath;
        }else{
            return null;
        }

    }


/**
     * 添加用户3D作品记录
     * @return \think\response\Json
     */
    public function addUser3dWork()
    {
        // 验证用户是否登录
        if (!cmf_is_user_login()) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        // 获取当前用户ID
        $userId = cmf_get_current_user_id();

        // 接收POST参数
        $work2dId = $this->request->post('work2dId', '', 'trim');
        $task3dId = $this->request->post('task3dId', '', 'trim');
        $glbUrl = $this->request->post('glb_url', '', 'trim');
        $imageUrl = $this->request->post('imageUrl', '', 'trim');

        // 参数验证
        if (empty($work2dId)) {
            return json(['code' => 400, 'msg' => 'work2dId不能为空']);
        }
        if (empty($task3dId)) {
            return json(['code' => 400, 'msg' => 'task3dId不能为空']);
        }
        if (empty($glbUrl)) {
            return json(['code' => 400, 'msg' => 'glb_url不能为空']);
        }
        if (empty($imageUrl)) {
            return json(['code' => 400, 'msg' => 'imageUrl不能为空']);
        }

        try {
            // 调用模型添加记录
            $model = new AiUserThreedworksModel();
            $result = $model->addUser3dWork($userId, $work2dId, $task3dId, $imageUrl,$glbUrl);

            if ($result) {
                return json(['code' => 200, 'msg' => '添加成功', 'data' => $result]);
            } else {
                return json(['code' => 500, 'msg' => '添加失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '服务器错误: ' . $e->getMessage()]);
        }
    }




}
