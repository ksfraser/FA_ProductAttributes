CREATE TABLE IF NOT EXISTS `@TB_PREF@product_media_attachments` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stock_id`    VARCHAR(32)   NOT NULL,
  `url`         VARCHAR(2048) NOT NULL,
  `description` VARCHAR(255)  NULL,
  `created_date` DATE NULL,
  `updated_ts`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_id` (`stock_id`)
);
