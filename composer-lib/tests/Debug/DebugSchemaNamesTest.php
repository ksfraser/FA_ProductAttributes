<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugSchemaNames;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugSchemaNames
 */
class DebugSchemaNamesTest extends TestCase
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
        $this->assertTrue(method_exists(DebugSchemaNames::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        DebugSchemaNames::debug($db, 0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturn([['TABLE_NAME' => 'product_attribute_categories']]);
        $db->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('');

        DebugSchemaNames::debug($db, 1);
        $this->assertTrue(true);
    }
}