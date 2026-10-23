<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:kane < chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\ai\model;

use think\Model;
use api\user\model\UserModel;

class AiUserWorksModel extends Model
{


    public function addUserWork($userid,$workKey,$timestamp)
    {
            $userModel       = new UserModel();
            $userData        = $userModel->find($userid);
            $update['uid'] = $userid;
            $update['key'] = $workKey;
            $update['create_timestamp'] = $timestamp;
            $update['user_name'] = $userData['user_nickname'];
            $update['create_date'] = date("Y-m-d H:i:s", $currentTimestamp);
            $result = $this->save($update);
            return $result;

            
    }
/*    public function addUserWorkArray($userid,$workKeyArray)
    {
            $updates = [];
            $userModel       = new UserModel();
            $userData        = $userModel->find($userid);
            foreach ($workKeyArray as $workKey) {
                $currentTimestamp = time();
                $update = [
                    'uid' => $userid,
                    'key' => $workKey,
                    'create_timestamp' => $currentTimestamp,
                    'user_name' => $userData['user_nickname'],
                    'create_date' => date("Y-m-d H:i:s", $currentTimestamp)
                ];
                $updates[] = $update;
            }

            $result = $this->saveAll($updates);
            return $result;            
  
    }*/
    /**
     * 批量添加用户作品
     * @param int $userid 用户ID
     * @param array $workKeyArray 作品Key数组
     * @return array|bool 成功返回关联数组[key => id]，失败返回false
     */
    public function addUserWorkArray($userid, $workKeyArray)
    {
        $userModel = new UserModel();
        $userData = $userModel->find($userid);
        $currentTimestamp = time();
        
        $dataList = [];
        foreach ($workKeyArray as $workKey) {
            $dataList[] = [
                'uid' => $userid,
                'key' => $workKey,
                'create_timestamp' => $currentTimestamp,
                'user_name' => $userData['user_nickname'],
                'create_date' => date("Y-m-d H:i:s", $currentTimestamp)
            ];
        }
    
        $result = $this->saveAll($dataList);
        
        if ($result) {
            // 构建并返回[key => id]的关联数组
            return array_column($result->toArray(), 'id', 'key');
        }
        
        return false;
    }
    public function updateWorkInfo($key,$data)
    {
        // 构建更新数据的数组，将字符串转换为适当的数据类型
        $updateData = [
            'control_net_info'   => json_encode($data['control_net_info']),
            'advanced_info'      => isset($data['advanced_info']) ? json_encode($data['advanced_info']) : null,
            'cost_info'          => json_encode($data['cost_info']),
            'picture_url'        => $data['picture_url'],
            'prefine_multiple'  => (float)$data['prefine_multiple'],
            'adetailer'          => json_encode($data['adetailer']),
            'supersize_multiple'=> (float)$data['supersize_multiple'],
            'model_code'         => (int)$data['model_code'],
            'width'              => (int)$data['width'],
            'height'             => (int)$data['height'],
            'update_timestamp'             => time(),
            'prompt'             => $data['prompt']
        ];       
        // 执行更新操作，假设 $id 是要更新的记录的 ID
        $result = $this->where(['key' => $key])->update($updateData);
        return $result;
  
    }
    public function getUserWorks($userid)
    {
        
            $records = $this->where('uid', $userid)->where('picture_url', '<>', '')->order('create_timestamp', 'DESC')->limit(1000)->select()->toArray(); 
            return $records;            
  
    }
}