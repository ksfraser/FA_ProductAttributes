CREATE TABLE IF NOT EXISTS `0_product_hierarchy` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `child_stock_id` VARCHAR(32) NOT NULL,
  `parent_stock_id` VARCHAR(32) NOT NULL,
  `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_child` (`child_stock_id`),
  KEY `idx_parent` (`parent_stock_id`)
);
