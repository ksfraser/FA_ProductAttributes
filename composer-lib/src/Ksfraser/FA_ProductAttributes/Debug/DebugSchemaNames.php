<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class DebugSchemaNames
{
    public static function debug(DbAdapterInterface $db, int $debugLevel = 1): void
    {
        if ($debugLevel < 1) {
            return;
        }

        // Debug: check if tables exist
        $query = "SELECT TABLE_NAME FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(DATABASE()) AND table_name LIKE '" . $db->getTablePrefix() . "product_attribute_%'";
        display_notification("Query: " . $query);
        $tables = $db->query($query);
        display_notification("Product attribute tables found: " . count($tables));
    }
}