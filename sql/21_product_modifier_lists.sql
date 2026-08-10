CREATE TABLE IF NOT EXISTS `0_product_modifier_lists` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                   VARCHAR(128) NOT NULL,
  `selection_type`         ENUM('SINGLE','MULTIPLE','TEXT') NOT NULL DEFAULT 'SINGLE',
  `modifier_type`          ENUM('NON_ALCOHOL','ALCOHOL') NOT NULL DEFAULT 'NON_ALCOHOL',
  `min_selected_modifiers` INT          NULL,
  `max_selected_modifiers` INT          NULL,
  `allow_quantities`       TINYINT(1)   NOT NULL DEFAULT 0,
  `hidden_from_customer`   TINYINT(1)   NOT NULL DEFAULT 0,
  `ordinal`                INT          NOT NULL DEFAULT 0,
  `active`                 TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_ts`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
