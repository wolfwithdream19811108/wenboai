<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\portal\controller;

use api\portal\model\PortalPostModel;
use cmf\controller\RestBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\model\UserCategoryModel;
use api\portal\model\UserModel;
use app\portal\service\ApiService;
use api\portal\model\UserArticleLogModel;
use plugins\message_board\model\PluginMessageBoardModel;
use think\Db;



use AlibabaCloud\SDK\Captcha\V20230305\Captcha;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Captcha\V20230305\Models\VerifyIntelligentCaptchaRequest;

class UserController extends RestBaseController
{
    protected $postModel;

    public function __construct(PortalPostModel $postModel)
    {
        parent::__construct();
        $this->postModel = $postModel;
    }

    /**
     * 会员文章列表
     */
    public function articles()
    {
        $userId   = $this->request->param('user_id', 0, 'intval');

        if(empty($userId)){
            $this->error('用户id不能空！');
        }

        $data     = $this->request->param();
        $articles = $this->postModel->setCondition($data)->where(['user_id' => $userId])->select();

        if (count($articles) == 0) {
            $this->error('没有数据');
        } else {
            $this->success('ok', ['list' => $articles]);
        }

    }
    
       /**
	 * 推荐文章列表
	 */
	public function myRecommend()
	{
	//	$userId = $this->getUserId();
		$userId =10;

		
		$page                 = $this->request->param('page');
		$category                 = $this->request->param('category');
		$randNumPerCategory = 5;//默认每个类别随机选5个
		$articleIdArray = array();
		$likecategories = array();

/*        $UserCategoryModel = new UserCategoryModel();
        $result = $UserCategoryModel->where('user_id', $userId)->column('categoryid');
        $categories = implode(",",$result);	
	
		$params = array();
		$params['category_ids'] = $categories;
		$params['page'] = 60;
		$ApiService = new ApiService();
	   
		
		$data = ApiService::recommendArticles($params);

		$this->success('请求成功!',  ['list' => $data['articles']]);*/
		
		
		
		
		

	
	    if(empty($category) || $category==0){
	        //如果用户没有选择类别，则从过往行为获得兴趣类别信息，如果选择类别，则不获得兴趣类别了
    		
    		$likeInfoList =  Db::name('like_category')->where('uid',$userId)->order('likenum DESC')->select()->toArray();
    		$count = 0;
    		//先从历史操作中选出5个喜欢最多的类别
    		foreach ($likeInfoList as $key => $likeInfo){
    			if($likeInfo['categoryid']!=102 && $likeInfo['categoryid']!=105){//暂时不要书法和碑帖
    				if($count < 5){
    					array_push($likecategories,$likeInfo['categoryid']);
    					$count = $count + 1;
    				}				
    			}
    
    		}
    
    		
    		
    		$allCategories = Db::name('portal_category')->select()->toArray();
    		//再从剩下的，随机选出5个，一共组合成10个兴趣。
    		for($index = 0; $index < count($allCategories); $index++){
    			if(count($likecategories)>10){
    				break;
    			}
    			$key = rand(0,count($allCategories)-1);
    			$categoryid = $allCategories[$key]['id'];
    			if(!in_array($categoryid,$likecategories)  && ($categoryid!=102) && ($categoryid!=105)){//暂时不要书法和碑帖
    				//如果不在要推荐的categories里则加入
    				array_push($likecategories,$categoryid);
    				
    			}
    		}
	        
	    }else{
	        array_push($likecategories,$category);
	        $randNumPerCategory = 30;//只有一个类别则随机选择30个
	    }
	

		$ids = Db::name('portal_museum')->field('id')->where(['is_recommend' => 1])->select()->toArray();
		$museumids = array();
		foreach ($ids as $key => $value){
		    array_push($museumids,$value['id']);
		}

        $where['post_museum_id'] = ['in',$museumids] ;    
        $where['has_desc'] = 1;
   
		foreach ($likecategories as $key => $categoryid){
				
				$articleIds = cache('api_articleidofcategory_'.json_encode($categoryid));
			    $where['post_category_id'] = $categoryid;
                if(empty($articleIds)){
                    $articleIds = Db::name('portal_post')->field('id')->where($where)->orderRaw("rand()")->limit(50000)->select()->toArray();
                    cache('api_articleidofcategory_'.json_encode($categoryid), $articleIds);
                } 
 
                if(!empty($articleIds) ){
                    if(count($articleIds) > $randNumPerCategory){
                        $randkeys = array_rand($articleIds,$randNumPerCategory);//随机选出5个
                    }else{
                        $randkeys = array_rand($articleIds,count($articleIds));//不够选择
                    }
                    foreach($randkeys as $key){
                        array_push($articleIdArray,$articleIds[$key]['id']);
                    }
                    
     
                }
                $articleIds = null;
		}
		
		
		
		$articleIds = implode(',',$articleIdArray);
	    $where = [
            'delete_time' => 0
        ];
		$where['id'] = array('in',$articleIds);
		$where['quality'] = array('gt',10);//质量大于10*100k
		
        $articles =  Db::name('portal_post')->where($where)->select()->toArray();
        $list = array();
        foreach ($articles as $key => $article){
            $article['more'] = json_decode( $article['more']);
           // $more = json_encode($article['more']);
            $more = $article['more'];
            
            $article['more']->thumbnail=cmf_get_image_url_with_token($article['more']->thumbnail,'!longimg');
            array_push($list,$article);
        }
        
        shuffle($list);

		
		
		
		

       // dump($categories);
       
/*		shuffle($categories);
		$params = array();
		$params['category_ids'] = $categories;
		$params['page'] = 30;
		$params['rand'] = true;
		$ApiService = new ApiService();
		$data = ApiService::recommendArticles($params);*/
		
		

	//	$list = $data['articles'];


		$this->success('请求成功!',  ['list' => $list]);
		
		
		
		
		
		
		
		
	}
	
	
	    /**
	 * 推荐文章列表
	 */
	public function doLikeWork()
	{
		$userId = $this->getUserId();
	//	$userId = 10;
		$postId = $this->request->param('postid');
		$categoryid = $this->request->param('categoryid');

		$likeInfo['uid'] = $userId;
		$likeInfo['categoryid'] = intval($categoryid);
		
		$result = Db::name('like_category')->where($likeInfo)->find();
		if(empty($result)){
			$likeInfo['likenum'] = 1;
			Db::name('like_category')->insert($likeInfo);
		}else{
			Db::name('like_category')->where($likeInfo)->setInc('likenum');
		}
		
		
		
/*		$likeInfo['postid'] = $postId;
		$result = Db::name('like_works')->where($likeInfo)->find();
		
		if(empty($result)){
			$likeInfoList =  Db::name('like_works')->where('uid',$userId)->order('time DESC')->select()->toArray();
			dump($likeInfoList);
			if(count($likeInfoList) >= 5 ){
				//删除最后也就是time最大的那条
				Db::name('like_works')->where('id',$likeInfoList[0]['id'])->delete();
			}			
			$likeInfo['time'] = time();
			$likeInfo['categoryid'] = $categoryid;
			Db::name('like_works')->insert($likeInfo);			
			
		}else{
			//已存在，则更新时间
			$result = Db::name('like_works')->where($likeInfo)->update(['time' => time()]);
		}
		*/
		
	
		$this->success('请求成功!');
	}
	
	   	/**
	 * 我的浏览记录
	 */
	public function myArticleHistory()
	{
		$userId = $this->getUserId();
	//	$userId = 1;
		$UserArticleLogModel = new UserArticleLogModel();
		$data['userid'] = $userId;

	    $result = $this->postModel->alias('post')->join('__USER_ARTICLE_LOG__ articleLog', 'articleLog.articleid=post.id')->where(["articleLog.userid"=>$userId])->order('articleLog.timestamp desc')->limit(100)->select();
		
		$this->success('success',$result);
	}
	       /**
	 * 推荐文章列表
	 */
	public function updateMembership()
	{
		$userId = $this->getUserId();
		$userModel = new UserModel();
		$data['member'] = 1;
		$data['member_expire']=time()+366*24*3600;
		$userModel->updateUser($data,$userId);
		
		$this->success('请求成功!');
	}
	
	public function getImageIsToken()
	{
		$userId = $this->getUserId();
		$userModel = new UserModel();
		$data['ImageIsToken'] = true;
		
		$this->success('请求成功!',$data['ImageIsToken']);
	}


	public function updateUserArticleLog()
	{
		
		$userId = $this->getUserId();
		$articleId = $this->request->param('articleId', 0, 'intval');
		


		$UserArticleLogModel = new UserArticleLogModel();
		$data['articleid']=$articleId;
		$data['userid'] = $userId;
		
		$result = $UserArticleLogModel->where($data)->find();
		if($result){
			$UserArticleLogModel->where($data)->update(['timestamp' => time()]);
		}else{
			$data['timestamp'] = time();
			$UserArticleLogModel->addArticleLog($data);
		}
		
		$this->success('success','更新浏览记录成功');
		
	}
	
	public function addFeedBack()
	{
		$userId = $this->getUserId();
        $data = $this->request->param();
		// 更新
		$data['cid'] = 1;
		$data['create_time'] = time();
		$data['uid'] = $userId;
		$DataModel = new PluginMessageBoardModel();
		$DataModel->allowField(true)->data($data, true)->save(); 
        $this->success('success','留言成功');
		
	}
	
/*    public function captcha(){
        $CaptchaVerifyParam = $this->request->param("captchaVerifyParam");
        $CaptchaVerifyParam = html_entity_decode($CaptchaVerifyParam, ENT_QUOTES, 'UTF-8');
        $config = new Config([]);
        // 设置您的AccessKey ID 和 AccessKey Secret。
        // getEnvProperty只是个示例方法，需要您自己实现AccessKey ID 和 AccessKey Secret安全的获取方式。
        $config->accessKeyId = "REMOVED_ACCESS_KEY";
        $config->accessKeySecret = 'REMOVED_SECRET';
        // 设置请求地址 国内调用地址 captcha.cn-shanghai.aliyuncs.com   新加坡调用地址 captcha.ap-southeast-1.aliyuncs.com
        $config->endpoint = "captcha.cn-shanghai.aliyuncs.com";
        // 设置连接超时为5000毫秒
        $config->connectTimeout = 5000;
        // 设置读超时为5000毫秒
        $config->readTimeout = 5000;
        // ====================== 2. 初始化客户端（实际生产代码中建议复用client） ======================
        $client = new Captcha($config);
        // 创建APi请求
        $request = new VerifyIntelligentCaptchaRequest([]);
        // 本次验证的场景ID，建议传入，防止前端被篡改场景
        $request->sceneId = "1doig5bf";
        // 前端传来的验证参数 CaptchaVerifyParam
        $request->captchaVerifyParam = $CaptchaVerifyParam;
        // ====================== 3. 发起请求） ======================
        try {
            $resp = $client->verifyIntelligentCaptcha($request);

            $captchaVerifyResult = $resp->body->result->verifyResult;
            // 原因code
            $captchaVerifyCode = $resp->body->result->verifyCode;

        }
        catch (Exception $error) {

            $captchaVerifyResult = false;
        }        
        $this->success('ok', $resp->body->result);
        
                
    }*/
    public function captcha()
{
    if (!$this->request->isPost()) {
        $this->error('请求错误');
    }

    $captchaVerifyParam = $this->request->post(
        'captchaVerifyParam',
        '',
        null
    );

    $result = cmf_verify_aliyun_captcha(
        $captchaVerifyParam
    );

    $this->success('ok', $result);
}

}
