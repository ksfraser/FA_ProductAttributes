<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugCompany;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugCompany
 */
class DebugCompanyTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock the display_notification function
        if (!function_exists('display_notification')) {
            function display_notification($message) {
                // Mock implementation - do nothing
            }
        }
    }

    public function testDebugMethodExists(): void
    {
        $this->assertTrue(method_exists(DebugCompany::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        // This should not throw any exceptions
        DebugCompany::debug(0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        // Mock session data
        global $_SESSION;
        $_SESSION['wa_current_user'] = (object)['company' => 0];

        // Mock db_connections
        global $db_connections;
        $db_connections = [
            0 => ['name' => 'test_db']
        ];

        // This should not throw any exceptions
        DebugCompany::debug(1);
        $this->assertTrue(true);
    }

    public function testDebugWithoutSession(): void
    {
        // Ensure no session is set
        unset($GLOBALS['_SESSION']);

        // This should not throw any exceptions
        DebugCompany::debug(1);
        $this->assertTrue(true);
    }
}