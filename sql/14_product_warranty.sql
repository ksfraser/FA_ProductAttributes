CREATE TABLE IF NOT EXISTS `0_product_warranty` (
  `stock_id`                    VARCHAR(32)   NOT NULL,
  `warranty_type`               ENUM('none','manufacturer','extended','third_party','lifetime')
                                               NOT NULL DEFAULT 'none',
  `manufacturer_duration`       INT           NULL,
  `manufacturer_duration_unit`  ENUM('days','months','years') NOT NULL DEFAULT 'months',
  `extended_duration`           INT           NULL,
  `extended_duration_unit`      ENUM('days','months','years') NOT NULL DEFAULT 'months',
  `third_party_duration`        INT           NULL,
  `third_party_duration_unit`   ENUM('days','months','years') NOT NULL DEFAULT 'months',
  `lifetime_notes`              VARCHAR(255)  NULL,
  `warranty_notes`              TEXT          NULL,
  `updated_ts`                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`)
);
