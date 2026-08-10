CREATE TABLE IF NOT EXISTS `0_product_custom_attributes` (
  `stock_id`    VARCHAR(32)  NOT NULL,
  `attr_key`    VARCHAR(64)  NOT NULL,
  `attr_value`  TEXT         NULL,
  `updated_ts`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`, `attr_key`),
  KEY `idx_stock` (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
