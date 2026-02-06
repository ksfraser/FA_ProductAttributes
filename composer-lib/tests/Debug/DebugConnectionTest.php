<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugConnection;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugConnection
 */
class DebugConnectionTest extends TestCase
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
        $this->assertTrue(method_exists(DebugConnection::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        DebugConnection::debug($db, 0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->willReturn([['test' => 1]]);
        $db->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('');

        DebugConnection::debug($db, 1);
        $this->assertTrue(true);
    }
}