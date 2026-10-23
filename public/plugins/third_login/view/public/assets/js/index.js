// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-03 21:47:23
// +----------------------------------------------
layui.use(['element', 'form'], function(){
	var element = layui.element;
	var form    = layui.form;

	form.on("submit(saveConfig)", function(data){
		request_post(data.elem, GV.WEB_ROOT + 'plugin/third_login/admin_index/dosave', data.field);
		return false;
	})
})

/**
 * post请求
 * @param  object   btn      表单提交按钮
 * @param  string   url      请求url
 * @param  json     data     请求数据
 * @param  function callback 回调函数
 */
function request_post(btn, url, data, callback) {
	var $btn = $(btn);
	var text = $btn.text();

	$btn.text(text + '...').prop('disabled', true).addClass('layui-disabled');

	$.post(url, data, function(response){
		layer.msg(response['msg']);
		$btn.text(text).prop('disabled', false).removeClass('layui-disabled');
	})
}