<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class DebugConnection
{
    public static function debug(DbAdapterInterface $db, int $debugLevel = 1): void
    {
        if ($debugLevel < 1) {
            return;
        }

        // Debug: test db connection
        $test = $db->query("SELECT 1 FROM " . $db->getTablePrefix() . "stock_master LIMIT 1");
        display_notification("Test query on FA table result count: " . count($test));
    }
}