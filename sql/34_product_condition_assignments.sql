CREATE TABLE IF NOT EXISTS `0_product_condition_assignments` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stock_id`     VARCHAR(32)   NOT NULL,
  `condition_id` INT UNSIGNED  NOT NULL,
  `updated_ts`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_condition` (`stock_id`),
  KEY `idx_condition` (`condition_id`)
);