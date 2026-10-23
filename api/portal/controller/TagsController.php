<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use api\portal\model\PortalPostModel;
use cmf\controller\RestBaseController;
use api\portal\model\PortalTagModel;
use app\portal\model\PortalPostTagModel;
use think\Db;

class TagsController extends RestBaseController
{


    /**
     * 获取标签列表
     */
    public function getTagTable()
    {
        $params = $this->request->get();
        if(isset($params['minworks'])){ 
            $minworks = $params['minworks'];
        }else {
            $minworks = 100;//默认100件以上作品
        }          
        
        
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
        $data = cache('api_tagtable_'.json_encode($params));
        if(empty($data)){
			$data = Db::name('portal_tag_table')->where('worksnum', '>', $minworks)->where('valid', '=', 1)->field('tagname')->select(true)->toArray();


			//dump($data);
            $data = array_values(array_column($data, 'tagname'));
			cache('api_tagtable_'.json_encode($params), $data);
			$cacheflag=false;
        }  
        


        $response ['list'] =  $data;
        $response['cacheflag']=$cacheflag;

        

        $this->success('请求成功!', $response);
    }

    /**
     * 获取热门标签列表
     */
    public function hotTags()
    {
        $params                         = $this->request->get();
        $params['where']['recommended'] = 1;
        $data                           = $this->tagModel->getDatas($params);

        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = $data;
        } else {
            $response = ['list' => $data,];
        }
        $this->success('请求成功1!', $response);
    }
    


    /**
     * 获取标签文章列表
     * @param int $id
     */
    public function articles()
    {
        $params = $this->request->get();
        $tagname                  = $this->request->param('tagname');
        $page                  = $this->request->param('page');
        $cacheflag=true;
		$data = cache('api_getTagPostLists_'.json_encode($params));
        if(empty($data)){
			$portalTagModel = new PortalPostTagModel();
    		$data = $portalTagModel->getTagPostLiteForApi($tagname,$page);
			cache('api_getTagPostLists_'.json_encode($params), $data);
			$cacheflag=false;
        }  

        $response ['list'] =  $data;
        $response['cacheflag']=$cacheflag;

        

        $this->success('请求成功!', $response);
    }
      /**
     * 获取标签列表
     */
    public function getTagTable2()
    {
        $params = $this->request->get();
        if(isset($params['minworks'])){ 
            $minworks = $params['minworks'];
        }else {
            $minworks = 100;//默认100件以上作品
        }          
        $data = null;
        //$before = memory_get_usage();
        $wordarray = array('作品','油画','资料','乾隆','山水','民国','主题','时代','铜钱','南宋','青花','印记','题跋','花卉','人物','技法','文字','类型','质地','时间','铜印','展览','全国','作品集','佚名','行书','书体','楷书','瓷碗','书法','创作','素描','口沿','现代','款识','北宋','美国','青白','青玉','白描','拓片','故宫博物院','形式','参考资料','插画','华盛顿国立美术馆','作者','书法篆刻','伦勃朗','装裱','宝笈','白釉','书画','设色','鼻烟壶','中国','花鸟','水彩','铜镜','速写','时期','艺术','康熙','浮雕','新石器时代','雕塑','粉彩','花纹','吉州窑','花瓶','残片','画家','白玉','牡丹','珊瑚','彩绘','剔红','龙纹','穆夏','类别','圆形','皮影','版画','图案','痕迹','刻划','色彩','南宋青','绘画','雍正','装饰','中华民国','如意','草书','篆书','乾隆通宝','青瓷','纹饰','珐琅','墓志','肖像','粉盒','宋代','铁锈','全文','元宝','故宫','建筑','王羲之','平底','战国','印花','台北巿','通宝','图册','釉瓷','方孔','惠斯勒','东汉','圆钱','釉面','晚期','口径','云纹','先生','清末','年号','鎏金','国画','简介','扇面','双耳','莲花','中央','参展','清代','隶书','掐丝珐琅','白色','西汉','腹部','方形','文物','大观帖','镀金','蓝色','文化','木雕','陶罐','拓本','黄色','蝴蝶','器用','特展','梅花','硬币','西班牙','齐白石','光绪','日本');
          //先从cache里查，如果有值，直接返回，如果没值，再继续执行，从mysql里查
        $cacheflag = true;  
        $data = cache('api_tagtable_all');
        if(empty($data)){
			$data = Db::name('portal_tag_table')->where('worksnum', '>', $minworks)->where('valid', '=', 1)->field('tagname')->select(true)->toArray();
            $data = array_values(array_column($data, 'tagname'));
			cache('api_tagtable_all');
			$cacheflag=false;
        } 
  // 从原始数组中随机取出1000个元素
$keys = array_rand($data, 100);

// 组成新数组
$wordarray = array();
foreach ($keys as $key) {
    $wordarray[] = $data[$key];
}      
        
       // $after = memory_get_usage();
        //dump(($after-$before)/1024/1024);
list($msec, $sec) = explode(' ', microtime());
$before = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
dump($before);
foreach ($wordarray as $key => $value) {
    if (in_array($value, $data)) {
        $wordarray[$key] = $wordarray[$key]."22";
    }
}
list($msec, $sec) = explode(' ', microtime());
$after = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

dump($after);
dump($after-$before);




        $response ['list'] =  $data;
        $response['cacheflag']=$cacheflag;

        

       // $this->success('请求成功!', $response);
    }
    
     public function handlefenci()
    {
        $fenciarray = $this->request->param('fencilist');
    
        $fenciarray = json_decode(str_replace('&quot;', '"', $fenciarray));
        
        //$tagArray = cache('api_tagtable_all');
        if(empty($tagArray)){
			$tagArray = Db::name('portal_tag_table')->where('worksnum', '>', 100)->where('valid', '=', 1)->field('tagname')->select(true)->toArray();
            $tagArray = array_values(array_column($tagArray, 'tagname'));
			cache('api_tagtable_all',$tagArray);
        }         
        $fenciresult = array();
        
        
foreach ($fenciarray as $key => $value) {
    if (in_array($value, $tagArray)) {
        array_push($fenciresult,$value);
    }
}        
        
/*   $fenciresult = array_filter($fenciarray, function($value) use ($tagArray) {
        return in_array($value, $tagArray);
    });   */    
        
        $response['list'] =  $fenciresult;


        

        $this->success('请求成功!', $response);
    }
}
