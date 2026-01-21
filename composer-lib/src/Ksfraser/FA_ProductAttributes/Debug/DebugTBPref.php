<?php

namespace Ksfraser\FA_ProductAttributes\Debug;

class DebugTBPref
{
    public static function debug(int $debugLevel = 1): void
    {
        if ($debugLevel < 1) {
            return;
        }

        if (defined('TB_PREF')) {
            display_notification("TB_PREF defined: " . TB_PREF);
        } else {
            display_notification("TB_PREF not defined");
        }
    }
}