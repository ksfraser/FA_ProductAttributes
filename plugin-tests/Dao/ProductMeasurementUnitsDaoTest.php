<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductMeasurementUnitsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductMeasurementUnitsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductMeasurementUnitsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductMeasurementUnitsDao($this->db);
    }

    public function testGetReturnsNullWhenNoMapping(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->get('SKU001'));
    }

    public function testGetReturnsRow(): void
    {
        $row = ['stock_id' => 'SKU001', 'measurement_unit_id' => 'measurement_unit:1'];
        $this->db->method('query')->willReturn([$row]);

        $this->assertSame($row, $this->dao->get('SKU001'));
    }

    public function testUpsertSetsMapping(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('ON DUPLICATE KEY UPDATE'),
                ['stock_id' => 'SKU001', 'measurement_unit_id' => 'measurement_unit:1']
            );

        $this->dao->upsert('SKU001', 'measurement_unit:1');
    }

    public function testUpsertWithNullDeletesMapping(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001']
            );

        $this->dao->upsert('SKU001', null);
    }

    public function testDeleteRemovesMapping(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001']
            );

        $this->dao->delete('SKU001');
    }
}
