<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Debug utility: tests and displays DB connection info.
 */
class DebugConnection
{
    /**
     * @param DbAdapterInterface $db
     * @param int                $level  Debug verbosity level. 0 = no output.
     */
    public static function debug(DbAdapterInterface $db, int $level = 0): void
    {
        if ($level <= 0) {
            return;
        }

        if (function_exists('display_notification')) {
            $p = $db->getTablePrefix();
            $result = $db->query('SELECT 1 as connection_test', []);
            display_notification('DEBUG DebugConnection [prefix=' . $p . ']: ' . (!empty($result) ? 'OK' : 'FAILED'));
        }
    }
}
