<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

/**
 * Debug utility: displays the table-name prefix in use.
 */
class DebugTBPref
{
    /**
     * @param int $level  Debug verbosity level. 0 = no output.
     */
    public static function debug(int $level = 0): void
    {
        if ($level <= 0) {
            return;
        }

        if (function_exists('display_notification')) {
            $prefix = defined('TB_PREF') ? TB_PREF : '(undefined)';
            display_notification('DEBUG DebugTBPref: prefix=' . $prefix);
        }
    }
}
