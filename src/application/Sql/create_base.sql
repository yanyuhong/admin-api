
CREATE TABLE `gw_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(32) NOT NULL COMMENT '用户名',
  `password` VARCHAR(32) NULL COMMENT '密码hash',
  `token` VARCHAR(32) NULL COMMENT '登陆票据',
  `avatar` TEXT NULL COMMENT '头像url',
  `name` VARCHAR(32) NULL COMMENT '姓名',
  `mobile` VARCHAR(16) NULL COMMENT '手机号',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  UNIQUE INDEX `token_UNIQUE` (`token` ASC)
)
ENGINE = InnoDB
COMMENT = '用户信息';

INSERT INTO `gw_user` (`username`, `password`)
VALUES ('admin', '7976c253b7450445088b647b7667b63b');


CREATE TABLE IF NOT EXISTS `gw_page` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(32) NOT NULL COMMENT '标题',
  `type` INT NOT NULL COMMENT '类型 1 - 列表页',
  `parent_id` INT NOT NULL DEFAULT 0 COMMENT '子页面所属父页面ID',
  `group` VARCHAR(32) NULL COMMENT '首页分组',
  `data_service` VARCHAR(64) NULL COMMENT '数据服务地址',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面配置';

INSERT INTO `gw_page` ( `title`, `type`, `parent_id`, `group`, `data_service`)
VALUES
	('用户管理', 1, 0, '系统设置', ''),
	('权限管理', 1, 0, '系统设置', ''),
	('页面管理', 1, 0, '系统设置', '');
