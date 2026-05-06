<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Debug utility: lists database schema names.
 */
class DebugSchemaNames
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
            $rows = $db->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES', []);
            $names = array_column($rows, 'TABLE_NAME');
            display_notification('DEBUG DebugSchemaNames [prefix=' . $p . ']: ' . implode(', ', $names));
        }
    }
}
