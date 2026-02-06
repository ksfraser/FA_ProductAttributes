<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DisplaySql;
use PHPUnit\Framework\TestCase;

/**
 * Test for DisplaySql
 */
class DisplaySqlTest extends TestCase
{
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
}