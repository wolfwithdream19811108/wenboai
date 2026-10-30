-- Only removes tables introduced by the ImageAI portal.
-- Existing cmf_user, cmf_user_points and cmf_ai_user_works data is preserved.
DROP TABLE IF EXISTS `cmf_imageai_failure_log`;
DROP TABLE IF EXISTS `cmf_imageai_test_account`;
DROP TABLE IF EXISTS `cmf_imageai_test_account_batch`;
DROP TABLE IF EXISTS `cmf_imageai_work_detail`;
DROP TABLE IF EXISTS `cmf_imageai_api_request_log`;
DROP TABLE IF EXISTS `cmf_imageai_api_key`;
DROP TABLE IF EXISTS `cmf_imageai_user_profile`;
