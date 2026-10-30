-- ImageAI developer portal schema (MySQL 5.7+)
-- Change `cmf_` if the deployed database uses a different prefix.

CREATE TABLE IF NOT EXISTS `cmf_imageai_user_profile` (
  `user_id` bigint(20) unsigned NOT NULL,
  `account_mode` varchar(20) NOT NULL DEFAULT 'normal',
  `register_source` varchar(30) NOT NULL DEFAULT 'imageai',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `service_agreement_version` varchar(30) NOT NULL DEFAULT '',
  `privacy_agreement_version` varchar(30) NOT NULL DEFAULT '',
  `agreement_accepted_at` int(10) unsigned NOT NULL DEFAULT '0',
  `agreement_ip` varchar(45) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `idx_mode_status` (`account_mode`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_imageai_real_name_verification` (
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'unverified',
  `provider` varchar(20) NOT NULL DEFAULT 'tencent',
  `id_card_hmac` char(64) DEFAULT NULL,
  `id_card_last4` char(4) NOT NULL DEFAULT '',
  `adult_eligible_at` int(10) unsigned NOT NULL DEFAULT '0',
  `provider_request_id` varchar(64) NOT NULL DEFAULT '',
  `verified_at` int(10) unsigned NOT NULL DEFAULT '0',
  `last_attempt_at` int(10) unsigned NOT NULL DEFAULT '0',
  `last_result_code` varchar(64) NOT NULL DEFAULT '',
  `last_error_message` varchar(255) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `idx_id_card_hmac` (`id_card_hmac`),
  KEY `idx_status_time` (`status`,`verified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `cmf_imageai_real_name_verification`
  MODIFY COLUMN `last_result_code` varchar(64) NOT NULL DEFAULT '';

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

CREATE TABLE IF NOT EXISTS `cmf_imageai_api_key` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT '',
  `key_hash` char(64) NOT NULL,
  `key_prefix` varchar(20) NOT NULL DEFAULT '',
  `key_suffix` varchar(10) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `expires_at` int(10) unsigned DEFAULT NULL,
  `last_used_at` int(10) unsigned NOT NULL DEFAULT '0',
  `last_used_ip` varchar(45) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_hash` (`key_hash`),
  KEY `idx_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_imageai_api_request_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` char(32) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `api_key_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `model` varchar(50) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'error',
  `reason` varchar(500) NOT NULL DEFAULT '',
  `prompt_digest` char(64) NOT NULL DEFAULT '',
  `image_url` varchar(500) NOT NULL DEFAULT '',
  `points` int(10) unsigned NOT NULL DEFAULT '0',
  `duration_ms` int(10) unsigned NOT NULL DEFAULT '0',
  `client_ip` varchar(45) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_request_id` (`request_id`),
  KEY `idx_user_time` (`user_id`,`create_time`),
  KEY `idx_status_time` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_imageai_failure_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` char(32) NOT NULL DEFAULT '',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `api_key_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `stage` varchar(30) NOT NULL DEFAULT 'unknown',
  `error_code` varchar(64) NOT NULL DEFAULT 'UNKNOWN_ERROR',
  `error_message` varchar(1000) NOT NULL DEFAULT '',
  `http_status` int(10) unsigned NOT NULL DEFAULT '0',
  `prompt_digest` char(64) NOT NULL DEFAULT '',
  `local_image_url` varchar(500) NOT NULL DEFAULT '',
  `local_file_path` varchar(1000) NOT NULL DEFAULT '',
  `remote_path` varchar(500) NOT NULL DEFAULT '',
  `remote_image_url` varchar(500) NOT NULL DEFAULT '',
  `context_json` text,
  `retry_count` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `resolved_at` int(10) unsigned NOT NULL DEFAULT '0',
  `client_ip` varchar(45) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_stage_status_time` (`stage`,`status`,`create_time`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_imageai_work_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_id` bigint(20) unsigned NOT NULL,
  `request_id` char(32) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `api_key_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `source` varchar(20) NOT NULL DEFAULT 'api',
  `model` varchar(50) NOT NULL DEFAULT '',
  `prompt_digest` char(64) NOT NULL DEFAULT '',
  `upstream_task_id` varchar(100) NOT NULL DEFAULT '',
  `image_url` varchar(500) NOT NULL DEFAULT '',
  `width` int(10) unsigned NOT NULL DEFAULT '0',
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `produce_id` varchar(100) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_request_id` (`request_id`),
  KEY `idx_work_id` (`work_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The production project already has these two tables. They are included so a
-- fresh local test database can exercise ImageAI without importing production.
CREATE TABLE IF NOT EXISTS `cmf_user_points` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `points` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_ai_user_works` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `key` varchar(100) NOT NULL DEFAULT '',
  `create_timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `create_date` datetime DEFAULT NULL,
  `picture_url` varchar(500) NOT NULL DEFAULT '',
  `prompt` text,
  `control_net_info` text,
  `advanced_info` text,
  `cost_info` text,
  `prefine_multiple` decimal(10,2) NOT NULL DEFAULT '0.00',
  `adetailer` text,
  `supersize_multiple` decimal(10,2) NOT NULL DEFAULT '0.00',
  `model_code` int(11) NOT NULL DEFAULT '0',
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `update_timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uid_time` (`uid`,`create_timestamp`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
