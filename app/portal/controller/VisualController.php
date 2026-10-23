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
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalPostTagModel;
use app\portal\model\PortalTagModel;
use app\portal\service\ApiService;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Exception;
use think\facade\Request;  // Add this import statement
use think\facade\Json;    // For JSON responses


class VisualController extends HomeBaseController
{
    private function envValue($name, $default = '')
    {
        return cmf_service_config($name, $default);
    }

    public function index()
    {

        return $this->fetch('/visual');


    }
    public function visualwithhead()
    {
        $portalCategoryModel = new PortalCategoryModel();
        $categoryArray = $portalCategoryModel->where('delete_time',0)->select()->toArray();
        $this->assign("categoryArray",$categoryArray);

        return $this->fetch('/visualwithhead');


    }

    
    public function query()
    {
        $params                       = $this->request->post();
        $encodedQuery                 = $params['queryinfo'];              
        $params                 = $params['paraminfo'];              

        $query = preg_replace([
            '/\bFIND\b/',   // \b 确保匹配整个单词
            '/\bFILTER\b/',
            '/\bGET\b/'
        ], [
            'MATCH',
            'WHERE',
            'RETURN'
        ], $encodedQuery);
        $query = html_entity_decode($query);
        try {
            $neo4jUri = $this->envValue('NEO4J_URI');
            $neo4jHttpUri = $this->envValue('NEO4J_HTTP_URI');
            $neo4jUsername = $this->envValue('NEO4J_USERNAME');
            $neo4jPassword = $this->envValue('NEO4J_PASSWORD');
            $client = \Laudis\Neo4j\ClientBuilder::create()
                ->withDriver('bolt', $neo4jUri, \Laudis\Neo4j\Authentication\Authenticate::basic($neo4jUsername, $neo4jPassword))
                ->withDriver('https', $neo4jHttpUri, \Laudis\Neo4j\Authentication\Authenticate::basic($neo4jUsername, $neo4jPassword))
                ->withDefaultDriver('https')
                ->build();
            
            $params = json_decode(urldecode($params), true);
            // Execute query with parameters
            $result = $client->run($query, $params);
            
            // Format results for JSON response
            $formatted = [];
            foreach ($result as $record) {
                $formatted[] = $record->toArray();
            }
            
            return json([
                'success' => true,
                'data' => $formatted,
                'time' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
    
     /**
     * 分析接口
     * @return \think\Response
     */
    public function analysis()
    {
        $params                       = $this->request->get();
        $imageUrl                 = $params['image'];        
        
        try {
            $apiKey = $this->envValue('DASHSCOPE_API_KEY');
            $prompt = '根据图片，从纹样、材质、技法、技艺、工艺、器型、功能维度推测并返回JSON格式结果.
                        要求：
                        1. 只返回精准名词，不要描述性语言
                        2. 必须包含所有七个维度，没有结果的留空
                        3. 返回标准JSON格式，不要Markdown标记
                        4. 如果可信度不高则不给出相应结果,给出的结果应该是尽可能精确的概念，如器型应该是梅瓶，双耳瓶等
                        5. 返回结果统一用精准的名词，不要有句子或者描述。比如失蜡法铸造应该是失蜡法这个名词概念，而没有铸造这个动词';
            
            
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
            
            $response = $this->callDashscopeApi($apiKey, 'qwen-vl-plus', $messages);
            
            // 处理返回的Markdown格式JSON
            $jsonData = $this->extractJsonFromMarkdown($response);
            
            // ThinkPHP 返回JSON响应
            return json([
                'status' => 'success',
                'code' => 200,
                'data' => $jsonData,
                'message' => '请求成功'
            ]);
            
        } catch (Exception $e) {
            // ThinkPHP 返回错误JSON响应
            return json([
                'status' => 'error',
                'code' => $e->getCode() ?: 500,
                'data' => null,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
    
    public function getTags()
    {
        $params                       = $this->request->get();
        $articleId                 = $params['id'];  
        $portalTagModel = new PortalPostTagModel();
        
        try {
                $tagArray = array();
                $tags = $portalTagModel->distinct(true)->field('tag')->where('id', $articleId)->where('type', 1)->where('score', 'gt', 50)->order('score DESC')->select();
                
                foreach ($tags as $tag){
                    if(strlen($tag['tag'])>=6){//每个汉字占3个字符
                        array_push($tagArray,$tag['tag']);
                    }
                }
                //文章标题提炼出来的标签，得分在50以上
                $tags = $portalTagModel->distinct(true)->field('tag')->where('id', $articleId)->where('type', 2)->where('score', 'gt', 50)->order('score DESC')->select();
                foreach ($tags as $tag){
                    if(strlen($tag['tag'])>=6){//每个汉字占3个字符
                        array_push($tagArray,$tag['tag']);
                    }
                }  
                // 去重处理（保留首次出现的值）
                $tagArray = array_unique($tagArray);
                
                // 重置数组索引（可选）
                $tagArray = array_values($tagArray);
                // ThinkPHP 返回JSON响应
                return json([
                    'status' => 'success',
                    'code' => 200,
                    'data' => $tagArray,
                    'message' => '请求成功'
                ]);
            
        } catch (Exception $e) {
            // ThinkPHP 返回错误JSON响应
            return json([
                'status' => 'error',
                'code' => $e->getCode() ?: 500,
                'data' => null,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
    
    /**
     * 从Markdown格式字符串中提取JSON并清理空值
     * @param string $markdown
     * @return array
     * @throws Exception
     */
    private function extractJsonFromMarkdown($markdown)
    {
        // 去除Markdown代码块标记
        $jsonStr = preg_replace('/```json|```/', '', $markdown);
        
        // 解码JSON
        $data = json_decode(trim($jsonStr), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("解析JSON失败: " . json_last_error_msg(), 500);
        }
        
        // 确保返回的是数组格式
        if (!is_array($data)) {
            throw new Exception("解析后的数据不是有效的JSON对象", 500);
        }
        
        // 移除空字符串的字段
        $cleanedData = [];
        foreach ($data as $key => $value) {
            if ($value !== "") {  // 只有当值不是空字符串时才保留
                $cleanedData[$key] = $value;
            }
        }
        
        return $cleanedData;
    }
    /**
     * 检查图片URL是否可访问
     * @param string $url
     * @return bool
     */
    private function checkImageUrl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode == 200;
    }
    
    /**
     * 调用DashScope API
     * @param string $apiKey
     * @param string $model
     * @param array $messages
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    private function callDashscopeApi($apiKey, $model, $messages, $options = [])
    {
        $url = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
        
        $data = array_merge([
            'model' => $model,
            'messages' => $messages
        ], $options);
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("API请求失败: " . $error, 500);
        }
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // 处理API返回的错误
        if (isset($result['error'])) {
            throw new Exception($result['error']['message'], $httpCode);
        }
        
        if ($httpCode >= 400 || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("API响应异常: " . $response, $httpCode);
        }
        
        // 安全地获取结果内容
        return $result['choices'][0]['message']['content'] ?? null;
    }
    
    function encryptData($data, $key) {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted); // IV + ciphertext
    }
    
    public function getinfo(){
        $secretKey = $this->envValue('NEO4J_ENCRYPTION_KEY');
        $neouser = $this->envValue('NEO4J_INFO_USERNAME', $this->envValue('NEO4J_USERNAME'));
        $neopassword = $this->envValue('NEO4J_INFO_PASSWORD', $this->envValue('NEO4J_PASSWORD'));
        $neopasswordEncryed =  $this->encryptData($neopassword, $secretKey);
        $neouserEncryed =  $this->encryptData($neouser, $secretKey);
        $response = [
            'ps' => $neopasswordEncryed,
            'us' => $neouserEncryed,
            'ks' => $secretKey
        ];     
        return json([
            'status' => 'success',
            'data' => $response
        ]);        
        
        
        
    }


}
