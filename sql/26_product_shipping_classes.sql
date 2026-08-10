CREATE TABLE IF NOT EXISTS `0_product_shipping_classes` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(128)  NOT NULL,
  `slug`         VARCHAR(128)  NOT NULL,
  `description`  VARCHAR(255)  NULL,
  `sort_order`   INT           NOT NULL DEFAULT 0,
  `active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `updated_ts`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
