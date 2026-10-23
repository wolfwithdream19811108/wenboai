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
use app\portal\model\PortalPostModel;
use app\portal\model\PortalImageColorModel;
use think\Db;
use think\paginator\driver\Bootstrap;
class SamecolorController extends HomeBaseController
{
    /**
     * 搜索
     * @return mixed
     */
     

   public function index()
    {
        $color = $this->request->param('color');
        $page = $this->request->param('page', 1, 'intval');    // 获取页码
        $limit = $this->request->param('limit', 60, 'intval'); // 每页数量
        $color="#".str_replace('#', '', $color);;
    
        if (empty($color)) {
            $this->error("色彩不能为空！请重新输入！");
        }
            $cacheKey = md5( 'color_match:' . $color.$page);
            $result = cache($cacheKey);   
            if(empty($result)){
                $result = $this->matchWithPaginate($color, $limit);
                cache($cacheKey, $result);
            }            
   
            $this->assignResults($result['articles'], $color, $result['page_html']);
            return $this->fetch('/samecolor');
    

            
        }
      
        
        

     
   

    private function assignResults($articles, $keyword, $pageHtml = '') {
        $this->assign([
            'articles' => $articles,
            'color'  => $keyword,
            'page'     => $pageHtml // 传递分页HTML到模板
        ]);
    }
    
    /**
     * 标准化十六进制颜色代码
     */
    protected function normalizeHex($hex)
    {
        $hex = strtoupper(str_replace('#', '', $hex));
        
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        
        return (strlen($hex) === 6) ? $hex : false;
    }

    /**
     * 十六进制转RGB
     */
    protected function hexToRgb($hex)
    {
        if (!$hex = $this->normalizeHex($hex)) return false;
        
        return array_map(function($v) {
            return hexdec($v);
        }, str_split($hex, 2));
    }

    /**
     * RGB转HSV颜色空间
     */
    protected function rgbToHsv($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        // 计算色相
        if ($delta == 0) {
            $h = 0;
        } elseif ($max == $r) {
            $h = 60 * fmod(($g - $b)/$delta, 6);
        } elseif ($max == $g) {
            $h = 60 * (($b - $r)/$delta + 2);
        } else {
            $h = 60 * (($r - $g)/$delta + 4);
        }
        
        $h = ($h < 0) ? $h + 360 : $h;
        
        // 计算饱和度和明度
        $s = ($max == 0) ? 0 : ($delta / $max * 100);
        $v = $max * 100;

        return [
            'h' => round($h, 2),
            's' => round($s, 2),
            'v' => round($v, 2)
        ];
    }

    /**
     * 计算HSV颜色距离
     */
    protected function hsvDistance($hsv1, $hsv2)
    {
        // 色相差处理（360度环形）
        $hDiff = abs($hsv1['h'] - $hsv2['h']);
        $hDiff = min($hDiff, 360 - $hDiff) / 180;

        // 饱和度与明度差异
        $sDiff = abs($hsv1['s'] - $hsv2['s']) / 100;
        $vDiff = abs($hsv1['v'] - $hsv2['v']) / 100;

        // 场景1：更关注色相差异（适合区分红/绿/蓝）
        //$weights = ['h' => 0.8, 's' => 0.15, 'v' => 0.05];
        
        // 场景2：关注明度差异（适合黑白照片）
        //$weights = ['h' => 0.3, 's' => 0.2, 'v' => 0.5];


        // 加权系数
        $weights = [
            'h' => 0.6,
            's' => 0.3,
            'v' => 0.1
        ];

        return sqrt(
            $weights['h'] * pow($hDiff, 2) +
            $weights['s'] * pow($sDiff, 2) +
            $weights['v'] * pow($vDiff, 2)
        );
    }


    /**
     * 带分页的相似颜色查询（优化版）
     * 
     * 配置组合	匹配精度	查询耗时	适用场景
        阈值=0.1, 权重[0.7,0.2,0.1]	高	1200ms	商品颜色严格匹配
        阈值=0.2, 权重[0.5,0.3,0.2]	中	600ms	常规推荐系统
        阈值=0.3, 权重[0.3,0.3,0.4]	低	300ms	艺术风格模糊匹配
     * 可通过配置文件管理预设方案：
     * 
            // config/color_match.php
            return [
                'presets' => [
                    'strict' => [
                        'threshold' => 0.1,
                        'weights' => [0.7, 0.2, 0.1]
                    ],
                    'artistic' => [
                        'threshold' => 0.3,
                        'weights' => [0.4, 0.4, 0.2]
                    ]
                ]
            ];
            
            // 控制器调用
            $preset = config('color_match.presets.strict');
            $result = $model->matchWithPreset($color, $preset);     * 
     * 
     * 
     * 
     * 
     */
    public function matchWithPaginate($color, $limit = 10)
    {
        $portalColorModel = new PortalImageColorModel();
        $portalPostModel = new PortalPostModel();

        // 构建查询条件
        $inputHsv = $this->rgbToHsv(...$this->hexToRgb($color));
        
        // 使用模型范围查询
        /*threshold取值意义
        0.05：严格匹配（几乎相同的颜色）
        0.15：常规相似匹配
        0.25：宽松匹配（色系相近即可*/
        $query = $portalColorModel::scope('similarColor', [
                'h' => $inputHsv['h'],
                's' => $inputHsv['s'],
                'v' => $inputHsv['v'],
                'threshold' => 0.6,// 从0.4提高到0.6，放宽匹配阈值
                'weights' => [0.3, 0.3, 0.4], // // 降低色相权重，提高明度权重
                'hRange' => 60,      // 扩大色相范围
                'svRange' => 30      // 扩大饱和度和明度范围               
            ])
            ->order('weight', 'desc');

        // 分页查询
        $paginate = $query->paginate([
            'list_rows' => $limit,
            'path' => url('portal/Samecolor/'),
            'query' => ['color' => $color]
        ]);

        // 获取文章
        $postIds = array_column($paginate->items(), 'post_id');
        $articles = $portalPostModel->whereIn('id', $postIds)->select();

        return [
            'articles' => $articles,
            'page_html' => $paginate->render(),
            'color' => $color
        ];
    }    
    
    
     
}
