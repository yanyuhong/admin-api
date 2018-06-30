
CREATE TABLE `gw_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(32) NOT NULL,
  `password` VARCHAR(32) NULL,
  `token` VARCHAR(32) NULL,
  `avatar` TEXT NULL,
  `name` VARCHAR(32) NULL,
  `mobile` VARCHAR(16) NULL,
  `created_at` DATETIME NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  UNIQUE INDEX `token_UNIQUE` (`token` ASC)
);