-- 仅移除测试账号管理表，不删除 cmf_user、积分、作品、请求或 API Key 数据。
DROP TABLE IF EXISTS `cmf_imageai_test_account`;
DROP TABLE IF EXISTS `cmf_imageai_test_account_batch`;
