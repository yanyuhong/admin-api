
CREATE TABLE `gw_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(32) NOT NULL COMMENT '用户名',
  `password` VARCHAR(32) NULL COMMENT '密码hash',
  `token` VARCHAR(32) NULL COMMENT '登陆票据',
  `avatar` TEXT NULL COMMENT '头像url',
  `name` VARCHAR(32) NULL COMMENT '姓名',
  `mobile` VARCHAR(16) NULL COMMENT '手机号',
  `status` INT NOT NULL DEFAULT 1 COMMENT '状态 1 - 正常 2 - 冻结',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  UNIQUE INDEX `token_UNIQUE` (`token` ASC)
)
ENGINE = InnoDB
COMMENT = '用户信息';

CREATE TABLE IF NOT EXISTS `gw_page` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(32) NOT NULL COMMENT '标题',
  `type` INT NOT NULL COMMENT '类型 1 - 列表页',
  `parent_id` INT NOT NULL DEFAULT 0 COMMENT '子页面所属父页面ID',
  `group` VARCHAR(32) NULL COMMENT '首页分组',
  `data_service` VARCHAR(64) NULL COMMENT '数据服务方法',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面配置';

CREATE TABLE IF NOT EXISTS `gw_page_filter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page_id` INT NOT NULL,
  `label` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '标签',
  `name` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '名称（查询字段）',
  `type` INT NOT NULL DEFAULT 0 COMMENT '类型 1 - 输入框 2 - 选择框 3 - 指定日期 4 - 日期范围',
  `default` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '默认值',
  `tip` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '水印',
  `rank` INT NOT NULL DEFAULT 1 COMMENT '排序从小到大',
  `data` VARCHAR(32) NULL COMMENT '选择框数据获取方法',
  `label_width` INT NOT NULL DEFAULT 100 COMMENT '标签宽度px',
  `value_width` INT NOT NULL DEFAULT 100 COMMENT '输入框宽度px',
  `operation` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '数据操作方法（数据库where方法）',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面筛选条件';

CREATE TABLE IF NOT EXISTS `gw_page_form` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page_id` INT NOT NULL,
  `name` VARCHAR(32) NOT NULL NULL,
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面表单';

CREATE TABLE IF NOT EXISTS `gw_page_form_field` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `form_id` INT NOT NULL,
  `label` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '标签',
  `name` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '名称（保存至数据库字段名）',
  `type` INT NOT NULL DEFAULT 0 COMMENT '类型 1 – 输入框，2 – 单项选择框，3 – 多项选择框，4 – 指定日期， 5 – 文件',
  `default` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '默认值',
  `tip` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '水印',
  `rank` INT NOT NULL DEFAULT 1 COMMENT '排序从小到大',
  `required` INT NOT NULL DEFAULT 0 COMMENT '必填状态 1 - 必填 2 - 非必填',
  `disabled` INT NOT NULL DEFAULT 0 COMMENT '编辑状态 1 - 可编辑 2 - 不可编辑',
  `data` VARCHAR(32) NULL COMMENT '选择框数据',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '表单字段';

CREATE TABLE IF NOT EXISTS `gw_page_operate` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page_id` INT NOT NULL,
  `label` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '名称',
  `method` VARCHAR(128) NOT NULL COMMENT '操作方法',
  `select` INT NOT NULL DEFAULT 0 COMMENT '选择记录状态 1- 需要 2 不需要',
  `rank` INT NOT NULL COMMENT '排序从小到大',
  `form_id` INT NULL COMMENT '关联表单（空时没有表单）',
  `confirm_text` VARCHAR(128) NULL COMMENT '提示文字',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面操作项';

CREATE TABLE IF NOT EXISTS `gw_page_header` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page_id` INT NOT NULL,
  `label` VARCHAR(64) NOT NULL COMMENT '标签',
  `name` VARCHAR(64) NOT NULL COMMENT '字段名',
  `type` INT NOT NULL COMMENT '类型 1 - 文字 2 - 图片 3 - 链接 4 - 按钮',
  `visible` INT NOT NULL COMMENT '可见状态 1 - 可见 2 - 不可见',
  `rank` INT NOT NULL DEFAULT 1 COMMENT '排序从小到大',
  `target_page_id` INT NULL COMMENT '子页面id',
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
COMMENT = '页面数据栏';




INSERT INTO `gw_user` (`id`, `username`, `password`)
VALUES (1, 'admin', '7976c253b7450445088b647b7667b63b');

INSERT INTO `gw_page` (`id`, `title`, `type`, `parent_id`, `group`, `data_service`)
VALUES
	(1, '用户管理', 1, 0, '系统设置', ''),
	(2, '权限管理', 1, 0, '系统设置', ''),
	(3, '页面管理', 1, 0, '系统设置', '');

INSERT INTO `gw_page_filter` (`id`, `page_id`, `label`, `name`, `type`, `default`, `tip`, `rank`, `data`, `label_width`, `value_width`, `operation`)
VALUES
	(1, 1, '用户名', 'username', 1, '', '', 1, NULL, 100, 300, 'like'),
	(2, 1, '手机号', 'mobile', 1, '', '', 2, NULL, 100, 300, '='),
	(3, 1, '状态', 'status', 2, '', '', 3, 'userStatus', 100, 300, '='),
	(4, 1, '注册时间', 'created_at', 4, '', '', 4, NULL, 100, 500, '');

INSERT INTO `gw_page_form` (`id`, `page_id`, `name`)
VALUES
	(1, 1, '用户信息');

INSERT INTO `gw_page_form_field` (`id`, `form_id`, `label`, `name`, `type`, `default`, `tip`, `rank`, `required`, `disabled`, `data`)
VALUES
	(1, 1, '用户名', 'username', 1, '', '', 1, 1, 1, NULL),
	(2, 1, '姓名', 'name', 1, '', '', 2, 1, 1, NULL),
	(3, 1, '手机号', 'mobile', 1, '', '', 3, 1, 1, NULL),
	(4, 1, '头像', 'avatar', 5, '', '', 4, 1, 1, NULL),
	(5, 1, '状态', 'status', 2, '', '', 5, 1, 1, 'userStatus');

INSERT INTO `gw_page_operate` (`id`, `page_id`, `label`, `method`, `select`, `rank`, `form_id`, `confirm_text`)
VALUES
	(1, 1, '新建', 'userNew', 2, 1, 1, NULL),
	(2, 1, '编辑', 'userUpdate', 1, 2, 1, '确认提交修改的信息？'),
	(3, 1, '冻结/解冻', 'userFrozen', 1, 2, 0, NULL);

INSERT INTO `gw_page_header` (`id`, `page_id`, `label`, `name`, `type`, `visible`, `rank`, `target_page_id`)
VALUES
	(1, 1, 'ID', 'id', 1, 2, 1, NULL),
	(2, 1, '用户名', 'username', 1, 1, 1, NULL),
	(3, 1, '姓名', 'name', 1, 1, 2, NULL),
	(4, 1, '头像', 'avatar', 2, 1, 3, NULL),
	(5, 1, '手机号', 'mobile', 1, 1, 4, NULL),
	(6, 1, 'status', 'status', 1, 2, 1, NULL),
	(7, 1, '状态', 'statusLabel', 1, 1, 5, NULL);






