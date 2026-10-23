<?php
// +----------------------------------------------------------------------
// | d_tag [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 DaliyCode All rights reserved.
// +----------------------------------------------------------------------
// | Author: DaliyCode <3471677985@qq.com> <author_url:dalicode.com>
// +----------------------------------------------------------------------

return array(
    'tag_type'     => array(
        'title'   => '是否开启:',
        'type'    => 'radio',
        'options' => [
            '1' => '开',
            '2' => '关',
        ],
        'value'   => '1',
    ),
    'tag_show_num' => array(
        'title'   => '标签显示个数:',
        'type'    => 'number',
        "rule"    => [
            "require" => true,
        ],
        "message" => [
            "require" => '标签显示个数不能为空',
        ],
        'value'   => '30',
        'tip'     => '热门标签的最多个数，默认30个',
    ),
);
