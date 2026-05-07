-- Product Attributes (Royal Order)
--
-- Level 1: categories (e.g. color, size_alpha, size_numeric)
-- Level 2: values (e.g. red, xl, 34)
-- Optional: assignments attach values to products

-- NOTE: In FrontAccounting, prefix with TB_PREF (e.g. 0_)

CREATE TABLE IF NOT EXISTS 0_product_attribute_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL,
  label VARCHAR(64) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_code (code)
);

CREATE TABLE IF NOT EXISTS 0_product_attribute_values (
  id INT(11) NOT NULL AUTO_INCREMENT,
  category_id INT(11) NOT NULL,
  value VARCHAR(64) NOT NULL,
  slug VARCHAR(32) NOT NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category_slug (category_id, slug),
  KEY idx_category (category_id)
);

CREATE TABLE IF NOT EXISTS 0_product_attribute_assignments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  stock_id VARCHAR(32) NOT NULL,
  category_id INT(11) NOT NULL,
  value_id INT(11) NOT NULL,
  parent_stock_id VARCHAR(32) NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stock_category_value (stock_id, category_id, value_id),
  KEY idx_stock (stock_id),
  KEY idx_category (category_id),
  KEY idx_value (value_id),
  KEY idx_parent (parent_stock_id)
);

CREATE TABLE IF NOT EXISTS 0_product_attribute_category_assignments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  stock_id VARCHAR(32) NOT NULL,
  category_id INT(11) NOT NULL,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stock_category (stock_id, category_id),
  KEY idx_stock (stock_id),
  KEY idx_category (category_id)
);

CREATE TABLE IF NOT EXISTS 0_product_hierarchy (
  id INT(11) NOT NULL AUTO_INCREMENT,
  child_stock_id VARCHAR(32) NOT NULL,
  parent_stock_id VARCHAR(32) NOT NULL,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_child (child_stock_id),
  KEY idx_parent (parent_stock_id)
);

-- ─── Shipping / Logistics Attributes ────────────────────────────────────────
-- One row per product.  Covers:
--   - Package dimensions (L×W×H) + unit (cm / in)
--   - Weight / mass + unit (kg / lb / g / oz)
--   - Hazardous-goods info (IATA / DOT / TDG / IMDG)
--   - Handling flags (fragile, stackable, oversize, perishable)
--   - Temperature requirements (min/max with unit)
--   - Customs / international trade (HS code, declared value, etc.)
CREATE TABLE IF NOT EXISTS 0_product_shipping_attributes (
  stock_id               VARCHAR(32)    NOT NULL,

  -- Package dimensions
  length                 DECIMAL(10,3)  NULL     COMMENT 'Outer length',
  width                  DECIMAL(10,3)  NULL     COMMENT 'Outer width',
  height                 DECIMAL(10,3)  NULL     COMMENT 'Outer height',
  dim_unit               ENUM('cm','in') NOT NULL DEFAULT 'cm',

  -- Weight / mass
  weight                 DECIMAL(10,3)  NULL,
  weight_unit            ENUM('kg','lb','g','oz') NOT NULL DEFAULT 'kg',

  -- Hazardous materials (UN / IATA / DOT / IMDG)
  is_hazardous           TINYINT(1)     NOT NULL DEFAULT 0,
  hazmat_class           VARCHAR(8)     NULL     COMMENT 'UN Class 1-9 (e.g. 3, 8)',
  un_number              VARCHAR(8)     NULL     COMMENT '4-digit ID without UN prefix',
  proper_shipping_name   VARCHAR(255)   NULL     COMMENT 'Official regulated name',
  packing_group          ENUM('I','II','III') NULL COMMENT 'IATA/IMDG packing group',

  -- Handling requirements
  is_fragile             TINYINT(1)     NOT NULL DEFAULT 0,
  is_stackable           TINYINT(1)     NOT NULL DEFAULT 1,
  is_oversize            TINYINT(1)     NOT NULL DEFAULT 0,
  is_perishable          TINYINT(1)     NOT NULL DEFAULT 0,

  -- Temperature-controlled shipping
  temperature_sensitive  TINYINT(1)     NOT NULL DEFAULT 0,
  temp_min               DECIMAL(5,1)   NULL     COMMENT 'Minimum storage temperature',
  temp_max               DECIMAL(5,1)   NULL     COMMENT 'Maximum storage temperature',
  temp_unit              ENUM('C','F')  NOT NULL DEFAULT 'C',

  -- Customs / international trade
  hs_code                VARCHAR(16)    NULL     COMMENT 'Harmonized System tariff code',
  country_of_origin      VARCHAR(64)    NULL,
  customs_description    VARCHAR(255)   NULL     COMMENT 'Customs item description',
  declared_value         DECIMAL(12,2)  NULL     COMMENT 'Customs declared value',

  updated_ts             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                 ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (stock_id),
  KEY idx_hazardous (is_hazardous)
);
