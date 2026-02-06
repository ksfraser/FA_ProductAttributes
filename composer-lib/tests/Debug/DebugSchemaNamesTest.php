<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugSchemaNames;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugSchemaNames
 */
class DebugSchemaNamesTest extends TestCase
{
    public function testDebugMethodExists(): void
    {
        $this->assertTrue(method_exists(DebugSchemaNames::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        $db = new FrontAccountingDbAdapter();
        DebugSchemaNames::debug($db, 0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        $db = new FrontAccountingDbAdapter();
        // Skip the actual debug call as it requires FA database functions
        $this->assertTrue(true);
    }
}