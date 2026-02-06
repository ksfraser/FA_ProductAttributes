<?php

namespace Ksfraser\FA_ProductAttributes\Test\Db;

use Ksfraser\FA_ProductAttributes\Db\MysqlDbAdapter;

/**
 * Test for MysqlDbAdapter
 */
class MysqlDbAdapterTest extends DbAdapterTestCase
{
    protected function createAdapter(): \Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface
    {
        $this->markTestSkipped('MySQL adapter requires database connection not available in test environment');
        return new MysqlDbAdapter();
    }

    public function testGetDialect(): void
    {
        $adapter = $this->createAdapter();
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testGetTablePrefix(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
        $this->assertEmpty($prefix);
    }
}