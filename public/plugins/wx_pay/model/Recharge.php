<?php

declare(strict_types=1);

namespace plugins\wx_pay\model;
use think\Model;

class Recharge extends Model
{

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'recharge';

}
