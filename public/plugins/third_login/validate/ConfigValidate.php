<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-03 22:11:50
// +----------------------------------------------
namespace plugins\third_login\validate;

use think\Validate;

class ConfigValidate extends Validate{
	protected $rule = [
		'id' 			=> 'require',
		'app_id'		=> 'require|max:32',
		'app_secret'	=> 'require|max:32'
	];

	protected $message = [
		'id.require' 			=> 'id不能为空！',
		'app_id.require'		=> 'App ID不能为空！',
		'app_id.max'			=> 'App ID不能超过32个字符！',
		'app_secret.require'	=> 'App Secret不能为空！',
		'app_secret.max'		=> 'App Secret不能超过32个字符！'
	];

	protected $scene = [
		'update' => ['id', 'app_id', 'app_secret']
	];
}