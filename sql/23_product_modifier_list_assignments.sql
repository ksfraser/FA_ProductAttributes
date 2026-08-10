CREATE TABLE IF NOT EXISTS `0_product_modifier_list_assignments` (
  `stock_id`          VARCHAR(32) NOT NULL,
  `modifier_list_id`  INT UNSIGNED NOT NULL,
  `sort_order`        INT         NOT NULL DEFAULT 0,
  `updated_ts`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`, `modifier_list_id`),
  KEY `idx_list` (`modifier_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
