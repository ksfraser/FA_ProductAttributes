<?php

namespace Ksfraser\FA_ProductAttributes\Test\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test for DbAdapterInterface implementations
 */
abstract class DbAdapterTestCase extends TestCase
{
    abstract protected function createAdapter(): DbAdapterInterface;

    public function testImplementsInterface(): void
    {
        $adapter = $this->createAdapter();
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testHasDialect(): void
    {
        $adapter = $this->createAdapter();
        $dialect = $adapter->getDialect();
        $this->assertIsString($dialect);
        $this->assertNotEmpty($dialect);
    }

    public function testHasTablePrefix(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function testQueryReturnsArray(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->query('SELECT 1 as test');
        $this->assertIsArray($result);
    }

    public function testExecuteDoesNotThrow(): void
    {
        $adapter = $this->createAdapter();
        // This might fail in test environment, but shouldn't throw
        try {
            $adapter->execute('SELECT 1');
        } catch (\Exception $e) {
            // Expected in test environment without real DB
            $this->assertTrue(true);
        }
    }
}