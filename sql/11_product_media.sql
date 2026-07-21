CREATE TABLE IF NOT EXISTS `@TB_PREF@product_media` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stock_id`    VARCHAR(32)   NOT NULL,
  `url`         VARCHAR(2048) NOT NULL,
  `alt_text`    VARCHAR(255)  NULL,
  `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
  `media_type`  ENUM('image','video','document') NOT NULL DEFAULT 'image',
  `is_primary`  TINYINT(1)    NOT NULL DEFAULT 0,
  `download_url` VARCHAR(2048) NULL,
  `updated_ts`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_id` (`stock_id`),
  KEY `idx_primary`  (`stock_id`, `is_primary`)
);
