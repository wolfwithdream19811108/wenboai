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


use cmf\controller\HomeBaseController;
use phpseclib\Crypt\RSA;
use app\user\model\UserPointsModel;
use app\ai\model\AiUserThreedworksModel;
use think\Db;


class ViewController extends HomeBaseController
{
   
    public function index()
    {
        $params = $this->request->get();
        $modelPic = isset($params['modelpic']) ? strval($params['modelpic']) : null; 
        $modelUrl = isset($params['modelUrl']) ? strval($params['modelUrl']) : null; 
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


		$this->assign('modelPic', $modelPic);
		$this->assign('modelUrl', $modelUrl);
		$this->assign('isLogin', $isLogin);
      
        
        
        return $this->fetch("/view");
        //return $this->fetch("/display");
    }
    
 


}
