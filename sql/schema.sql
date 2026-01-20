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
  sort_order INT(11) NOT NULL DEFAULT 0,
  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stock_category_value (stock_id, category_id, value_id),
  KEY idx_stock (stock_id),
  KEY idx_category (category_id),
  KEY idx_value (value_id)
);
