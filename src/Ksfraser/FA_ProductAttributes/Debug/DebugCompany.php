<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

/**
 * Debug utility: dumps current-company info via display_notification.
 */
class DebugCompany
{
    /**
     * @param int $level  Debug verbosity level. 0 = no output.
     */
    public static function debug(int $level = 0): void
    {
        if ($level <= 0) {
            return;
        }

        global $_SESSION;

        if (function_exists('display_notification')) {
            $company = isset($_SESSION['wa_current_user']) ? (int)$_SESSION['wa_current_user']->company : 'n/a';
            display_notification('DEBUG DebugCompany: company=' . $company);
        }
    }
}
