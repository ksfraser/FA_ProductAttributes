-- Optional seed data for product attributes

-- NOTE: Replace {{prefix}} with TB_PREF (e.g. 0_) if running inside FrontAccounting.

INSERT INTO {{prefix}}product_attribute_categories (code, label, description, sort_order, active) VALUES
  ('color', 'Color', 'Primary color adjective', 10, 1),
  ('size_alpha', 'Size (alpha)', 'S/M/L/XL/XXL style sizing', 20, 1),
  ('size_numeric', 'Size (numeric)', '30/32/34/36 style sizing', 21, 1);

-- Example values
INSERT INTO {{prefix}}product_attribute_values (category_id, value, slug, sort_order, active)
SELECT c.id, 'Small', 's', 10, 1 FROM {{prefix}}product_attribute_categories c WHERE c.code = 'size_alpha';
INSERT INTO {{prefix}}product_attribute_values (category_id, value, slug, sort_order, active)
SELECT c.id, 'Medium', 'm', 20, 1 FROM {{prefix}}product_attribute_categories c WHERE c.code = 'size_alpha';
INSERT INTO {{prefix}}product_attribute_values (category_id, value, slug, sort_order, active)
SELECT c.id, 'Large', 'l', 30, 1 FROM {{prefix}}product_attribute_categories c WHERE c.code = 'size_alpha';
