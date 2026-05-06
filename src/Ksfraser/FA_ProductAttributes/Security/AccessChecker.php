<?php

namespace Ksfraser\FA_ProductAttributes\Security;

/**
 * Permission / access guard for the Product Attributes plugin (NFR2).
 *
 * Wraps the FrontAccounting access-control functions so that the rest of the
 * plugin code can be tested without a live FA environment.  When the FA
 * functions are not available (e.g. in unit-tests) the behaviour falls back to
 * a configurable "test mode" flag.
 *
 * Usage:
 *   $checker = new AccessChecker();
 *   if (!$checker->canAccessAdminScreens()) { die('Access denied'); }
 */
class AccessChecker
{
    /**
     * When true every permission check returns true automatically.
     * Set via setTestMode(true) in unit tests.
     *
     * @var bool
     */
    private $testMode = false;

    /**
     * Enable / disable test mode (bypasses all real FA permission checks).
     */
    public function setTestMode(bool $enabled): void
    {
        $this->testMode = $enabled;
    }

    /**
     * Returns true when the currently logged-in user has access to the
     * product-attributes administration pages.
     *
     * In a live FA environment this delegates to `check_db_access()` and/or
     * `user_check()`.  When those functions are not available the method
     * returns false so that unauthenticated requests are always rejected.
     */
    public function canAccessAdminScreens(): bool
    {
        if ($this->testMode) {
            return true;
        }

        // Prefer FA's built-in access guard when available
        if (function_exists('check_db_access')) {
            return (bool) check_db_access();
        }

        // Fallback: if we cannot verify, deny
        return false;
    }

    /**
     * Returns true when the current user is allowed to manage product
     * variations (create, edit, deactivate).
     *
     * This maps to the FA "Inventory Manager" role or higher.  Requires that
     * the user has already passed canAccessAdminScreens().
     */
    public function canManageVariations(): bool
    {
        if ($this->testMode) {
            return true;
        }

        if (!$this->canAccessAdminScreens()) {
            return false;
        }

        // Delegate to FA's user role check when available
        if (function_exists('user_check')) {
            return (bool) user_check(/* SC_INVENTORY_MANAGEMENT */ 'IV');
        }

        return false;
    }
}
