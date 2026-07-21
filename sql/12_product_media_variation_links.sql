CREATE TABLE IF NOT EXISTS `@TB_PREF@product_media_variation_links` (
  `media_id`            INT UNSIGNED  NOT NULL,
  `variation_stock_id`  VARCHAR(32)   NOT NULL,
  PRIMARY KEY (`media_id`, `variation_stock_id`),
  KEY `idx_variation` (`variation_stock_id`)
);
