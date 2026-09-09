CREATE TABLE IF NOT EXISTS `0_product_condition_defs` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(64)   NOT NULL,
  `label`       VARCHAR(128)  NOT NULL,
  `sort_order`  INT           NOT NULL DEFAULT 0,
  `active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `is_default`  TINYINT(1)    NOT NULL DEFAULT 0,
  `updated_ts`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
);