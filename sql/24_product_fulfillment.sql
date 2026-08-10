CREATE TABLE IF NOT EXISTS `0_product_fulfillment` (
  `stock_id`                VARCHAR(32) NOT NULL,
  `product_type`            ENUM('REGULAR','SERVICE') NOT NULL DEFAULT 'REGULAR',
  `service_duration_minutes` INT        NULL,
  `available_for_booking`   TINYINT(1)  NOT NULL DEFAULT 0,
  `sellable`                TINYINT(1)  NOT NULL DEFAULT 1,
  `stockable`               TINYINT(1)  NOT NULL DEFAULT 1,
  `updated_ts`              TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
