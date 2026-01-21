<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

class DebugCompany
{
    public static function debug(int $debugLevel = 1): void
    {
        if ($debugLevel < 1) {
            return;
        }

        // Debug: current company
        if (isset($_SESSION['wa_current_user']->company)) {
            display_notification("Current company: " . $_SESSION['wa_current_user']->company);
            global $db_connections;
            if (isset($db_connections[$_SESSION['wa_current_user']->company]['name'])) {
                display_notification("DB name: " . $db_connections[$_SESSION['wa_current_user']->company]['name']);
            }
        } else {
            display_notification("Current company not set");
        }
    }
}