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
namespace app\portal\controller;


use cmf\controller\HomeBaseController;
use app\portal\model\PortalKnowledgeModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;
class KnowledgeController extends HomeBaseController
{
    public function index()
    {
        $portalKnowledgeModel = new PortalKnowledgeModel();
        $tagsGroup = array();
        $knowledeArray=array('窑口','玉器','酒器','食器','兵器','乐器','新石器时代','文房','巴蜀画派','北宋三大家','海上画派','京津画派','岭南画派','六朝三杰','明四家','南宋四大家','四僧画派','四王画派','五代四大家','新金陵画派','扬州画派','元四家','长安画派','浙派三大家','家具','禁止出国文物');
        foreach ($knowledeArray as $index => $knowledge){
            $tags = $portalKnowledgeModel->where('knowledge',$knowledge)->select();
            $tagsGroup[$index]['knowledge']=$knowledge;
            $tagsGroup[$index]['list']=$tags;
        }
     
        $this->assign('tagsGroup', $tagsGroup);

        $listTpl = 'knowledge_tag_list';
		

        return $this->fetch('/' . $listTpl);


    }




}
