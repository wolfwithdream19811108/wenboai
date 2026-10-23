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
namespace app\portal\model;

use think\Model;

class PortalImageColorModel extends Model
{


    /**
     * 相似颜色查询范围
     */
    public function scopeSimilarColor($query, $params)
{
    // 默认参数设置
    $defaults = [
        'hRange' => 60,    // 从30提高到60
        'svRange' => 30,   // 从20提高到30
        'weights' => [0.3, 0.3, 0.4] // 调整权重分配
    ];
    $params = array_merge($defaults, $params);

    // 获取距离计算表达式
    $distanceExpr = $this->getDistanceExpression($params);

    // 构建基础查询
    $query = $query->where(function($q) use ($params) {
        // 色相环形查询
        $q->where(function($q) use ($params) {
            $hMin = max(0, $params['h'] - $params['hRange']);
            $hMax = min(360, $params['h'] + $params['hRange']);
            
            $q->whereBetween('h', [$hMin, $hMax]);
            
            // 处理色相环形特性
            if ($params['h'] < $params['hRange']) {
                $q->whereOr('h', '>=', 360 - ($params['hRange'] - $params['h']));
            } elseif ($params['h'] > 360 - $params['hRange']) {
                $q->whereOr('h', '<=', ($params['h'] + $params['hRange']) - 360);
            }
        })
        ->whereBetween('s', [
            max(0, $params['s'] - $params['svRange']),
            min(100, $params['s'] + $params['svRange'])
        ])
        ->whereBetween('v', [
            max(0, $params['v'] - $params['svRange']),
            min(100, $params['v'] + $params['svRange'])
        ]);
    });

    // 添加计算字段
    $query = $query->field(['*', $distanceExpr.' AS weight']);

    // 阈值过滤（使用子查询解决HAVING限制）
    if (isset($params['threshold']) && $params['threshold'] !== null) {
        $minWeight = 1 - $params['threshold'];
        $query = $query->whereExists(function($q) use ($distanceExpr, $minWeight) {
            $q->table('cmf_portal_image_color')
              ->whereRaw($distanceExpr.' >= '.floatval($minWeight));
        });
    }

    return $query;
}
    protected function getDistanceExpression($params)
    {
        // 解构权重参数
        [$hWeight, $sWeight, $vWeight] = $params['weights'];

    
        return sprintf("1 - (
            %.2f * LEAST(ABS(h - %d), 360 - ABS(h - %d)) / 180 +
            %.2f * ABS(s - %d) / 100 +
            %.2f * ABS(v - %d) / 100
        )", 
        $hWeight, $params['h'], $params['h'],
        $sWeight, $params['s'],
        $vWeight, $params['v']);
    }
}