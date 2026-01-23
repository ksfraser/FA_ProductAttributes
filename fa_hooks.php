<?php

// Global Hook Manager for FrontAccounting Module Extensions
// This file should be included early in FA's bootstrap process

// Load fa-hooks autoloader if available
$faHooksAutoload = dirname(__DIR__) . '/modules/fa-hooks/vendor/autoload.php';
if (file_exists($faHooksAutoload)) {
    require_once $faHooksAutoload;
}

// Initialize global hook manager if not already done
if (!isset($GLOBALS['fa_hooks'])) {
    // Try to load via composer autoloader first
    if (class_exists('\Ksfraser\FA_Hooks\HookManager')) {
        $GLOBALS['fa_hooks'] = new \Ksfraser\FA_Hooks\HookManager();
    } else {
        // Fallback: direct require (for development)
        $hookManagerPath = __DIR__ . '/fa-hooks/src/Ksfraser/FA_Hooks/HookManager.php';
        if (file_exists($hookManagerPath)) {
            require_once $hookManagerPath;
            $GLOBALS['fa_hooks'] = new \Ksfraser\FA_Hooks\HookManager();
        } else {
            // Last resort: old location
            $oldPath = __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Hooks/HookManager.php';
            if (file_exists($oldPath)) {
                require_once $oldPath;
                $GLOBALS['fa_hooks'] = new \Ksfraser\FA_ProductAttributes\Hooks\HookManager();
            }
        }
    }
}

/**
 * Get the global hook manager instance
 *
 * @return \Ksfraser\FA_Hooks\HookManager|\Ksfraser\FA_ProductAttributes\Hooks\HookManager
 */
function fa_hooks() {
    return $GLOBALS['fa_hooks'];
}