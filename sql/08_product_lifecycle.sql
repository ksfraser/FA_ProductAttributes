CREATE TABLE IF NOT EXISTS `0_product_lifecycle` (
  `stock_id`                VARCHAR(32)   NOT NULL,
  `status`                  ENUM('active','draft','discontinued','archived')
                                          NOT NULL DEFAULT 'active',
  `is_special_order`        TINYINT(1)    NOT NULL DEFAULT 0,
  `is_clearance`            TINYINT(1)    NOT NULL DEFAULT 0,
  `is_out_of_stock_notice`  TINYINT(1)    NOT NULL DEFAULT 0,
  `is_new_arrival`          TINYINT(1)    NOT NULL DEFAULT 0,
  `is_bestseller`           TINYINT(1)    NOT NULL DEFAULT 0,
  `is_featured`             TINYINT(1)    NOT NULL DEFAULT 0,
  `is_seasonal`             TINYINT(1)    NOT NULL DEFAULT 0,
  `available_from`          DATE          NULL,
  `discontinue_on`          DATE          NULL,
  `clearance_note`          VARCHAR(255)  NULL,
  `updated_ts`              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`),
  KEY `idx_status`   (`status`),
  KEY `idx_featured` (`is_featured`)
);
