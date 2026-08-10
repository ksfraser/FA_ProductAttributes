CREATE TABLE IF NOT EXISTS `0_product_measurement_units` (
  `stock_id`            VARCHAR(32) NOT NULL,
  `measurement_unit_id` VARCHAR(64) NULL,
  `updated_ts`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
