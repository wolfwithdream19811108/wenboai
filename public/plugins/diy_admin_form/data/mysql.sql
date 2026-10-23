SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `cmf_plugin_code_app`;
CREATE TABLE `cmf_plugin_code_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) DEFAULT '' COMMENT '应用名称',
  `menu_name` varchar(30) DEFAULT '' COMMENT '系统菜单名称',
  `menu_order` int(10) unsigned DEFAULT '50' COMMENT '序号',
  `menu_icon` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `table_name` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `cmf_plugin_code_tables`;
CREATE TABLE `cmf_plugin_code_tables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(10) unsigned DEFAULT '0' COMMENT '应用id',
  `table_name` varchar(100) NOT NULL DEFAULT '' COMMENT '数据表名称(不含前缀)',
  `table_tag` varchar(10) DEFAULT '' COMMENT '数据表标识文字',
  `key_name` varchar(100) DEFAULT '' COMMENT '主键字段名称',
  `export_flag` tinyint(3) unsigned DEFAULT '0' COMMENT '列表页是否具有导出excel功能',
  `page_flag` tinyint(3) unsigned DEFAULT '0' COMMENT '列表页是否可以自定义分页行数',
  `batch_delete` tinyint(3) unsigned DEFAULT '0' COMMENT '列表页是否可以批量删除行',
  `batch_setfield` varchar(100) DEFAULT '' COMMENT '列表页可以批量设置值的字段名称',
  `menu_name` varchar(30) DEFAULT '' COMMENT '系统菜单名称',
  `menu_parent` int(10) unsigned DEFAULT '0' COMMENT '上级菜单',
  `menu_parent_url` varchar(255) DEFAULT '' COMMENT '应用名/控制器名/方法名',
  `menu_order` int(10) unsigned DEFAULT '50' COMMENT '序号',
  `menu_icon` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `table_name` (`table_name`,`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `cmf_plugin_code_fields`;
CREATE TABLE `cmf_plugin_code_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `table_name` varchar(100) DEFAULT '' COMMENT '数据表名称',
  `field_name` varchar(100) DEFAULT '' COMMENT '字段名称',
  `field_datatype` varchar(100) DEFAULT '' COMMENT '字段数据类型',
  `field_typename` char(10) DEFAULT '' COMMENT '字段类型大类:数值,字符,日期',
  `field_null` char(10) DEFAULT '' COMMENT '字段是否可空值',
  `field_key` char(10) DEFAULT '' COMMENT '是否为关键字PRI',
  `field_default` varchar(100) DEFAULT '' COMMENT '字段默认值',
  `field_extra` varchar(100) DEFAULT '' COMMENT '字段附加信息',
  `field_tag` varchar(100) DEFAULT '' COMMENT '字段标识',
  `control_type` char(20) DEFAULT '' COMMENT '字段在列表页上使用查询的控件:文本,列表等',
  `control_default` varchar(100) DEFAULT '' COMMENT '控件默认值',
  `ctr_check_v0` int(11) DEFAULT '0' COMMENT '复选框未选中时的默认值',
  `ctr_check_v1` int(11) DEFAULT '1' COMMENT '复选框选中时的默认值',
  `ctr_check_default` int(10) unsigned DEFAULT '0' COMMENT '复选框默认状态  0:未选中   1:选中',
  `compare_type` char(30) DEFAULT '' COMMENT '控件与数据表字段运算方式:模糊或等于',
  `ctr_select_item` varchar(255) DEFAULT '' COMMENT '列表控件自定义项',
  `ctr_select_table_info` varchar(255) DEFAULT '' COMMENT '列表项内容来自数据表:名称,key,val',
  `ctr_min_val` char(10) DEFAULT '0' COMMENT '列表框或复选框的最小有效值',
  `used_list` tinyint(3) unsigned DEFAULT '1' COMMENT '作为列表页字段',
  `used_count_sum` tinyint(3) unsigned DEFAULT '0' COMMENT '统计字段:求和',
  `used_count_avg` tinyint(3) unsigned DEFAULT '0' COMMENT '统计字段:平均数',
  `used_count_max` tinyint(3) unsigned DEFAULT '0' COMMENT '统计字段:最大值',
  `used_count_min` tinyint(3) unsigned DEFAULT '0' COMMENT '统计字段:最小值',
  `used_add` tinyint(3) unsigned DEFAULT '1' COMMENT '添加数据页',
  `used_edit` tinyint(3) unsigned DEFAULT '1' COMMENT '编辑数据页',
  PRIMARY KEY (`id`),
  KEY `table_name` (`id`) USING BTREE,
  KEY `field_name` (`field_name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;