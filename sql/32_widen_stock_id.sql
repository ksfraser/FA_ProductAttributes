-- Widen FA core item-code columns so slug-based variation child stock_ids
-- (e.g. Americans_S1-S-11-36-Yellow) fit. FA core defaults to varchar(20)
-- (char(20) in some history/loc tables); the module's own tables already
-- use varchar(32). This is the standard FA "widen item codes" practice.
--
-- Idempotent: re-running once columns are already varchar(32) is a no-op.
-- 0_stock_master is the PK; the rest are the sibling stock references a
-- generated child can appear in (invoice code selection, stock history,
-- pricing, purchasing, order lines, work orders, BOM).
--
-- No ALGORITHM/LOCK hints: MariaDB 10.x/11.x rejects ALGORITHM=INPLACE for
-- char->varchar width changes and falls back to COPY automatically.

ALTER TABLE `0_stock_master` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_item_codes` MODIFY `stock_id` VARCHAR(32) NOT NULL;

ALTER TABLE `0_item_codes` MODIFY `item_code` VARCHAR(32) NOT NULL;

ALTER TABLE `0_loc_stock` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_stock_moves` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_prices` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_purch_data` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_sales_order_details` MODIFY `stk_code` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_supp_invoice_items` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_debtor_trans_details` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_workorders` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_wo_requirements` MODIFY `stock_id` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_grn_items` MODIFY `item_code` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_purch_order_details` MODIFY `item_code` VARCHAR(32) NOT NULL DEFAULT '';

ALTER TABLE `0_bom` MODIFY `component` VARCHAR(32) NOT NULL DEFAULT '';