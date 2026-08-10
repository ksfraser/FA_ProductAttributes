CREATE TABLE IF NOT EXISTS `0_product_related_products` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stock_id`         VARCHAR(32)   NOT NULL,
  `related_stock_id` VARCHAR(32)   NOT NULL,
  `relation_type`    ENUM('upsell','cross_sell') NOT NULL,
  `sort_order`       INT           NOT NULL DEFAULT 0,
  `updated_ts`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_related_type` (`stock_id`, `related_stock_id`, `relation_type`),
  KEY `idx_stock` (`stock_id`),
  KEY `idx_related` (`related_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
