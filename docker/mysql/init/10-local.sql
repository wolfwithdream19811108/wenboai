-- 让没有生产数据备份时，二开首页也能正常打开。
CREATE TABLE IF NOT EXISTS `cmf_portal_chat_question` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cmf_portal_chat_question` (`question`) VALUES
  ('请介绍一下中国山水画的发展脉络'),
  ('如何欣赏一幅中国画'),
  ('齐白石的艺术风格有什么特点');

INSERT INTO `cmf_option` (`autoload`, `option_name`, `option_value`) VALUES
  (1, 'function_settings', '{"analysis_object":true,"need_login":false,"open_AI3D":false}'),
  (1, 'site_info', '{"site_name":"闻博AI本地测试环境","site_seo_title":"闻博AI本地测试环境","site_seo_keywords":"","site_seo_description":""}')
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- 仅供本机测试：用户名 admin，密码 admin123。
INSERT INTO `cmf_user`
  (`id`, `user_type`, `create_time`, `user_status`, `user_login`, `user_pass`, `user_nickname`, `user_email`)
VALUES
  (1, 1, UNIX_TIMESTAMP(), 1, 'admin', '###0d097f7b04ca3c1e634a973c80452776', '本地管理员', 'admin@localhost')
ON DUPLICATE KEY UPDATE `user_login` = VALUES(`user_login`);

INSERT INTO `cmf_role_user` (`role_id`, `user_id`)
SELECT 1, 1 FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `cmf_role_user` WHERE `role_id` = 1 AND `user_id` = 1
);
