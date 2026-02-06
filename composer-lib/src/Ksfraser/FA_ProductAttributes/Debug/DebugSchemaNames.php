<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class DebugSchemaNames
{
    public static function debug(DbAdapterInterface $db, int $debugLevel = 1): void
    {
        if ($debugLevel < 1) {
            return;
        }

        // Debug: check if tables exist
        $query = "SELECT TABLE_NAME FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(DATABASE()) AND table_name LIKE '" . $db->getTablePrefix() . "product_attribute_%'";
        $tables = $db->query($query);
        $count = count($tables);
        $expected = 3; // categories, values, assignments

        if ($count < $expected) {
            display_notification("WARNING: Only $count product attribute tables found (expected $expected). Schema may be incomplete.");
        } elseif ($count > $expected) {
            display_error("ERROR: $count product attribute tables found (expected $expected). Unexpected tables detected.");
        }
        // If count == expected, display nothing
    }
}