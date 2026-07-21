CREATE TABLE IF NOT EXISTS `@TB_PREF@product_attribute_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(64) NOT NULL,
  `label` VARCHAR(64) NOT NULL,
  `description` VARCHAR(255) NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
);
