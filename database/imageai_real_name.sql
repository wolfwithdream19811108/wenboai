-- ImageAI 腾讯云实名认证。可重复执行；数据库表前缀不是 cmf_ 时请相应替换。
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
