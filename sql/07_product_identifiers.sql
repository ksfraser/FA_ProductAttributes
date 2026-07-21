CREATE TABLE IF NOT EXISTS `0_product_identifiers` (
  `stock_id`          VARCHAR(32)   NOT NULL,
  `brand`             VARCHAR(128)  NULL,
  `manufacturer`      VARCHAR(128)  NULL,
  `mpn`               VARCHAR(64)   NULL,
  `gtin`              VARCHAR(14)   NULL,
  `ean`               VARCHAR(13)   NULL,
  `upc`               VARCHAR(12)   NULL,
  `isbn`              VARCHAR(17)   NULL,
  `asin`              VARCHAR(16)   NULL,
  `internal_barcode`  VARCHAR(64)   NULL,
  `supplier_part_no`  VARCHAR(64)   NULL,
  `model_no`          VARCHAR(64)   NULL,
  `updated_ts`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`),
  KEY `idx_gtin` (`gtin`),
  KEY `idx_upc`  (`upc`),
  KEY `idx_ean`  (`ean`)
);
