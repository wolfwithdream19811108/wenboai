<?php
/**
 *
 * +------------------------------------------------------------+
 * @category Elastic
 * +------------------------------------------------------------+
 * Elastic类
 * +------------------------------------------------------------+
 *
 * @version 1.0
 *
 * Created at : 2021-4-15 15:36:43
 *
 * 使用步骤：
 * 一、composer下载elasticsearch-php扩展 https://github.com/elastic/elasticsearch-php/
 * 二、use App\Handlers\ElasticHandler;加载该类
 * 三、设置对应hosts,调用对应方法
 *
 */
 
namespace ElasticHandler;
use Elasticsearch\ClientBuilder;
use Faker\Generator as Faker; //伪造数据
 
class ElasticHandler
{
    /**
     * 配置
     * @var array
     */
 private $hosts = [
/*    '127.0.0.1:9200',
    '127.0.0.1',
    'localhost:9200',
    'localhost',
    'http://localhost',
    'https://127.0.0.1:9200'*/
    'http://es-hmys-k03.public.cn-hangzhou.es-serverless.aliyuncs.com:9200'
    
];
 
    /**
     * es连接对象
     * @access private
     * @var object
     */
    private $EsClient = null;
 
    /**
     * 伪造类
     */
    private $faker = null;
 
    /**
     * 索引 类似库
     * @access private
     * @var string
     */
    private $index = 'ind_ysyk_cmf_portal_post';
 
    /**
     * 类型 类似表  *es9后面会被弃用 弃用原因：同一索引下，不同type的数据存储其他type的field大量空值，造成资源浪费
     * @access private
     * @var string
     */
    private $type = '_doc';
 
    /**
     * 从哪一条开始
     * @access private
     * @var int
     */
    private $from = 0;
 
    /**
     * 条数
     * @access private
     * @var int //默认10 最高10000
     */
    private $size = 1000;
 
    /**
     * 排序
     * @access private
     * @var array
     */
    private $sort = [];
 
    /**
     * 字段
     * @access private
     * @var string
     */
    private $field;
 
    /**
     * 条件
     * @access private
     * @var string
     */
    private $query;
 
    /**
     * 配置重试次数
     * @access private
     * @var string
     */
    private $retries = 1;
 
    /**
     * 数据库错误信息
     * @access private
     * @var string
     */
    private $error = '';
 
    /**
     * 错误的输出方式 1直接返回 2报错异常
     * @access private
     * @var int
     */
    private $errorType = 1;
 
    /**
     * 忽略错误
     * @access private
     * @var array
     */
    private $ignore = [400, 404, 503];
 
    /**
     * 超时时间
     * @access private
     * @var int
     */
    private $timeout = 10;
 
    /**
     * 连接超时时间
     * @access private
     * @var int
     */
    private $connect_timeout = 10;
 
 
    public function __construct($index = 'database')
    {
        /**
         * 实例化 ES 客户端
         */
        $this->EsClient = ClientBuilder::create() // 实例化 ClientBuilder
                            ->setHosts($this->hosts) // 设置主机信息
                            ->setRetries($this->retries) // 配置重试次数
                            ->setBasicAuthentication('es-hmys-k03', '168609Cay') // 添加认证
                            ->build(); // 构建客户端对象
 
        // 默认索引
        /*$this->index = config('other.elastic_environment') . $this->index;*/ //辨别对应库，以及正式测试环境
        $this->index = $index; 

 
    }
 
    /**
     * 限制条数
     * @access public
     * @param  int $rows 记录行
     * @param  int $offset 偏移量
     * @return object
     */
    public function limit(int $rows, int $offset = 0)
    {
        $this->size = $rows;
 
        if ($offset == 0) {
            $this->from = 0;
        } else {
            $this->from = $rows * $offset;
        }
 
        return $this;
    }
 
    /**
     * 排序
     * @access public
     * @param  string $field 字段
     * @param  string $sort 排序
     * @return object
     */
    public function order($field, $sort = 'ASC')
    {
        $this->sort = [
            'sort' => [ // 排序
                [
                    $field => [
                        'order' => $sort, //对字段进行降序排序
                    ],
                ]
            ]
        ];
 
        return $this;
    }
 
    /**
     * 直接打印错误 无效？
     * @access public
     * @return string
     */
    public function fetchError($errorType = 1)
    {
        $this->errorType = $errorType;
        return $this;
    }
 
    /**
     * 获取最后一次执行错误
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
 
    /**
     * 设置错误
     * @access private
     * @param $msg string 错误信息
     * @param $type string 错误信息类别
     * @return void
     */
    private function setError($msg = '', $type = 1)
    {
        if ($type == 2) {
            $msg = 'error';
        }
 
        if ($this->errorType == 1) {
            $this->error = $msg;
        } else {
            // throw new \Exception($msg);
            echo $msg; exit();
        }
    }
 
    /**
     * 字段
     * @access public
     * @param  string $field 请求指定的字段,只显示对应的字段
     * @return object
     */
    public function field($field)
    {
        if (empty($field) || $field == '*') {
            $this->field = [];
        } else {
            $field = explode(',', $field);
            $this->field = $field;
        }
 
 
        return $this;
    }
 
    /**
     * 条件
     * @access public
     * @param  array $condition 条件
     * @return object
     */
    public function where($condition = [])
    {
        $this->query =  [
            'query' => [
                'bool' => $condition
            ]
        ];
        
        $params = [
            'index'   => $this->index,
            'type'    => $this->type,
          
            'body'    => array_merge([
                'from' => $this->from,
                'size' => $this->size,
            ], $this->query)
        ];        
        
        
        
        

         try {
            return $this->EsClient->search($params);
        } catch (\Exception $e) {
            dump($e);
            $this->setError($e->getMessage());
 
            return false;
        }       
        
        
        
        
        return $this;
    }
 
    /**
     * 配置重试次数
     * @access public
     * @param  int $retries
     * @return object
     */
    public function retries($retries = [])
    {
        $this->retries = $retries;
        return $this;
    }
 
    /**
     * 连接信息
     * @access public
     * @param  array $hosts
     * @return object
     */
    public function hosts($hosts = [])
    {
        $this->hosts = $hosts;
        return $this;
    }
 
    /**
     * 设置忽略错误
     * @access public
     * @param $ignore array 错误状态
     * @return void
     */
    public function setIgnore($ignore = [])
    {
        $this->ignore = $ignore;
        return $this;
    }
 
    /**
     * 设置超时时间
     * @access public
     * @param $timeout int
     * @return void
     */
    public function setTimeout($timeout = 10)
    {
        $this->timeout = $timeout;
        return $this;
    }
 
    /**
     * 设置连接超时时间
     * @access public
     * @param $connect_timeout int
     * @return void
     */
    public function setConnectTimeout($connect_timeout = 10)
    {
        $this->connect_timeout = $connect_timeout;
        return $this;
    }
 
    /**
     * 索引
     * @access public
     * @param  array $index 索引
     * @return object
     */
    public function index($index)
    {
        $this->index = config('other.elastic_environment') . '_' . $index;
        return $this;
    }
 
    /**
     * 查询多行记录
     * @access public
     * @return array
     */
    public function select()
    {
        $result = $this->search();
       // dump($this->getError());
        // dd($result, $this->getError());
 
        $list = $result['hits']['hits'] ?? [];
        $source_list = [];
        foreach ($list as $key => $vo) {
            $source_list[] = $vo['_source'];
        }
 
        return $source_list;
    }
 
    /**
     * 查询行数 超过10000条会失败需要配置其他参数
     * @access public
     * @return int
     */
    public function count()
    {
        $this->query = array_merge($this->query, [
            'size' => 0,
            'track_total_hits' => true, //实际数量，不设置最大为10000
        ]);
        $result = $this->search();
        // dd($result);
 
        $count = $result['hits']['total']['value'] ?? 0;
 
        return $count;
    }
 
    /**
     * 添加一个文档到 Index 的Type中
     * @param array $body
     * @return void
     *          -(Y)_index:索引
     *          -(Y)_id:id值
     *          -(Y)_version:版本
     *          -(Y)result:结果
     *          -(Y)_shards:分片
     *              -(Y)total:总计
     *              -(Y)successful:成功
     *              -(Y)failed:失败
     *          -(Y)_seq_no:序列号（第几条数据）
     *          -(Y)_primary_term:初级术语？？
     */
    public function insert($body = [])
    {
        $params = [
            'index' => $this->index, //索引
            'type'  => $this->type, //表
            // 'id' => 1, #可以手动指定id，也可以不指定随机生成
            'body'  => $body,
            'client' => [
                'ignore'          => $this->ignore,
                'timeout'         => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
            ]
        ];
 
        return $this->EsClient->index($params);
    }
 
    /**
     * 批量添加文档到 Index 的Type中
     * @param array $body
     * @return void
     */
    public function insertAll($body = [])
    {
        $params = [];
        foreach ($body as $key => $vo) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->index, //索引
                    '_type'  => $this->type, //表
                ]
            ];
 
            $params['body'][] = $vo;
        }
 
        $params['client'] = [
            'ignore'          => $this->ignore,
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->connect_timeout,
        ];
        // dd($params['body']);
 
        return $this->EsClient->bulk($params);
    }
 
    /**
     * 直接检索该字段
     * @param $id id值
     * @return array 该条记录的数据
     */
    public function find($id)
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
            '_source' => $this->field,
            'client' => [
                'ignore'          => $this->ignore,
                // 'verbose' => true, //增加响应的冗长
                'timeout'         => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
                // 'future' => 'lazy', //启用 Future 模式  lazy、true
                // 'verify' => 'path/to/cacert.pem'      // SSL加密,使用自签名证书
            ]
        ];
 
        $result = $this->EsClient->getSource($params);
        if (isset($result['error'])) {
            if (isset($result['error']['reason'])) {
                $this->setError($result['error']['reason']);
            } else {
                $this->setError($result);
            }
            $result = [];
        }
 
        return $result;
    }
 
    /**
     * 删除一个文档
     * @param $id
     * @return bool
     * @return array
     *          -(Y)_index:索引
     *          -(Y)_type:？？
     *          -(Y)_id:id值
     *          -(Y)_version:版本
     *          -(Y)result:结果
     *          -(Y)_shards:分片
     *              -(Y)total:总计
     *              -(Y)successful:成功
     *              -(Y)failed:失败
     *          -(Y)_seq_no:序列号（第几条数据）
     *          -(Y)_primary_term:初级术语？？
     */
    public function delete($id)
    {
        $params = [
            'index' => $this->index,
            // 'type'  => $this->type,
            'id'    => $id,
            'client' => [
                'ignore'          => $this->ignore,
                'timeout'         => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
            ]
        ];
 
        $result =  $this->EsClient->delete($params);
        // dd($result);
        if (isset($result) && $result['result'] != 'not_found') {
            return true;
        } else {
            $this->setError('not_found');
 
            return false;
        }
    }
 
 
    /**
     * 获取单个文档
     * @param $id id值
     * @return array
     *          -(Y)_index:索引名
     *          -(Y)_type:_doc 文档类型？？
     *          -(Y)_id:id值
     *          -(Y)_version:版本
     *          -(Y)_seq_no:序列号（第几条数据）
     *          -(Y)_primary_term:初级术语？？
     *          -(Y)found:是否找到
     *          -(Y)_source:该条记录的数据
     *              -(Y)
     */
    public function getDoc($id)
    {
        
        $params = [
            'index'  => $this->index,
            'type'   => $this->type,
            'id'     => $id,
            'client' => [
                'ignore'          => $this->ignore,
                'timeout'         => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
            ]
        ];
 
         try {
            return $this->EsClient->get($params);
        } catch (\Exception $e) {
            dump($e);
            $this->setError($e->getMessage());
 
            return false;
        }  
        
        
    }
    
    
    
    
 
    /**
     * 搜索文档，query是查询条件
     * @param array $query
     * @return array
     *          -(Y)took:??
     *          -(Y)timed_out:是否超时
     *          -(Y)_shards:分片
     *              -(Y)total:总计
     *              -(Y)successful:成功
     *              -(Y)skipped:？？？
     *              -(Y)failed:失败
     *          -(Y)hits:命中情况
     *              -(Y)total:总计
     *                  -(Y)value:命中数
     *                  -(Y)relation:关联
     *              -(Y)max_score:最大匹配值
     *                  -(Y)hits:命中情况
     *                      -(Y)_index:索引
     *                      -(Y)_type:初级术语？？
     *                      -(Y)_id:id
     *                      -(Y)_score:分值
     *                      -(Y)_source:该条记录的数据
     *                      -(Y)sort:排序字段的情况
     */
    public function search($keyword)
    {
         $query = [
/*            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'post_content' => '中国'
                        ],
                        'match' => [
                            'post_title' => '向上'
                        ]
                    ]
                ]
 
            ],*/
 
            /*'query' => [
                'match_phrase' => [
                    'fang_name' => [
                        'query' => '底色锃亮',
                        //'slop' => 1
                    ]
                ]
            ],*/
 
            /* 'query' => [
                 'match' => [
                     'fang_name' => [
                         'query' => '中国网络文明大会'
                     ]
                 ]
             ],*/
 
      /*      'query' => [
                'multi_match' => [
                    'query' => '北京',
                    'fields' => [
                        'post_content',
                        'post_title'
                    ]
                ]
            ],*/
 
      'query' => [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                        'post_title',
                        'post_author',
                        'post_museum'
                    ]
                ]
/*            'highlight' => [
                'fields' => [
                    "post_content" => [	"pre_tags"=>"<b style='color:red'>",
					"post_tags"=>"</b>"]
                ]*/
            ],
        "_source"=> [
               'includes' => ["id",
            "post_title",
            "post_author",
            "post_age",
            'post_museum',
            'firstpic_width',
            'firstpic_height',
            "more"
                    ]       

        
            ]            
        ];


 
        $params = [
            'index'   => $this->index,
            'type'    => $this->type,
          
            'body'    => array_merge([
                'from' => $this->from,
                'size' => $this->size,
            ], $query)
        ];

/*$params = [
            'body' => [
                'text' => '中国网络文明大会',
                'analyzer'=>'ik_smart' //ik_max_word 精细  ik_smart 粗略
            ]
        ];
        $res =  $this->EsClient->indices()->analyze($params);*/
 



 
        try {
            return $this->EsClient->search($params);
        } catch (\Exception $e) {
            //dump($e);
           // $this->setError($e->getMessage());
 
            return false;
        }
    }
public function search_es($keyword, $from, $size)
{
    try {
        $query = [
            'query' => [
                'bool' => [
                    'should' => [
                        ['match' => ['post_title' => $keyword]],
                        ['wildcard' => ['post_author' => '*'.$keyword.'*']],
                        ['wildcard' => ['post_museum' => '*'.$keyword.'*']],
                        ['match' => ['post_content.text' => $keyword]]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'from' => $from,
            'size' => $size
        ];
        
        $params = [
            'index' => 'ind_ysyk_cmf_portal_post',  // 确保索引名正确
            'body' => $query
        ];
        
        $result = $this->EsClient->search($params);
 
        
        return $result;
        
    } catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e) {
        // 处理没有可用节点的情况
        return ['error' => 'No Elasticsearch nodes available.', 'details' => $e->getMessage()];
        
    } catch (\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
        // 处理错误的请求（如查询语法错误）
        return ['error' => 'Bad request to Elasticsearch.', 'details' => $e->getMessage()];
        
    } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
        // 处理索引不存在的情况
        return ['error' => 'Elasticsearch index not found.', 'details' => $e->getMessage()];
        
    } catch (\Exception $e) {
        // 处理其他异常
        return ['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()];
    }
}
    // 辅助方法：记录错误日志

     public function search_with_conditions($query)
    {


    
/*    $term0['term']['bigimg']="true";
    $term1['term']['post_age']="清";
    $query['query']['bool']['must'][0]=$term0;
    $query['query']['bool']['must'][1]=$term1;
    
    $should0['match']['post_title']="陈洪绶";
    $should1['match']['post_author']="陈洪绶";
    $query['query']['bool']['should'][0]=$should0;
    $query['query']['bool']['should'][1]=$should1; */   
    
 
        $params = [
            'index'   => $this->index,
            'type'    => $this->type,
          
            'body'    => array_merge([
                'from' => $this->from,
                'size' => $this->size,
            ], $query)
        ];

        try {
            return $this->EsClient->search($params);
        } catch (\Exception $e) {
            dump($e);
            $this->setError($e->getMessage());
 
            return false;
        }
    }  
 
    /**
     * 删除一个Index
     * @return void
     *          -(Y)acknowledged:结果
     */
    public function delIndex()
    {
        $params = [
            'index' => $this->index
        ];
        if ($this->checkIndexExists()) {
            return $this->EsClient->indices()->delete($params);
        }
    }
 
    /**
     * 检查Index 是否存在
     * @return bool
     */
    public function checkIndexExists()
    {
        $params = [
            'index' => $this->index
        ];
        return $this->EsClient->indices()->exists($params);
    }
 
    /**
     * 获取 ES 的状态信息，包括index 列表
     * @return array
     */
    public function esStatus()
    {
        return $this->EsClient->indices()->stats();
    }
 
    /**
     * 批量生成文档
     * @param $num
     */
    public function generateDoc($num = 100)
    {
        $params = [];
        foreach (range(1, $num) as $item) {
            // $this->insert([
            //     'first_name' => $this->faker->name,
            //     'last_name'  => $this->faker->name,
            //     'age'        => $this->faker->numberBetween(20, 80),
            //     '@timestamp' => date('Y-m-d H:i:s')
            // ]);
            $params[] = [
                'first_name' => $this->faker->name,
                'last_name'  => $this->faker->name,
                'age'        => $this->faker->numberBetween(20, 80),
                '@timestamp' => date('Y-m-d H:i:s')
            ];
        }
 
        $this->insertAll($params);
    }
 
    /**
     * 获取Index的文档模板信息
     * @return array
     */
    public function getMapping()
    {
        $params = [
            'index' => $this->index
        ];
        return $this->EsClient->indices()->getMapping($params);
    }
    
    /**
     * 修改Index的文档模板信息
     * @return array
     */
 public function putMappings(){

    $params = [           
        'index' => $this->index,
        'type' => $this->type,
        'body' => [
            'my_type' => [
                'properties' => [
                    'idcard' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ]
    ]; 
 
    $res = $this->EsClient->indices()->putMapping($params);      

}
    /**
     * 删除所有的 Index 谨慎操作！！！
     */
/*    public function delAllIndex()
    {
        echo '谨慎操作!!!!';exit();
 
        $indexList = $this->esStatus()['indices'];
        foreach ($indexList as $item => $index) {
            $this->delIndex();
        }
    }*/
 
    /**
     * 一次获取多个文档
     * @param $ids
     * @return array
     */
    public function getDocs($ids)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => ['ids' => $ids]
        ];
        return $this->EsClient->mget($params);
    }
 
    /**
     * 更新一个文档
     * @param $id
     * @return array
     */
    public function update($id, $doc)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'id'    => $id,
            'body'  => [
                'doc' => $doc
            ],
            'client' => [
                'ignore'          => $this->ignore,
                'timeout'         => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
            ]
        ];
        $result = $this->EsClient->update($params);
 
        if (isset($result['error'])) {
            if (isset($result['error']['reason'])) {
                $this->setError($result['error']['reason']);
            } else {
                $this->setError($result);
            }
            $result = [];
        }
 
        return $result;
    }
 
    /**
     * 创建索引文档模板
     * @return void
     */
    public function createMapping($properties = [])
    {
        $this->createIndex();
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'include_type_name' => true,
            'body'  => [
                $this->type => [
                    '_source'    => [
                        'enabled' => true
                    ],
                    'properties' => $properties,
                    // 'properties' => [
                    //     'ip'         => [
                    //         'type' => 'text'
                    //         //'index' => 'analyzed', // 全文搜索
                    //     ],
                    //     'input'      => [
                    //         'type' => 'text'
                    //     ],
                    //     'type'       => [
                    //         'type' => 'integer',
                    //     ],
                    //     'host_name'  => [
                    //         'type' => 'keyword',
                    //     ],
                    //     'input_type' => [
                    //         'type' => 'integer',
                    //     ],
                    //     'result'     => [
                    //         'type' => 'text',
                    //     ],
                    //     'key'        => [
                    //         'type' => 'text',
                    //     ],
                    //     'created_at' => [
                    //         'type'   => 'date', //需要先设置字段以及格式，才可以做排序
                    //         'format' => 'yyyy-MM-dd HH:mm:ss',
                    //     ],
                    //     '@timestamp' => [
                    //         'type'   => 'date',
                    //         'format' => 'yyyy-MM-dd HH:mm:ss',
                    //     ],
                    // ],
                ]
            ]
        ];
        $result = $this->EsClient->indices()->putMapping($params);
 
        return $result;
    }
 
    /**
     * 创建一个索引 Index （非关系型数据库里面那个索引，而是关系型数据里面的数据库的意思）
     * @return void
     *          -(Y)acknowledged:结果
     *          -(Y)shards_acknowledged:分片结果
     *          -(Y)index:索引
     */
    public function createIndex()
    {
        //先删除
        // $this->delIndex();
 
        $params = [
            'index' => $this->index,
            'body'  => [
                'settings' => [ //包含有关索引（分片数量等）以及分析器的配置
                    'number_of_shards'   => 5, //设置主分片
                    'number_of_replicas' => 3 //副分片
                ],
            ]
        ];
 
        return $this->EsClient->indices()->create($params);
    }
}
 