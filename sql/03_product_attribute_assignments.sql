CREATE TABLE IF NOT EXISTS `0_product_attribute_assignments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `stock_id` VARCHAR(32) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `value_id` INT(11) NOT NULL,
  `parent_stock_id` VARCHAR(32) NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_category_value` (`stock_id`, `category_id`, `value_id`),
  KEY `idx_stock` (`stock_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_value` (`value_id`),
  KEY `idx_parent` (`parent_stock_id`)
);
