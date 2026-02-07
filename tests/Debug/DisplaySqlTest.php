<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DisplaySql;
use PHPUnit\Framework\TestCase;

/**
 * Test for DisplaySql
 */
class DisplaySqlTest extends TestCase
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

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists(DisplaySql::class, 'log'));
    }

    public function testLogWithEmptyParams(): void
    {
        DisplaySql::log('SELECT 1');
        $this->assertTrue(true);
    }

    public function testLogWithParams(): void
    {
        DisplaySql::log('SELECT * FROM table WHERE id = ?', [1]);
        $this->assertTrue(true);
    }

    public function testLogWithShowSqlEnabled(): void
    {
        global $show_sql;
        $show_sql = 1;

        DisplaySql::log('SELECT * FROM table WHERE id = ?', ['id' => 1]);
        $this->assertTrue(true);

        unset($GLOBALS['show_sql']);
    }

    public function testLogWithShowSqlDisabled(): void
    {
        global $show_sql;
        $show_sql = 0;

        DisplaySql::log('SELECT * FROM table WHERE id = ?', ['id' => 1]);
        $this->assertTrue(true);

        unset($GLOBALS['show_sql']);
    }
}