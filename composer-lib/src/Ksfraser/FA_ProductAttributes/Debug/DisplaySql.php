<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

class DisplaySql
{
    public static function log(string $sql, array $params = []): void
    {
        global $show_sql;

        if (!isset($show_sql)) {
            display_notification("show_sql is not set");
            return;
        }

        if ($show_sql > 0) {
            $paramStr = empty($params) ? '' : ' [' . implode(', ', array_map(function($k, $v) { return "$k=$v"; }, array_keys($params), array_values($params))) . ']';
            display_notification("SQL: " . $sql . $paramStr);
        }
    }
}