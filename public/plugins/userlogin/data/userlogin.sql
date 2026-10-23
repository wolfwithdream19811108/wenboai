--
-- 表的结构 `cmf_user_login_log`
--

CREATE TABLE IF NOT EXISTS `cmf_user_login_log` (
  `id` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `pwd` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `succeed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,1:成功,0失败',
  `time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员登录记录';


--
-- AUTO_INCREMENT for table `cmf_user_login_log`
--
ALTER TABLE `cmf_user_login_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;