<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\ai\controller;
require_once VENDOR_PATH. 'upyun/vendor/autoload.php';
use Upyun\Upyun;
use Upyun\Config;
use cmf\controller\RestBaseController;
use cmf\controller\PluginBaseController;
use plugins\qiniu\lib\Qiniu;
use app\user\model\UserPointsModel;
use app\ai\model\AiUserWorksModel;
use app\ai\service\ImageGenerationConfig;
use app\imageai\service\AccessService;
use think\Db;
use think\Request;
use api\user\model\UserModel;

class AiimageController extends RestBaseController
{

    private $aliApiKey = '';
    private $moonshotApiKey = '';
    private $aliApiUrl = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';

    public function __construct()
    {
        parent::__construct();
        $this->aliApiKey = $this->envValue('DASHSCOPE_API_KEY');
        $this->moonshotApiKey = $this->envValue('MOONSHOT_API_KEY');
    }

    private function envValue($name, $default = '')
    {
        return cmf_service_config($name, $default);
    }

    /**
     * 获取model信息
     */
    public function create()
    {
        if (cmf_is_user_login() && AccessService::isApiOnly(cmf_get_current_user_id())) {
            return ApiHelper::error('此账号仅支持通过 API 创建图片', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Invalid request method.';
        }

        // 读取原始POST数据
        $rawData = file_get_contents('php://input');

        // 检查是否是JSON格式
        if (!json_decode($rawData)) {
            return 'Invalid JSON format.';
        }

        // 解析JSON数据
        $data = json_decode($rawData, true);
        // 检查prompt参数是否存在
        if (!isset($data['auth'])) {
            return 'Missing auth parameter.';
        }
        // 获取prompt参数
        $auth = $data['auth'];

        if (!isset($data['isPro'])) {
            return 'Missing isPro parameter.';
        }

        // 根据是否是专业版选择不同的URL
        //$isPro = isset($_POST['isPro']) && $_POST['isPro'] === 'true';
        $isPro = $data['isPro'];
        $url = $isPro ? 'https://gate.wujieai.net/wj-open/v1/ai/pro/create' : 'https://gate.wujieai.net/wj-open/v1/ai/create';

        $picNum = 0;
        if (isset($data['num'])) {
            $picNum = $data['num'];
        }
        if (isset($data['batch_count'])) {
            $picNum = $data['batch_count'];
        }
        if(!$picNum){
             return 'Missing num or batch_count parameter.';
        }


        $return = array(
                    "code" => "200",
                    "message" => "积分不够生成，请先充值!",
                    "success" => false
                );

        if (cmf_is_user_login()) {
            $userid = cmf_get_current_user_id();
            $UserPointsModel = new UserPointsModel();
            $currentPoints = $UserPointsModel->getUserPoints($userid);
            $subtractPoints = $picNum *50;//一张图片消耗50点

            if($currentPoints-$subtractPoints < 0){
                $return['data']['message'] = "积分不够生成，请先充值!";
                $return = json_encode($return);
                return json_decode($return);
            }

        } else {
                $return['data']['message'] = "用户未登录，请先登陆再生成";
                $return = json_encode($return);
                return json_decode($return);
        }










        // 初始化cURL会话
        $ch = curl_init($url);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ]);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // 检查cURL错误
        if ($error) {
            return 'cURL Error: ' . $error;
        }
        $response = str_replace('\\', "", $response);

        // 返回服务器的响应
        return json_decode($response);

    }
    public function getImageStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Invalid request method.';
        }

        // 读取原始POST数据
        $rawData = file_get_contents('php://input');

        // 检查是否是JSON格式
        if (!json_decode($rawData)) {
            return 'Invalid JSON format.';
        }

        // 解析JSON数据
        $data = json_decode($rawData, true);
        if (!isset($data['auth'])) {
            return 'Missing auth parameter.';
        }
        $auth = $data['auth'];

        if (!isset($data['key'])) {
            return 'Missing key parameter.';
        }
        $key = $data['key'];

/*        $key = "E60D50813380B5CD9BE7A4609CDD9578";
        $auth = '{"appId":"wjok9a9wy8812a9h3b","secretKeyVersion":"1","sign":"tMX85suaTajgBeYld8n18OLCyAr9nZK3puTXrinnir46fnAd0NMPPd/MWDCcuXQXH0nkeen26fW1aEZCfeT0Wg==","original":"{\"appId\":\"wjok9a9wy8812a9h3b\",\"timestamp\":\"1714912999\"}"}';*/
        $queryUrl = "https://gate.wujieai.net/wj-open/v1/ai/pro/info?key=" . urlencode($key);
        // 初始化cURL会话  get请求


        $ch = curl_init($queryUrl);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回而不是输出内容
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ]);
        curl_setopt($ch, CURLOPT_HEADER, false); // 不返回HTTP头


        // 执行cURL请求并获取响应
        $response = curl_exec($ch);


        // 检查cURL错误
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            // 处理错误，例如记录日志或返回错误信息
            // error_log("cURL error when fetching from server: " . $error);
            curl_close($ch);
            return json_decode($error); // 或者返回一个错误响应
        }

        // 关闭cURL资源，释放内存
        curl_close($ch);


        $response = str_replace('\\', "", $response);


        $data = json_decode($response, true);
        $pictureUrl = $data['data']['picture_url'] ?? null;
        if($pictureUrl){
            $upyunImageUrl = $this->uploadUpyun($pictureUrl);
            if($upyunImageUrl){
                $data['data']['picture_url'] = $upyunImageUrl;
                // 重新编码为JSON字符串（替换原始$response）
                $response = json_encode($data);
            }
        }


        // 返回服务器的响应
        return json_decode($response);

    }

    public function getGeneratingInfo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Invalid request method.';
        }

        // 读取原始POST数据
        $rawData = file_get_contents('php://input');


        // 检查是否是JSON格式
        if (!json_decode($rawData)) {
            return 'Invalid JSON format.';
        }

        // 解析JSON数据
        $data = json_decode($rawData, true);
        // 检查必要参数
        if (!isset($data['keys'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing keys parameter.']);
        }
        // 检查必要参数
        if (!isset($data['auth'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing auth parameter.']);
        }
        $auth = $data['auth'];
        $keys = $data['keys'];


        $queryUrl = "https://gate.wujieai.net/wj-open/v1/ai/pro/generating_info";


        // 初始化cURL会话
        $ch = curl_init($queryUrl);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($keys));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ]);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);



        // 处理响应数据
        $responseData = json_decode($response, true);


        // 检查是否有infos数组
        if (isset($responseData['data']['infos']) && is_array($responseData['data']['infos'])) {
            foreach ($responseData['data']['infos'] as &$info) {
                if (!empty($info['picture_url'])) {
                    // 上传到又拍云并获取新URL
                    $upyunImageUrl = $this->uploadUpyun($info['picture_url']);
                    if ($upyunImageUrl) {
                        $info['picture_url'] = $upyunImageUrl;
                    }
                }
            }
            unset($info); // 解除引用

            // 重新编码为JSON
            $response = json_encode($responseData);
        }



        http_response_code($httpCode);
        header('Content-Type: application/json');
        // 直接返回响应内容
        echo $response;
        exit;



    }


    public function acclearate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'Invalid request method.';
        }

        // 读取原始POST数据
        $rawData = file_get_contents('php://input');


        // 检查是否是JSON格式
        if (!json_decode($rawData)) {
            return 'Invalid JSON format.';
        }

        // 解析JSON数据
        $data = json_decode($rawData, true);
        // 检查必要参数
        if (!isset($data['key'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing key parameter.']);
        }

        // 检查必要参数
        if (!isset($data['auth'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing auth parameter.']);
        }
        $auth = $data['auth'];
        // 从$data中移除auth参数（如果存在）
        if (isset($data['auth'])) {
            unset($data['auth']);
        }

        $queryUrl = "https://gate.wujieai.net/wj-open/v1/ai/accelerate";


        // 初始化cURL会话
        $ch = curl_init($queryUrl);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Apifox/1.0.0 (https://apifox.com)',
            'Authorization: ' . $auth
        ]);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        http_response_code($httpCode);
        header('Content-Type: application/json');
        // 直接返回响应内容
        echo $response;
        exit;



    }

    public function moonshot()
    {

        // 只接受POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        // 读取原始POST数据
        $rawData = file_get_contents('php://input');

        // 检查是否是JSON格式
        if (!json_decode($rawData)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON format']);
            exit;
        }

        // 解析JSON数据
        $requestData = json_decode($rawData, true);

        if ($this->moonshotApiKey === '') {
            http_response_code(401);
            echo json_encode(['error' => 'Moonshot API key is not configured']);
            exit;
        }

        $apiUrl = "https://api.moonshot.cn/v1/chat/completions";

        // 初始化cURL会话
        $ch = curl_init($apiUrl);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->moonshotApiKey
        ]);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 如果有错误
        if ($error) {
            http_response_code(500);
            echo json_encode(['error' => $error]);
            exit;
        }

        // 设置HTTP状态码并返回响应
        http_response_code($httpCode);
        echo $response;
        exit;

    }

    public function suggestWork()
        {
            // 只接受POST请求
            if (!$this->request->isPost()) {
                http_response_code(405);
                header('Content-Type: application/json');
                die(json_encode([
                    'status' => 'error',
                    'code' => 405,
                    'message' => '仅支持POST请求'
                ]));
            }


            // 获取POST数据
            $input = $this->request->post();
            $imageUrl = $input['image'] ?? '';

            if (empty($imageUrl)) {
                http_response_code(400);
                header('Content-Type: application/json');
                die(json_encode([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'image参数不能为空'
                ]));
            }

            try {
                $prompt = "
                  请对此图像从建筑设计专业角度进行专业分析：

                ";

                $messages = [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageUrl
                                ]
                            ]
                        ]
                    ]
                ];



                $this->streamDashscopeApi($this->$aliApiKey, 'qvq-max', $messages);

                exit;

            } catch (Exception $e) {
                // 流式输出错误信息
                echo "data: [ERROR] " . json_encode([
                    'status' => 'error',
                    'code' => $e->getCode() ?: 500,
                    'message' => $e->getMessage()
                ]) . "\n\n";
                flush();
                exit;
            }
        }
        /**
         * 流式调用DashScope API
         * @param string $apiKey
         * @param string $model
         * @param array $messages
         * @param array $options
         * @throws Exception
         */
        private function streamDashscopeApi($apiKey, $model, $messages, $options = [])
        {
            $url = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';

            $data = array_merge([
                'model' => $model,
                'enable_thinking' => true,
                'messages' => $messages,
                'stream' => true  // 启用流式输出
            ], $options);

            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ];
           // 禁用输出缓冲并确保立即刷新
            ob_end_clean(); // 清除现有缓冲区
            ini_set('output_buffering', 'off');
            ini_set('implicit_flush', true);
            ob_implicit_flush(true);

            // 设置流式输出头
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');




            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_WRITEFUNCTION => function($ch, $data) {
                    echo $data;
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    return strlen($data);
                },
                CURLOPT_TIMEOUT => 300,
                CURLOPT_CONNECTTIMEOUT => 300,
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception("API请求失败: " . curl_error($ch), 500);
            }

            curl_close($ch);
        }


    public function recordWorksKey()
    {
        if (cmf_is_user_login() && AccessService::isApiOnly(cmf_get_current_user_id())) {
            return ApiHelper::error('此账号仅支持通过 API 创建图片', 403);
        }
        $aiUserWorksModel = new AiUserWorksModel();
        $keyArray = $this->request->post();

        if (cmf_is_user_login()) {
            $userid = cmf_get_current_user_id();
            $result = $aiUserWorksModel->addUserWorkArray($userid,$keyArray);
            if($result){
                $this->success('增加用户作品记录成功',$result);
            }else{
                $this->error("增加用户作品记录失败");
            }

        } else {
            $this->error("此用户未登录!");
        }


    }
    public function getUserAikey(){
            $userModel       = new UserModel();
            $userid = cmf_get_current_user_id();
            $userData        = $userModel->find($userid);
            $appIdIndex = $userData['appid_index'];
            $list = Db::name('ai_wj_appid')->where('id', '=', $appIdIndex)->find();

            $this->success('请求成功!', $list);
    }
    public function getAiModels()
    {



        $list = cache('api_ai_getAiModels');
        if(empty($list)){
            $list = Db::name('portal_aiimage_model')->where('model_type', '=', "craft")->where('open', '=', "1")->select()->toArray();
			cache('api_ai_getAiModels', $list);
        }

        $response ['list'] =  $list;

        $this->success('请求成功!', $response);

    }
    public function updateWorksInfo()
    {
        if (cmf_is_user_login() && AccessService::isApiOnly(cmf_get_current_user_id())) {
            return ApiHelper::error('此账号仅支持通过 API 创建图片', 403);
        }
        $aiUserWorksModel = new AiUserWorksModel();
        $params = $this->request->post();
        $key = $params['key'];
        $worksInfo = $params['worksInfo'];

        if (cmf_is_user_login()) {
            $userid = cmf_get_current_user_id();
            $result = $aiUserWorksModel->updateWorkInfo($key,$worksInfo);
            if($result){
                $this->success('更新用户作品记录成功');
            }else{
                $this->error("更新用户作品记录失败");
            }

        } else {
            $this->error("此用户未登录!");
        }


    }

    public function getUserPoints()
    {


        $UserPointsModel = new UserPointsModel();

        if (cmf_is_user_login()) {
            $userid = cmf_get_current_user_id();
            $result = $UserPointsModel->getUserPoints($userid);
            if($result){
                $this->success('获得积分成功!',$result);
            }else{
                $this->error("获得积分失败!");
            }

        } else {
            $this->error("此用户未登录!");
        }

    }

    /**
     * 获取model信息
     */
    public function getModelInfo()
    {
        $params = $this->request->get();
        $model_id = isset($params['model_id']) ? intval($params['model_id']) : 0;
        $data = Db::name('portal_aiimage_model')->where('id', '=', $model_id)->select()->toArray();
        $response['list'] = $data;
        $this->success('请求成功!', $response);


    }




       /**
     * 显示model prompt
     */
    public function getModelPrompt()
    {
        $params = $this->request->get();
        $model_id = isset($params['model_id']) ? intval($params['model_id']) : 0;
        $data = Db::name('portal_prompt_tag')->where('model_id', '=', $model_id)->where('delete', '=', 0)->field('prompt_category,tag_pre_prompt,tag')->select()->toArray();
        // 创建一个新的数组，用于存储转换后的数据
        $transformedData = [];

        // 遍历查询结果，并对每个元素进行处理
        foreach ($data as $item) {
            // 将 prompt_category 和 tag_pre_prompt 拼接，并创建一个新对象
            $transformedItem = [
                'pre_prompt' => $item ["prompt_category"] . (!empty($item ["tag_pre_prompt"]) ? ',' . $item ["tag_pre_prompt"] : ''),
                'tag_prompt' => $item ["tag"]
            ];
            // 将新对象添加到结果数组中
            $transformedData[] = $transformedItem;
        }


        $response['list'] = $transformedData;
        $this->success('请求成功!', $response);


    }
    public function getEverything(){
        $category = $this->request->get('category', null, 'string');
        $everythingimgArray = Db::name('portal_aiimage_everything')->where('category', '=', $category)->select()->toArray();
        $response ['list'] =  $everythingimgArray;
        $this->success('请求成功!', $response);
    }
    public function getPoemSentence(){
        $category = $this->request->get('category', null, 'string');
        $poem = Db::name('ai_poemtoart')->where('category', '=', $category)->orderRaw('rand()')->find();
        $response ['list'] =  $poem;
        $this->success('请求成功!', $response);
    }
     public function getBookDesign(){
        $category = $this->request->get('category', null, 'string');
        $book = $this->request->get('book', null, 'string');
        $where['category'] = $category;
        $where['book'] = $book;
        $bookDesignArray = Db::name('ai_booktoart')->where($where)->orderRaw('rand()')->limit(10)->select()->toArray();
        $response ['list'] =  $bookDesignArray;
        $this->success('请求成功!', $response);
    }
     public function getPoemDesign(){
        $category = $this->request->get('category', null, 'string');
        $poem = $this->request->get('poem', null, 'string');
        $poemmode = $this->request->get('poemmode', null, 'string');
        if($poemmode=='poemsea'){
            $DesignArray = Db::name('ai_poemtoart')->orderRaw('rand()')->limit(20)->select()->toArray();
        }else{
            $where['category'] = $category;
            $where['poem'] = $poem;
            $DesignArray = Db::name('ai_poemtoart')->where($where)->orderRaw('rand()')->select()->toArray();
        }
        $response ['list'] =  $DesignArray;
        $this->success('请求成功!', $response);

    }
    public function getMyWorks() {
        // 从请求中获取参数
        $userId = $this->request->get('uid', null, 'int');
        $page = $this->request->get('page', 1, 'int'); // 默认第一页
        $pageSize = $this->request->get('pagesize', 100, 'int'); // 默认每页10条

        if ($userId) {
            // 获取分页数据
            $worksItems = Db::name('ai_user_works')
                ->where('uid', $userId)
                ->where('picture_url', '<>', '')
                ->order('id', 'DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            // 返回分页数据
            $result = [
                'worksItems' => $worksItems,
                'page' => $page,
                'pageSize' => $pageSize
            ];
            return json($result);
        } else {
            $this->error("用户未登录!请先登陆");
        }
    }

   public function getDesignElements() {
        // 从请求中获取参数
        $page = $this->request->get('page', 1, 'int'); // 默认第一页
        $pageSize = $this->request->get('pagesize', 100, 'int'); // 默认每页10条
        $category = $this->request->get('category', null, 'string'); // 默认每页10条

        // 获取分页数据
        $worksItems = Db::name('portal_pattern')
            ->where('category', $category)
            ->order('id', 'DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        // 返回分页数据
        $result = [
            'list' => $worksItems,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return json($result);
    }
    public function uploadImage()
    {
        // 获取请求对象
        $request = Request::instance();
        // 获取上传的文件对象
        $file = $request->file('image');

        // 验证文件是否上传成功
        if ($file) {
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $filePath = '/uploads/' . $info->getSaveName();
                return json(['status' => 1, 'message' => '上传成功', 'data' => $filePath]);
            } else {
                // 上传失败获取错误信息
                return json(['status' => 0, 'message' => $file->getError()]);
            }
        } else {
            return json(['status' => 0, 'message' => '没有文件被上传']);
        }
    }
    public function uploadUpyun($imageUrl){
        $keys = ImageGenerationConfig::keys();
        $settings = ImageGenerationConfig::settings();
        if (empty($keys['upyun_service']) || empty($keys['upyun_operator']) || empty($keys['upyun_password'])) {
            return null;
        }
        $serviceConfig = new Config($keys['upyun_service'], $keys['upyun_operator'], $keys['upyun_password']);
        $client = new Upyun($serviceConfig);
        // 3. 直接下载内容并上传
        $remotePath = '/works/' . basename($imageUrl); // 又拍云保存路径
        $fileContent = file_get_contents($imageUrl); // 直接读取远程文件
        $uploadInfo = $client->write($remotePath, $fileContent); // 上传字符串内容
        if($uploadInfo){
            return rtrim($settings['storage']['upyun_domain'], '/').$remotePath;
        }else{
            return null;
        }

    }

    public function isLogin()
    {

        if (cmf_is_user_login()) {
            $response ['user'] =  cmf_get_current_user();
            $this->success("请求成功",$response);
        } else {
            $this->success("请求成功",null);
        }
    }


}
