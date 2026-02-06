<?php

namespace Ksfraser\FA_ProductAttributes\Test\Db;

use Ksfraser\FA_ProductAttributes\Db\DatabaseAdapterFactory;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Db\PdoDbAdapter;
use Ksfraser\FA_ProductAttributes\Db\MysqlDbAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Test for DatabaseAdapterFactory
 */
class DatabaseAdapterFactoryTest extends TestCase
{
    public function testCreateFaAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateFaAdapterWithPrefix(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa', 'custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreatePdoAdapter(): void
    {
        $this->markTestSkipped('PDO adapter requires database connection not available in test environment');
    }

    public function testCreateMysqlAdapter(): void
    {
        $this->markTestSkipped('MySQL adapter requires database connection not available in test environment');
    }

    public function testCreateDefaultAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create();
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateUnknownDriverThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown database driver: unknown');
        DatabaseAdapterFactory::create('unknown');
    }
}