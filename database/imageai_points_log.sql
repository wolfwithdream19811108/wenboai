CREATE TABLE IF NOT EXISTS `cmf_imageai_points_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `operation` varchar(20) NOT NULL DEFAULT '',
  `change_points` int(11) NOT NULL DEFAULT '0',
  `before_points` int(11) NOT NULL DEFAULT '0',
  `after_points` int(11) NOT NULL DEFAULT '0',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `admin_ip` varchar(45) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`user_id`,`create_time`),
  KEY `idx_admin_time` (`admin_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
