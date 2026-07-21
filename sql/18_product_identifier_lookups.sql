CREATE TABLE IF NOT EXISTS `{TB_PREF}product_identifier_lookups` (
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type`        varchar(32) NOT NULL COMMENT 'brand or manufacturer',
    `name`        varchar(128) NOT NULL,
    `updated_ts`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_name` (`type`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
