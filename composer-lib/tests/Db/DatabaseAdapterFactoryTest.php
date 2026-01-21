<?php

namespace Ksfraser\FA_ProductAttributes\Test\Db;

use Ksfraser\FA_ProductAttributes\Db\DatabaseAdapterFactory;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DatabaseAdapterFactoryTest extends TestCase
{
    public function testCreateFaAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa');
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testCreatePdoAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('pdo');
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testCreateMysqlAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('mysql');
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testCreateInvalidDriver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DatabaseAdapterFactory::create('invalid');
    }
}