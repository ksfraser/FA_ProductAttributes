CREATE TABLE IF NOT EXISTS `@TB_PREF@product_attribute_category_assignments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `stock_id` VARCHAR(32) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_category` (`stock_id`, `category_id`),
  KEY `idx_stock` (`stock_id`),
  KEY `idx_category` (`category_id`)
);
