<?php

namespace Ksfraser\FA_ProductAttributes\Test\Security;

use Ksfraser\FA_ProductAttributes\Security\AccessChecker;
use PHPUnit\Framework\TestCase;

class AccessCheckerTest extends TestCase
{
    public function testCanAccessAdminScreensReturnsTrueInTestMode(): void
    {
        $checker = new AccessChecker();
        $checker->setTestMode(true);

        $this->assertTrue($checker->canAccessAdminScreens());
    }

    public function testCanManageVariationsReturnsTrueInTestMode(): void
    {
        $checker = new AccessChecker();
        $checker->setTestMode(true);

        $this->assertTrue($checker->canManageVariations());
    }

    public function testCanAccessAdminScreensReturnsFalseWhenFAFunctionsUnavailable(): void
    {
        // Ensure FA functions are NOT present in unit-test environment
        $this->assertFalse(function_exists('check_db_access'), 'FA should not be loaded in unit tests');

        $checker = new AccessChecker();
        // testMode is false by default
        $this->assertFalse($checker->canAccessAdminScreens());
    }

    public function testCanManageVariationsReturnsFalseWhenAdminAccessDenied(): void
    {
        $this->assertFalse(function_exists('check_db_access'), 'FA should not be loaded in unit tests');

        $checker = new AccessChecker();
        $this->assertFalse($checker->canManageVariations());
    }

    public function testSetTestModeToFalseRestoresRealBehaviour(): void
    {
        $checker = new AccessChecker();
        $checker->setTestMode(true);
        $this->assertTrue($checker->canAccessAdminScreens());

        $checker->setTestMode(false);
        // Real FA functions absent → should deny
        $this->assertFalse($checker->canAccessAdminScreens());
    }
}
