CREATE TABLE IF NOT EXISTS `@TB_PREF@product_tag_assignments` (
  `stock_id`  VARCHAR(32)   NOT NULL,
  `tag_id`    INT UNSIGNED  NOT NULL,
  PRIMARY KEY (`stock_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`)
);
