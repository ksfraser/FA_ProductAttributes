CREATE TABLE IF NOT EXISTS `0_product_cart_rules` (
  `stock_id`          VARCHAR(32) NOT NULL,
  `sold_individually` TINYINT(1)  NOT NULL DEFAULT 0,
  `updated_ts`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
