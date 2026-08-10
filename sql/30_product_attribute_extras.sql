-- Upgrade path: add columns introduced in Stage 3 to tables created by earlier
-- schema files. Applies once on existing installations; fresh installs get the
-- columns directly from the base schema files (02/03/06).

ALTER TABLE `0_product_attribute_values` ADD COLUMN `color` VARCHAR(32) NULL AFTER `slug`;
ALTER TABLE `0_product_attribute_assignments` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`;
ALTER TABLE `0_product_shipping_attributes` ADD COLUMN `shipping_class_id` INT(11) NULL;
