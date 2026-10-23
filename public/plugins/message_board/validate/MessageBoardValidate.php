<?php
// +----------------------------------------------------------------------
// | Author: Sam
// +----------------------------------------------------------------------
namespace plugins\message_board\validate;
use think\Validate;

class MessageBoardValidate extends Validate
{
    protected $rule = [
        'area' => 'require',
        'name' => 'require',
        'mobile' => 'require',
    ];

    protected $message = [
        'area.require' => '请选择您所在区域',
        'name.require' => '请输入您的姓名',
        'mobile.require' => '请输入您的联系电话',
    ];

}