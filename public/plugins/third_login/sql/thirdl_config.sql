/*
 Navicat Premium Data Transfer

 Source Server         : 127.0.0.1
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : 127.0.0.1:3306
 Source Schema         : thinkcmf-5.0.190111-wx

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 15/06/2019 20:29:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for thirdl_config
-- ----------------------------
DROP TABLE IF EXISTS `thirdl_config`;
CREATE TABLE `thirdl_config`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'App Id',
  `app_secret` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'App Secret',
  `type` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '(qq:qq登录,wechat:微信登录,sina:新浪微博登录)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of thirdl_config
-- ----------------------------
INSERT INTO `thirdl_config` VALUES (1, '', '', 'qq');
INSERT INTO `thirdl_config` VALUES (2, '', '', 'sina');
INSERT INTO `thirdl_config` VALUES (3, '', '', 'wechat');

SET FOREIGN_KEY_CHECKS = 1;
