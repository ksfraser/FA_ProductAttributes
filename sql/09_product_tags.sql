CREATE TABLE IF NOT EXISTS `@TB_PREF@product_tags` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(128)  NOT NULL,
  `slug`        VARCHAR(128)  NOT NULL,
  `updated_ts`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
);
