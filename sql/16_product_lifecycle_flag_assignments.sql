CREATE TABLE IF NOT EXISTS `0_product_lifecycle_flag_assignments` (
  `stock_id`  VARCHAR(32)   NOT NULL,
  `flag_id`   INT UNSIGNED  NOT NULL,
  PRIMARY KEY (`stock_id`, `flag_id`),
  KEY `idx_flag_id` (`flag_id`)
);
