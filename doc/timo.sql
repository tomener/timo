/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50727
Source Host           : localhost:3306
Source Database       : timo

Target Server Type    : MYSQL
Target Server Version : 50727
File Encoding         : 65001

Date: 2022-09-27 12:28:16
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL DEFAULT '' COMMENT '姓名',
  `nickname` varchar(16) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(32) NOT NULL DEFAULT '' COMMENT '头像',
  `age` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '年龄',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 -1删除 0禁用 1正常',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最近登录时间',
  `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间 ',
  `updated` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', '刘禅', 'liushan', 'a/b/c/8973947883.jpg', '100', '1', '1664251543', '1664250183', '0');
INSERT INTO `user` VALUES ('2', '赵云', 'zhaoyun', 'd/e/f/7837287494.jpg', '120', '1', '0', '1664250183', '0');
