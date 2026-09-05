-- Persisted cartesian combination pool for product variations (FR-9.12..9.16, #60).
--
-- Gen Combos ("Generate Combinations", renamed from "Generate Variations") persists
-- the cartesian product of a parent product's assigned category values here. It is
-- written ONLY on the explicit "Generate Combinations" action - never auto-rewritten
-- when a parent's categories/values change.
--
-- Gen Child instantiates each combo (child_stock_id NULL) into a stock_master child
-- and stamps child_stock_id, and reconciles this parent's children against the pool
-- (delete no-history orphans / inactive history-no-stock / discontinued history-stock).

CREATE TABLE IF NOT EXISTS `0_product_variation_combos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_stock_id` VARCHAR(32) NOT NULL,
  `value_set_key` VARCHAR(255) NOT NULL COMMENT 'order-independent comma-joined value_ids for dedupe',
  `slug_key` VARCHAR(255) NOT NULL COMMENT 'Royal Order dash-joined slug chain; child stock_id suffix',
  `value_set` TEXT NULL COMMENT 'JSON array of {category_id, value_id, slug} so Create Child can record the child value assignments',
  `child_stock_id` VARCHAR(32) NULL DEFAULT NULL COMMENT 'filled when Gen Child instantiates this combo',
  `created_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parent_value_set` (`parent_stock_id`, `value_set_key`),
  KEY `idx_parent` (`parent_stock_id`),
  KEY `idx_child` (`child_stock_id`)
);
