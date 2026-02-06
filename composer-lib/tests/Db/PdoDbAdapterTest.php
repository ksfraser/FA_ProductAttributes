<?php

namespace Ksfraser\FA_ProductAttributes\Test\Db;

use Ksfraser\FA_ProductAttributes\Db\PdoDbAdapter;

/**
 * Test for PdoDbAdapter
 */
class PdoDbAdapterTest extends DbAdapterTestCase
{
    protected function createAdapter(): \Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface
    {
        $this->markTestSkipped('PDO adapter requires database connection not available in test environment');
        return new PdoDbAdapter();
    }

    public function testGetDialect(): void
    {
        $adapter = $this->createAdapter();
        $this->assertEquals('pdo', $adapter->getDialect());
    }

    public function testGetTablePrefix(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
        $this->assertEmpty($prefix);
    }
}