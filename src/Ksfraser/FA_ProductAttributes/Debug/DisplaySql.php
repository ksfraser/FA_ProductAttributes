<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

/**
 * Debug utility: logs SQL statements via display_notification when enabled.
 */
class DisplaySql
{
    /** @var bool */
    private static $enabled = false;

    /**
     * Enable or disable SQL logging.
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Log an SQL statement with optional parameter substitution.
     *
     * @param string               $sql
     * @param array<mixed, mixed>  $params
     */
    public static function log(string $sql, array $params = []): void
    {
        if (!self::$enabled) {
            return;
        }

        if (function_exists('display_notification')) {
            $display = $sql;
            foreach ($params as $key => $value) {
                $display .= ' [' . $key . '=' . $value . ']';
            }
            display_notification('SQL: ' . $display);
        }
    }
}
