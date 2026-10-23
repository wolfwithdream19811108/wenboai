SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for cmf_plugin_
-- ----------------------------
DROP TABLE IF EXISTS `cmf_plugin_message_board`;
CREATE TABLE `cmf_plugin_message_board` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID号',
  `cid` bigint(20) NOT NULL  COMMENT '分类ID号',
  `name` varchar(50) DEFAULT NULL COMMENT '姓名',
  `mobile` varchar(50) DEFAULT NULL COMMENT '手机号码',
  `tel` varchar(50) DEFAULT NULL COMMENT '固定电话',
  `company` varchar(255) DEFAULT NULL COMMENT '工作',
  `fax` varchar(15) DEFAULT NULL COMMENT '传真',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `area` varchar(255) DEFAULT NULL COMMENT '地区',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `referrer` varchar(255) DEFAULT NULL COMMENT '来源页面',
  `message` text COMMENT '信息',
  `isread` tinyint(2) DEFAULT '0' COMMENT '是否查看过',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `more` text COMMENT '扩展属性',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of cmf_plugin_
-- ----------------------------

SET FOREIGN_KEY_CHECKS=1;

