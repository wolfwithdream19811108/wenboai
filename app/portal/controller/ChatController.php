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
use think\Db; 
use app\portal\service\PostService;
use app\portal\model\PortalKnowledgeModel;
use app\portal\model\PortalPostTagModel;
use app\portal\model\PortalTagModel;
use app\portal\model\AiChatMessageModel;
use app\portal\model\AiChatConversionModel;
use app\portal\service\ApiService;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Exception;
use think\facade\Request;  // Add this import statement
use think\facade\Json;    // For JSON responses


class ChatController extends HomeBaseController
{
    private $apiKey = '';
    private $apiUrl = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
    
        // 定义后端的 presetContent
    private $defaultSysPrompt = "你是一位在文物与文博领域具有深厚造诣的专家，能够解答任何关于文博、艺术、工艺美术、技艺与文化相关的专业问题。如果不是文博类的问题，你尽可能简短回答,不要超过200字。如果是文博类的问题，你的回答需符合以下要求：
        1、专业性与深度：你提供的回答必须展现出高度的专业性，内容准确、深入，且具备清晰、条理化的结构。每次回答不得少于2000字，确保问题得到充分的阐述与剖析。
        2、回答结构与内容：确保回答的结构清晰，每个部分有明确的主题和小结，涉及文物、艺术或工艺的部分应根据时代背景、文化背景、历史渊源等进行详细阐述。回答应涉及多个层面的探索，能够深入讨论某个概念或现象的历史背景、技术演变、文化意义等。
        3、结合实例与理论：在回答中结合具体的文物、艺术作品或历史事件作为例证，使回答不仅具有理论深度，还能体现实际的应用与实际意义。
        4、跨学科融合：文博领域与历史、文化、哲学等学科息息相关，建议你在回答时能够将这些学科内容结合起来，为读者呈现更全面的学术视角。
        5、文化与技艺传承：对于传统技艺和艺术形式的探讨应着重分析其传承与演变。";
   
    private $yidunKey = '';
    private $secretId = '';
    private $yidunApi = 'http://as.dun.163.com/v5/text/check';
    private $yidunAuditId = '';
    private $yidunNonAuditId = '';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = $this->envValue('DASHSCOPE_API_KEY');
        $this->yidunKey = $this->envValue('YIDUN_SECRET_KEY');
        $this->secretId = $this->envValue('YIDUN_SECRET_ID');
        $this->yidunAuditId = $this->envValue('YIDUN_AUDIT_BUSINESS_ID');
        $this->yidunNonAuditId = $this->envValue('YIDUN_NON_AUDIT_BUSINESS_ID');
    }

    private function envValue($name, $default = '')
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            $value = getenv('PHP_' . $name);
        }

        return ($value === false || $value === '') ? $default : $value;
    }

    public function index()
    {
        $questions = $this->getPreQuestion();
        $functionSettings  = cmf_get_option('function_settings');
        $this->assign("analysis_object", $functionSettings['analysis_object']);
        $this->assign('questions', $questions);
        return $this->fetch('/chatstreamwithhead');


    }
    public function jianyuan()
    {
        $questions = $this->getPreQuestion();
        $this->assign('questions', $questions);
        return $this->fetch('/jianyuan');


    }
    public function openchat()
    {
        $questions = $this->getPreQuestion();
        $this->assign('questions', $questions);  
        return $this->fetch('/chatstream');


    }

    public function getContent()
    {
        $params                       = $this->request->get();
        $articleId  = $this->request->param('id',0, 'intval');
        $postService         = new PostService();
        $article    = $postService->publishedArticle($articleId); 
        $content = $article['post_content'].$article['deep_content'];
        
        // ThinkPHP 返回JSON响应
        return json([
            'status' => 'success',
            'code' => 200,
            'data' => $content,
            'message' => '请求成功'
        ]);
       
    }
    public function getPreQuestion()
    {
        $allQuestions = Db::name('portal_chat_question')
            ->field('question')
            ->select()->toArray();
        
        shuffle($allQuestions); // PHP端随机打乱
        $questions = array_column($allQuestions, 'question');
        return array_slice($questions, 0, 3);

    }
    

    // 2. 获取当前用户的所有会话流
    public function getConversations()
    {
        if(cmf_is_user_login()){
            $uid  = cmf_get_current_user_id();
            $list = AiChatConversionModel::where('uid', $uid)
                ->order('start_time', 'desc')
                ->field('id,title')
                ->select();
            return json($list);
        }else{
            return json(['error' => '用户未登录']);
        }
    }
/**
 * 从服务端登录态获取用户 ID。
 */
private function chatSecurityUserId()
{
    if (!cmf_is_user_login()) {
        return 0;
    }

    $uid = filter_var(
        cmf_get_current_user_id(),
        FILTER_VALIDATE_INT
    );

    return $uid !== false && $uid > 0 ? $uid : 0;
}

/**
 * 严格校验会话 ID，避免 intval 把非法输入转换成有效 ID。
 */
private function chatSecurityConversationId()
{
    $value = $this->request->param('conversation_id');

    if (
        (!is_string($value) && !is_int($value))
        || !preg_match('/^[1-9][0-9]*$/D', (string) $value)
    ) {
        return 0;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    return $id !== false && $id > 0 ? $id : 0;
}

/**
 * 只能读取当前登录用户自己的会话。
 */
public function getMessages()
{
    $uid = $this->chatSecurityUserId();

    if (!$uid) {
        return json(['error' => '用户未登录'], 401);
    }

    $conversationId = $this->chatSecurityConversationId();

    if (!$conversationId) {
        return json(['error' => '会话参数错误'], 400);
    }

    $conversation = AiChatConversionModel::where('id', $conversationId)
        ->where('uid', $uid)
        ->find();

    if (!$conversation) {
        return json(['error' => '会话不存在或无权访问'], 404);
    }

    $messages = AiChatMessageModel::where('conversation_id', $conversationId)
        ->order('sequence', 'asc')
        ->field('role,content,timestamp')
        ->select();

    return json($messages);
}

/**
 * 创建会话必须登录。
 */
public function newConversation()
{
    $uid = $this->chatSecurityUserId();

    if (!$uid) {
        return json(['error' => '用户未登录'], 401);
    }

    $conversationId = AiChatConversionModel::insertGetId([
        'uid'        => $uid,
        'start_time' => time(),
        'title'      => '',
    ]);

    return json([
        'conversation_id' => $conversationId,
    ]);
}

/**
 * 对外保存接口只能保存当前用户自己的 user 消息。
 */
public function saveMessage()
{
    $uid = $this->chatSecurityUserId();

    if (!$uid) {
        return json(['error' => '用户未登录'], 401);
    }

    if (!$this->request->isPost()) {
        return json(['error' => '仅支持POST请求'], 405);
    }

    $conversationId = $this->chatSecurityConversationId();
    $message = $this->request->param('message');
    $role = $this->request->param('role', 'user');

    if (
        !$conversationId
        || !is_string($message)
        || trim($message) === ''
    ) {
        return json(['error' => '会话或消息参数错误'], 400);
    }

    if ($role !== 'user') {
        return json(['error' => '客户端只能提交用户消息'], 400);
    }

    $message = trim($message);

    return Db::transaction(function () use (
        $uid,
        $conversationId,
        $message
    ) {
        // 在写入之前，同时校验会话 ID 和所属用户。
        $conversation = AiChatConversionModel::where('id', $conversationId)
            ->where('uid', $uid)
            ->lock(true)
            ->find();

        if (!$conversation) {
            return json(['error' => '会话不存在或无权访问'], 404);
        }

        $maxSeq = AiChatMessageModel::where(
            'conversation_id',
            $conversationId
        )->max('sequence');

        AiChatMessageModel::create([
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'timestamp'       => time(),
            'sequence'        => $maxSeq ? $maxSeq + 1 : 1,
            'content'         => $message,
        ]);

        if (empty($conversation->title)) {
            $conversation->title = $message;
            $conversation->save();
        }

        return json([
            'conversation_id' => $conversationId,
            'title'           => $conversation->title,
        ]);
    });
}

/**
 * 收集上游 SSE 内容，支持跨网络分块的 JSON 行。
 */
private function chatSecurityCaptureChunk(&$state, $chunk)
{
    $state['buffer'] .= $chunk;

    while (($end = strpos($state['buffer'], "\n")) !== false) {
        $line = rtrim(
            substr($state['buffer'], 0, $end),
            "\r"
        );

        $state['buffer'] = substr($state['buffer'], $end + 1);

        if (strpos($line, 'data:') !== 0) {
            continue;
        }

        $payload = trim(substr($line, 5));

        if ($payload === '[DONE]') {
            continue;
        }

        $data = json_decode($payload, true);

        if (!is_array($data) || isset($data['error'])) {
            $state['valid'] = false;
            continue;
        }

        if (!isset($data['choices'][0])) {
            continue;
        }

        $choice = $data['choices'][0];

        if (isset($choice['delta']['content'])) {
            if (
                !is_string($choice['delta']['content'])
                || $state['finished']
            ) {
                $state['valid'] = false;
            } else {
                $state['content'] .= $choice['delta']['content'];
            }
        }

        if (isset($choice['finish_reason'])) {
            if ($choice['finish_reason'] === 'stop') {
                $state['finished'] = true;
            } else {
                $state['valid'] = false;
            }
        }
    }
}

/**
 * 仅由可信服务端生成流程调用。
 */
private function chatSecurityPersistAssistant(
    $conversationId,
    $uid,
    $content
) {
    return Db::transaction(function () use (
        $conversationId,
        $uid,
        $content
    ) {
        $conversation = AiChatConversionModel::where('id', $conversationId)
            ->where('uid', $uid)
            ->lock(true)
            ->find();

        if (!$conversation) {
            throw new \RuntimeException(
                'Conversation no longer accessible'
            );
        }

        $maxSeq = AiChatMessageModel::where(
            'conversation_id',
            $conversationId
        )->max('sequence');

        AiChatMessageModel::create([
            'conversation_id' => $conversationId,
            'role'            => 'assistant',
            'timestamp'       => time(),
            'sequence'        => $maxSeq ? $maxSeq + 1 : 1,
            'content'         => $content,
        ]);

        if (empty($conversation->title)) {
            $conversation->title = $content;
            $conversation->save();
        }
    });
}

/**
 * 统一执行文本、图片模型的流式请求。
 */
private function chatSecurityExecuteStream($ch, $options)
{
    $conversationId = null;
    $uid = 0;

    // 有会话 ID 时，必须在请求上游之前验证登录和归属。
    // 未提供会话 ID 的临时分析不保存历史。
    if ($this->request->param('conversation_id') !== null) {
        $uid = $this->chatSecurityUserId();
        $conversationId = $this->chatSecurityConversationId();

        if (!$uid || !$conversationId) {
            curl_close($ch);

            throw new \think\exception\HttpResponseException(
                json(
                    [
                        'error' => $uid
                            ? '会话参数错误'
                            : '用户未登录',
                    ],
                    $uid ? 400 : 401
                )
            );
        }

        $conversation = AiChatConversionModel::where('id', $conversationId)
            ->where('uid', $uid)
            ->find();

        if (!$conversation) {
            curl_close($ch);

            throw new \think\exception\HttpResponseException(
                json(['error' => '会话不存在或无权访问'], 404)
            );
        }
    }

    $state = [
        'buffer'   => '',
        'content'  => '',
        'finished' => false,
        'valid'    => true,
    ];

    $options[CURLOPT_WRITEFUNCTION] = function (
        $handle,
        $chunk
    ) use (&$state, $conversationId) {
        if ($conversationId !== null) {
            $this->chatSecurityCaptureChunk($state, $chunk);
        }

        echo $chunk;

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();

        return strlen($chunk);
    };

    curl_setopt_array($ch, $options);

    $result = curl_exec($ch);
    $error = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if (
        $result === false
        || $error
        || $httpCode < 200
        || $httpCode >= 300
    ) {
        echo 'data: ' . json_encode(
            ['error' => 'AI服务请求失败'],
            JSON_UNESCAPED_UNICODE
        ) . "\n\n";

        return;
    }

    if ($conversationId !== null) {
        $this->chatSecurityCaptureChunk($state, "\n");

        if (
            $state['valid']
            && $state['finished']
            && $state['content'] !== ''
        ) {
            try {
                $this->chatSecurityPersistAssistant(
                    $conversationId,
                    $uid,
                    $state['content']
                );
            } catch (\Exception $e) {
                error_log('Chat assistant history persistence failed');

                echo 'data: ' . json_encode(
                    ['error' => '回复已生成，但历史记录保存失败'],
                    JSON_UNESCAPED_UNICODE
                ) . "\n\n";
            }
        }
    }
}
    
/*
    // 3. 获取某个会话流下的所有消息
    public function getMessages()
    {
        $conversationId = $this->request->param('conversation_id', 0, 'intval');
        $messages = AiChatMessageModel::where('conversation_id', $conversationId)
            ->order('sequence', 'asc')
            ->field('role,content,timestamp')
            ->select();
        return json($messages);
    }

    // 4. 点“新对话”按钮，创建一个新的会话流
    public function newConversation()
    {
        $uid = cmf_get_current_user_id();
        $data = [
            'uid'        => $uid,
            'start_time' => time(),
            // title 先留空，等用户发第一条消息时再写入
            'title'      => ''
        ];
        $conversationId = AiChatConversionModel::insertGetId($data);
        return json(['conversation_id' => $conversationId]);
    }

 // 5. 保存消息（并在首次发送时为会话写入 title）
    public function saveMessage()
    {
        $conversationId = $this->request->param('conversation_id', 0, 'intval');
        $message        = $this->request->param('message', '', 'trim');
        $role        = $this->request->param('role', '', 'trim');        
        
        
        $uid            = cmf_get_current_user_id();

        // 计算本条位于该会话的第几条
        $maxSeq = AiChatMessageModel::where('conversation_id', $conversationId)
            ->max('sequence');
        $sequence = $maxSeq ? $maxSeq + 1 : 1;

        // 保存用户消息
        AiChatMessageModel::create([
            'conversation_id' => $conversationId,
            'role'            => $role,
            'timestamp'       => time(),
            'sequence'        => $sequence,
            'content'         => $message,
        ]);

        // 如果是第一条消息，更新会话的 title（前20字符）
        $conversation = AiChatConversionModel::find($conversationId);
        if (empty($conversation->title)) {
            //$title = mb_substr($message, 0, 20, 'UTF-8');
            $title = $message;
            $conversation->title = $title;
            $conversation->save();
        } else {
            $title = $conversation->title;
        }

        return json([
            'conversation_id' => $conversationId,
            'title'           => $title,
        ]);
    }    
    */
 
    public function message_is_allowed($content){
        $params = array(
            "dataId" => "dataId1",
            "content" => $content
            // "dataType"=>"1",
            // "ip"=>"123.115.77.137",
            // "account"=>"php@163.com",
            // "deviceType"=>"4",
            // "deviceId"=>"92B1E5AA-4C3D-4565-A8C2-86E297055088",
            // "callback"=>"ebfcad1c-dba1-490c-b4de-e784c2691768",
            // "publishTime"=>round(microtime(true)*1000),
            // "callbackUrl"=>"主动回调url地址"
        );
    
        $ret = $this->yidun_check($params);
        if ($ret["code"] == 200) {
            $result = $ret["result"];
            // 内容安全结果
            $antispam = $result["antispam"];
            if ($antispam != null) {
                $taskId = $antispam["taskId"];
                $dataId = $antispam["dataId"];
                $suggestion = $antispam["suggestion"];
                $resultType = $antispam["resultType"];
                $censorType = $antispam["censorType"];
                $isRelatedHit = $antispam["isRelatedHit"];
                $labels = $antispam["labels"];
                if($suggestion==0){
                    return [true, "通过检测"];
                }else{
                    $errorMsg = "";
                    foreach ($labels as $index => $labelElement) {
                        $label = $labelElement["label"];
                        $level = $labelElement["level"];
                        $subLabels = $labelElement["subLabels"];
                        foreach ($subLabels as $index => $subLabelElement) {
/*                            $errorMsg .= "内容安全分类，label: {$label}，subLabel: {$subLabelElement["subLabel"]}, \n details:" . json_encode($subLabelElement["details"]);*/
                              $errorMsg .= "内容安全分类，label: {$label}" 
                                    . (isset($subLabelElement["subLabel"]) ? "，subLabel: {$subLabelElement["subLabel"]}" : "")
                                    . (isset($subLabelElement["details"]) ? "，details: " . json_encode($subLabelElement["details"]) : "");
                        }
                    } 
                    return [false, $errorMsg];
                }

            }
            
        } else {
            return [true, "内容安全检测API返回错误状态码：" . $ret["code"]];
        }
 
        
        
        
        
    }
    
public function jianyuanai()
{
    // 只接受 POST 请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['error' => '仅支持POST请求']));
    }

    // 获取原始 POST 数据
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        die(json_encode(['error' => '无效的JSON数据']));
    }

    if (empty($input['messages'])) {
        http_response_code(400);
        die(json_encode(['error' => 'messages参数不能为空']));
    }

    // 选择系统提示词
    $systemPrompt = $this->defaultSysPrompt;



    $response_format = $input['response_format'] ?? 'json_object';
    $isStream = $input['isStream'] ?? true;
    $model = $input['model'] ?? 'qwen3.8-flash';
    $enablethinking = $input['enable_thinking'] ?? false;

    $messages = $input['messages'];

    // 检查最后一条消息内容
    $messageCheck = $this->message_is_allowed(
        $input['messages'][count($input['messages']) - 1]['content']
    );

    if (!$messageCheck[0]) {
        $errorMessage = '您的问题不符合法律法规，请修改问题再提问'
            . $messageCheck[1];

        http_response_code(400);
        die(json_encode(['error' => $errorMessage]));
    }

    // 添加系统提示词
    $messages = array_merge(
        [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ],
        $messages
    );

    // 构造请求数据
    $requestData = [
        'model' => $model,
        'messages' => $messages,
        'enable_thinking' => $enablethinking,
        'type' => $response_format,
        'stream' => $isStream,
    ];

    // 必须先初始化 cURL，再进入流式处理分支
    $ch = curl_init();

    $options = [
        CURLOPT_URL => $this->apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 300,
    ];

    // 流式输出处理
    if ($isStream) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');

        // 使用已有的安全流式处理方法
        $this->chatSecurityExecuteStream($ch, $options);

        exit;
    } else {
        // 非流式输出处理
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch) ? curl_error($ch) : false;

        curl_close($ch);

        if ($error || $httpCode > 400) {
            http_response_code($httpCode ?: 500);
            header('Content-Type: application/json');

            die(json_encode([
                'error' => $error ?: 'API请求失败',
            ]));
        }

        if ($httpCode == 400) {
            http_response_code(200);
            header('Content-Type: application/json');

            $responseData = json_decode($response, true);

            if (isset($responseData['error'])) {
                $responseData['error']['code'] = '回答错误';
                $responseData['error']['message'] =
                    '您的提问涉及到不符合法律法规的内容，请重新提问。';
                $responseData['error']['type'] = '回答错误';
            }

            unset($responseData['id']);
            unset($responseData['request_id']);

            die(json_encode($responseData));
        }

        // 解码 JSON 响应
        $responseData = json_decode($response, true);

        // 修改 model 和 id
        $responseData['model'] = 'jianyuan-turbo';
        $responseData['id'] = str_replace(
            'chatcmpl',
            'jianyuan',
            $responseData['id']
        );

        // 移除不需要的字段
        unset($responseData['usage']);
        unset($responseData['object']);

        header('Content-Type: application/json');
        echo json_encode($responseData);

        exit;
    }
}
    
    public function jianyuanselfai() {
        // 只接受POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => '仅支持POST请求']));
        }
    
        // 获取原始POST数据
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            die(json_encode(['error' => '无效的JSON数据']));
        }
    
        // 验证必需参数
        if (empty($input['messages'])) {
            http_response_code(400);
            die(json_encode(['error' => 'messages参数不能为空']));
        }
        
        $response_format = $input['response_format'] ?? 'json_object';
        $isStream = $input['isStream'] ?? true;
        /*$model = $input['model'] ?? 'deepseek-v3';*/
        $model = $input['model'] ?? 'qwen3.8-flash';
        // 从POST数据获取参数
        $messages = $input['messages'];
        // 新增：检查消息内容是否合法


         $messageCheck = $this->message_is_allowed($input['messages'][count($input['messages']) - 1]['content']);
        if (!$messageCheck[0]) {  // If not allowed (first element is false)
            $errorMessage =  '您的问题不符合法律法规，请修改问题再提问'.$messageCheck[1];
                http_response_code(400);
                die(json_encode(['error' => $errorMessage]));
        }

              
   
        $messages = array_merge([['role' => 'system', 'content' => $this->systemPrompt]], $messages);
    

    
        // 构造请求数据
        $requestData = [
            'model' => $model,
            'messages' => $messages,
            'type' => $response_format,
            'stream' => $isStream
        ];
        
        if($isStream){
             // 禁用输出缓冲并确保立即刷新
            ob_end_clean(); // 清除现有缓冲区
            ini_set('output_buffering', 'off');
            ini_set('implicit_flush', true);
            ob_implicit_flush(true);           
        }        
        
        
        
    
        // 初始化cURL
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true, // 始终设置为true，我们自己处理输出
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 300 // 单独设置连接超时
        ];
    
        // 流式输出处理
        if ($isStream) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            

            $options[CURLOPT_WRITEFUNCTION] = function($ch, $data) {
                echo $data;
                if (ob_get_level() > 0) ob_flush();
                flush();
                return strlen($data);
            };           
            
            
            
            
            curl_setopt_array($ch, $options);
            curl_exec($ch);
            
            if (curl_errno($ch)) {
                echo "data: [ERROR] " . json_encode(['error' => curl_error($ch)]) . "\n\n";
                flush();
            }
            
            curl_close($ch);
            exit;
        } 
        // 非流式输出处理
        else {
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_errno($ch) ? curl_error($ch) : false;
            curl_close($ch);
            if ($error || $httpCode > 400) {
                http_response_code($httpCode ?: 500);
                header('Content-Type: application/json');
                die(json_encode(['error' => $error ?: 'API请求失败']));
            }

            
            if ($httpCode == 400) {
                http_response_code(200);
                header('Content-Type: application/json');
                // 解码JSON响应
                $responseData = json_decode($response, true);
                
                // 修改错误信息
                if (isset($responseData['error'])) {
                    $responseData['error']['code'] = '回答错误';
                    $responseData['error']['message'] = '您的提问涉及到不符合法律法规的内容，请重新提问。';
                    $responseData['error']['type'] = '回答错误';
                }
                
                // 移除不需要的字段
                unset($responseData['id']);
                unset($responseData['request_id']);
                
                die(json_encode($responseData));
            }      
            

            
            // 解码JSON响应
            $responseData = json_decode($response, true);
            
            // 修改model和id
            $responseData['model'] = 'jianyuan-turbo';
            $responseData['id'] = str_replace('chatcmpl', 'jianyuan', $responseData['id']);
            
            // 移除不需要的字段
            unset($responseData['usage']);
            unset($responseData['object']);
            
            header('Content-Type: application/json');
            echo json_encode($responseData);            
            
            exit;
        }
    }    
    
    
    
    
    
    
    
    
    /**
     * 分析接口（流式输出，POST请求）
     * @return void
     */
    public function analysis()
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
              请对此图像中的文物/艺术品进行专业分析：
                    
                    1. 基本信息：
                       - 鉴定材质（如陶、瓷、青铜、玉石、木质等）
                       - 判断年代与可能的产地/流派
                       - 评估保存状态
                    
                    2. 造型与结构：
                       - 详述器型特征、比例与形态
                       - 分析构造方式与结构特点
                       - 指出与同类器物的异同点
                    
                    3. 工艺技术：
                       - 识别制作工艺（如捏塑、拉坯、模制、锻造等）
                       - 分析装饰技法（如印花、刻划、彩绘、镶嵌等）
                       - 评价工艺水平与制作难度
                    
                    4. 纹饰与图像：
                       - 辨识主要纹样类型（如几何纹、动物纹、植物纹等）
                       - 解读纹饰组合与布局特点
                       - 阐释纹饰的象征意义与文化内涵
                    
                    5. 文化与美学价值：
                       - 探讨该器物在历史背景中的功能与地位
                       - 分析其艺术风格特征与美学表现
                       - 评价其在文化遗产中的重要性
                       
                    6. 挖掘本体词汇并标注：
                       - 在你的回答中，涉及任何与文物、文博、艺术、工艺美术、技艺、工艺或文化本体相关的词汇时，必须使用<a>标签进行标注。尽量发掘与文物、文博、艺术、工艺美术、技艺、工艺、文化本体等概念相关的多样化词汇。尤其对于器型、纹样、纹饰、工艺、知名人物、知名作品这些本体，千万不要遗漏标注。一定要并通过<a>标签形式进行标注。
                       - 标注必须使用<a>标签进行标注，a标签的内容应为对应的文本，且标签内包含jumptokey(key)的函数，传入a标签文本作为参数。a标签应禁用跳转功能，以避免页面跳转。例如：<a href=\"javascript:void(0)\" onclick=\"jumptokey('青铜器')\">青铜器</a>
                       - 重点本体词汇：器型、纹样、纹饰、材质、工艺、知名人物、知名作品这些为重要本体，一定要识别并标注。
                       - 在当前回答已经识别并标注的本体，不要重复识别并标注。
                       
                    
                    请尽可能详细、准确地提供分析，如有不确定之处，请标明。
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
            

            
            // 调用API并流式输出
            /*$this->streamDashscopeApi($apiKey, 'qwen-vl-max', $messages);*/
            $this->streamDashscopeApi($this->apiKey, 'qvq-max', $messages);
            
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
    public function jianzhuAnalysis(){
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
    

        $prompt = "
                请从以下维度对图像从建筑设计角度进行全面评价：
                1. 整体美学：建筑的外观是否具有视觉吸引力？造型、比例、立面语言是否和谐？
                2. 功能性：空间布局是否合理？是否满足使用需求并体现人性化设计？
                3. 创新性：设计中是否体现出独特创意或突破性的理念？
                4. 环境融合度：建筑与周边环境、景观、城市肌理是否协调？是否尊重文化与历史语境？
                5. 可持续性：在材料选择、能效、生态友好性方面是否有考虑？
                6. 用户体验：动线设计、采光、通风、舒适度和可达性是否优秀？
                
                请结合以上维度，给出定性和定量的评价，并指出优点、缺点及改进建议。
                如果图像不是建筑设计，则提示用户只能对建筑设计图像进行分析与评价
        ";
        $this->analysisImage($imageUrl,$prompt);
            
       
    }
    public function analysisImage($imageUrl,$prompt)
    {

    
        try {
            
            
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
  
            // 调用API并流式输出
            /*$this->streamDashscopeApi($apiKey, 'qwen-vl-max', $messages);*/
            $this->streamDashscopeApi($this->apiKey, 'qvq-max', $messages);
            
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
   /* private function streamDashscopeApi($apiKey, $model, $messages, $options = [])
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
    */
private function streamDashscopeApi(
    $apiKey,
    $model,
    $messages,
    $options = []
) {
    $url = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';

    $data = array_merge([
        'model'           => $model,
        'enable_thinking' => true,
        'messages'        => $messages,
        'stream'          => true,
    ], $options);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];

    ob_end_clean();
    ini_set('output_buffering', 'off');
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $ch = curl_init();

    $this->chatSecurityExecuteStream($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_CONNECTTIMEOUT => 300,
    ]);
}    
    
    //详细签名方法见：https://github.com/yidun/antispam-php-demo/blob/master/util.php
    
    /**
     * 生成签名信息
     * $secretKey 产品私钥
     * $params 接口请求参数，不包括signature参数
     */
    function gen_yidun_signature($params){
        
    	ksort($params);
    	$buff="";
    	foreach($params as $key=>$value){
    		$buff .=$key;
    		$buff .=$value;
    	}
    	$buff .= $this->yidunKey;
    	return md5(mb_convert_encoding($buff, "utf8", "auto"));
    }
    
    function toUtf8($string) {
        if (!mb_check_encoding($string, 'UTF-8')) {
            return mb_convert_encoding($string, 'UTF-8', 'GBK');
        }
        return $string;
    }
    
        
    /**
     * CURL POST请求函数（带超时参数）
     * @param string $url 请求地址
     * @param array|string $data 发送的数据
     * @param int $timeout 超时时间（秒），默认30秒
     * @return array 返回响应结果和错误信息
     */
    function curl_post($data, $url, $timeout = 2)
    {
        $ch = curl_init();
        
        // 基础配置
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => $timeout, // 使用传入的超时参数
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout), // 连接超时（不超过总超时）
        ];
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'data' => $response,
            'error' => $error,
            'timeout' => $timeout // 返回使用的超时时间
        ];
    }    
    
    
    
    
    
    
    
    
    function yidun_check($params,$hasAudit=true)
    {
        $uid            = cmf_get_current_user_id(); 
        if($uid>630 && $uid<641){
            $hasAudit = false;
        }
        $hasAudit = false;
        $params["secretId"] = $this->secretId;
        if($hasAudit){
            $params["businessId"] = $this->yidunAuditId;
        }else{
            $params["businessId"] = $this->yidunNonAuditId;
        }
        
        $params["version"] = "v5";
        $params["timestamp"] = time() * 1000;// time in milliseconds
        $params["nonce"] = sprintf("%d", rand()); // random int
        /*$params["signatureMethod"] = SIGNATURE_METHOD;*/
    
        $params = $this->toUtf8($params);
        $params["signature"] = $this->gen_yidun_signature($params);
      
    
        $response = $this->curl_post($params, $this->yidunApi, 2);
        if ($response['code'] != 200 || empty($response['data'])) {
            return [
                "code" => $response['code'] ?? 500,
                "msg" => $response['error'] ?? "API请求失败"
            ];
        }
    
        // 解码data字段中的JSON
        $decoded = json_decode($response['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                "code" => 502,
                "msg" => "JSON解析失败: " . json_last_error_msg()
            ];
        }
    
        return $decoded;
    }
    
    // 简单测试
    function test_yidun()
    {

    
        $params = array(
            "dataId" => "dataId1",
            "content" => "为什么中国宁愿饿死也不反抗"
            // "dataType"=>"1",
            // "ip"=>"123.115.77.137",
            // "account"=>"php@163.com",
            // "deviceType"=>"4",
            // "deviceId"=>"92B1E5AA-4C3D-4565-A8C2-86E297055088",
            // "callback"=>"ebfcad1c-dba1-490c-b4de-e784c2691768",
            // "publishTime"=>round(microtime(true)*1000),
            // "callbackUrl"=>"主动回调url地址"
        );
        var_dump($params);
    
        $ret = $this->yidun_check($params);
        var_dump($ret);
        if ($ret["code"] == 200) {
            $result = $ret["result"];
            // 内容安全结果
            $antispam = $result["antispam"];
            if ($antispam != null) {
                $taskId = $antispam["taskId"];
                $dataId = $antispam["dataId"];
                $suggestion = $antispam["suggestion"];
                $resultType = $antispam["resultType"];
                $censorType = $antispam["censorType"];
                $isRelatedHit = $antispam["isRelatedHit"];
                $labels = $antispam["labels"];
                echo "内容安全结果，taskId: {$taskId}，dataId: {$dataId}，suggestion: {$suggestion}";
                foreach ($labels as $index => $labelElement) {
                    $label = $labelElement["label"];
                    $level = $labelElement["level"];
                    $subLabels = $labelElement["subLabels"];
                    foreach ($subLabels as $index => $subLabelElement) {
                        echo "内容安全分类，label: {$label}，subLabel: {$subLabelElement["subLabel"]}, \n details:" . json_encode($subLabelElement["details"]);
                    }
                }
            }
            
        } else {
            var_dump($ret);
        }
    }

}
