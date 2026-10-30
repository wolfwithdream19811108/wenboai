-- ImageAI 测试账号批次及账号资料表。
-- 执行前请确认当前数据库表前缀为 cmf_。

CREATE TABLE IF NOT EXISTS `cmf_imageai_test_account_batch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(32) NOT NULL,
  `account_prefix` varchar(32) NOT NULL DEFAULT '',
  `start_number` varchar(16) NOT NULL DEFAULT '',
  `account_count` int(10) unsigned NOT NULL DEFAULT '0',
  `initial_points` int(11) NOT NULL DEFAULT '0',
  `expires_at` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_code` (`batch_code`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_imageai_test_account` (
  `user_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `expires_at` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `idx_batch_status` (`batch_id`,`status`),
  KEY `idx_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
