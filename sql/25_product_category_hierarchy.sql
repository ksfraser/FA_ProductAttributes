CREATE TABLE IF NOT EXISTS `0_product_category_hierarchy` (
  `category_id`         INT(11) NOT NULL,
  `parent_category_id`  INT(11) NULL,
  `updated_ts`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  KEY `idx_parent` (`parent_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
