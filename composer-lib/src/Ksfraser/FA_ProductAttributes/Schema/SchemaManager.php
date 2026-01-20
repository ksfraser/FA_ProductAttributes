<?php

namespace Ksfraser\FA_ProductAttributes\Schema;

use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

final class SchemaManager
{
    public function ensureSchema(DbAdapterInterface $db): void
    {
        $p = $db->getTablePrefix();

        if ($db->getDialect() === 'sqlite') {
            $this->ensureSqliteSchema($db, $p);
            return;
        }

        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_categories (\n"
            . "  id INT(11) NOT NULL AUTO_INCREMENT,\n"
            . "  code VARCHAR(64) NOT NULL,\n"
            . "  label VARCHAR(64) NOT NULL,\n"
            . "  description VARCHAR(255) NULL,\n"
            . "  sort_order INT(11) NOT NULL DEFAULT 0,\n"
            . "  active TINYINT(1) NOT NULL DEFAULT 1,\n"
            . "  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
            . "  PRIMARY KEY (id),\n"
            . "  UNIQUE KEY uq_code (code)\n"
            . ");"
        );

        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_values (\n"
            . "  id INT(11) NOT NULL AUTO_INCREMENT,\n"
            . "  category_id INT(11) NOT NULL,\n"
            . "  value VARCHAR(64) NOT NULL,\n"
            . "  slug VARCHAR(32) NOT NULL,\n"
            . "  sort_order INT(11) NOT NULL DEFAULT 0,\n"
            . "  active TINYINT(1) NOT NULL DEFAULT 1,\n"
            . "  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
            . "  PRIMARY KEY (id),\n"
            . "  UNIQUE KEY uq_category_slug (category_id, slug),\n"
            . "  KEY idx_category (category_id)\n"
            . ");"
        );

        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_assignments (\n"
            . "  id INT(11) NOT NULL AUTO_INCREMENT,\n"
            . "  stock_id VARCHAR(32) NOT NULL,\n"
            . "  category_id INT(11) NOT NULL,\n"
            . "  value_id INT(11) NOT NULL,\n"
            . "  sort_order INT(11) NOT NULL DEFAULT 0,\n"
            . "  updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
            . "  PRIMARY KEY (id),\n"
            . "  UNIQUE KEY uq_stock_category_value (stock_id, category_id, value_id),\n"
            . "  KEY idx_stock (stock_id),\n"
            . "  KEY idx_category (category_id),\n"
            . "  KEY idx_value (value_id)\n"
            . ");"
        );
    }

    private function ensureSqliteSchema(DbAdapterInterface $db, string $p): void
    {
        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_categories (\n"
            . "  id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
            . "  code TEXT NOT NULL UNIQUE,\n"
            . "  label TEXT NOT NULL,\n"
            . "  description TEXT NULL,\n"
            . "  sort_order INTEGER NOT NULL DEFAULT 0,\n"
            . "  active INTEGER NOT NULL DEFAULT 1,\n"
            . "  updated_ts TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP\n"
            . ");"
        );

        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_values (\n"
            . "  id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
            . "  category_id INTEGER NOT NULL,\n"
            . "  value TEXT NOT NULL,\n"
            . "  slug TEXT NOT NULL,\n"
            . "  sort_order INTEGER NOT NULL DEFAULT 0,\n"
            . "  active INTEGER NOT NULL DEFAULT 1,\n"
            . "  updated_ts TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
            . "  UNIQUE(category_id, slug)\n"
            . ");"
        );
        $db->execute("CREATE INDEX IF NOT EXISTS idx_pav_category ON {$p}product_attribute_values(category_id);");

        $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}product_attribute_assignments (\n"
            . "  id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
            . "  stock_id TEXT NOT NULL,\n"
            . "  category_id INTEGER NOT NULL,\n"
            . "  value_id INTEGER NOT NULL,\n"
            . "  sort_order INTEGER NOT NULL DEFAULT 0,\n"
            . "  updated_ts TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
            . "  UNIQUE(stock_id, category_id, value_id)\n"
            . ");"
        );
        $db->execute("CREATE INDEX IF NOT EXISTS idx_paa_stock ON {$p}product_attribute_assignments(stock_id);");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_paa_category ON {$p}product_attribute_assignments(category_id);");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_paa_value ON {$p}product_attribute_assignments(value_id);");
    }
}
