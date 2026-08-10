CREATE TABLE IF NOT EXISTS `0_product_modifiers` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `modifier_list_id`  INT UNSIGNED  NOT NULL,
  `name`              VARCHAR(128)  NOT NULL,
  `price`             DECIMAL(12,2) NULL,
  `on_by_default`     TINYINT(1)    NOT NULL DEFAULT 0,
  `ordinal`           INT           NOT NULL DEFAULT 0,
  `kitchen_name`      VARCHAR(255)  NULL,
  `hidden_online`     TINYINT(1)    NOT NULL DEFAULT 0,
  `active`            TINYINT(1)    NOT NULL DEFAULT 1,
  `updated_ts`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_list` (`modifier_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
