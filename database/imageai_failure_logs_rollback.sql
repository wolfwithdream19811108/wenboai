-- 移除 ImageAI 失败日志及其后台菜单，不影响作品、请求、用户或 API Key 数据。
DELETE FROM `cmf_auth_rule`
WHERE `app`='imageai' AND `type`='admin_url'
  AND `name` IN ('imageai/AdminPortal/failureLogs','imageai/AdminPortal/retryFailure');
DELETE FROM `cmf_admin_menu`
WHERE `app`='imageai' AND `controller`='AdminPortal'
  AND `action` IN ('retryFailure','failureLogs');
DROP TABLE IF EXISTS `cmf_imageai_failure_log`;
