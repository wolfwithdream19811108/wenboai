-- ImageAI 后台菜单。可重复执行；数据库表前缀不是 cmf_ 时请相应替换。
START TRANSACTION;

INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT 0,0,1,60,'imageai','AdminPortal','default','','ImageAI 开放平台','image','ImageAI 用户、密钥、请求、作品和协议管理'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `cmf_admin_menu`
    WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='default'
);

SET @imageai_menu_root = (
    SELECT `id` FROM `cmf_admin_menu`
    WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='default'
    ORDER BY `id` LIMIT 1
);

INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_menu_root,1,1,10,'imageai','AdminPortal','users','','开发者用户','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='users');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_menu_root,1,1,20,'imageai','AdminPortal','keys','','API Keys','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='keys');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_menu_root,1,1,30,'imageai','AdminPortal','requests','','API 请求','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='requests');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_menu_root,1,1,40,'imageai','AdminPortal','works','','生成作品','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='works');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_menu_root,1,1,50,'imageai','AdminPortal','agreements','','协议管理','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='agreements');

SET @imageai_users_menu = (SELECT `id` FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='users' ORDER BY `id` LIMIT 1);
SET @imageai_keys_menu = (SELECT `id` FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='keys' ORDER BY `id` LIMIT 1);
SET @imageai_agreements_menu = (SELECT `id` FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='agreements' ORDER BY `id` LIMIT 1);

INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_users_menu,2,0,10000,'imageai','AdminPortal','userStatus','','启停开发者用户','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='userStatus');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_keys_menu,2,0,10000,'imageai','AdminPortal','revokeKey','','撤销 API Key','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='revokeKey');
INSERT INTO `cmf_admin_menu`
    (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT @imageai_agreements_menu,2,0,10000,'imageai','AdminPortal','agreementPost','','发布协议','',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='imageai' AND `controller`='AdminPortal' AND `action`='agreementPost');

INSERT IGNORE INTO `cmf_auth_rule` (`status`,`app`,`type`,`name`,`param`,`title`,`condition`) VALUES
(1,'imageai','admin_url','imageai/AdminPortal/default','','ImageAI 开放平台',''),
(1,'imageai','admin_url','imageai/AdminPortal/users','','开发者用户',''),
(1,'imageai','admin_url','imageai/AdminPortal/keys','','API Keys',''),
(1,'imageai','admin_url','imageai/AdminPortal/requests','','API 请求',''),
(1,'imageai','admin_url','imageai/AdminPortal/works','','生成作品',''),
(1,'imageai','admin_url','imageai/AdminPortal/agreements','','协议管理',''),
(1,'imageai','admin_url','imageai/AdminPortal/userStatus','','启停开发者用户',''),
(1,'imageai','admin_url','imageai/AdminPortal/revokeKey','','撤销 API Key',''),
(1,'imageai','admin_url','imageai/AdminPortal/agreementPost','','发布协议','');

COMMIT;
