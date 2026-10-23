SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for cmf_plugin_duanshi_guestbook
-- ----------------------------
DROP TABLE IF EXISTS `cmf_plugin_duanshi_ad`;
CREATE TABLE `cmf_plugin_duanshi_ad` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID号',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '状态,1:显示,0不显示',
  `delete_time` int(10) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `start_time` int(10) NOT NULL DEFAULT '0' COMMENT '广告开始时间',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '广告结束时间',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '广告名称',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '广告地址',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '广告图片',
  `content` text COMMENT '广告内容',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '广告备注',
  `target` varchar(10) NOT NULL DEFAULT '' COMMENT '广告名称',
  `display` tinyint(3) NOT NULL DEFAULT '0' COMMENT '展示方式,0:静态展示,1浮窗展示',
  `offset` varchar(10) NOT NULL DEFAULT '0' COMMENT '浮窗位置',
  `anim` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '浮窗动画',
  `show_time` int(10) NOT NULL DEFAULT '0' COMMENT '弹出时间',
  `duration_time` int(10) NOT NULL DEFAULT '0' COMMENT '显示时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of cmf_plugin_duanshi_guestbook
-- ----------------------------
SET FOREIGN_KEY_CHECKS=1;