CREATE TABLE IF NOT EXISTS `0_product_attribute_values` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `value` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(32) NOT NULL,
  `color` VARCHAR(32) NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_slug` (`category_id`, `slug`),
  KEY `idx_category` (`category_id`)
);
