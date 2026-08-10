<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductFulfillmentDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductFulfillmentDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductFulfillmentDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductFulfillmentDao($this->db);
    }

    public function testGetReturnsNullWhenNoRecord(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->get('SKU001'));
    }

    public function testGetReturnsRow(): void
    {
        $row = ['stock_id' => 'SKU001', 'product_type' => 'SERVICE', 'service_duration_minutes' => '60'];
        $this->db->method('query')->willReturn([$row]);

        $this->assertSame($row, $this->dao->get('SKU001'));
    }

    public function testUpsertInsertsWhenNoExistingRecord(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['product_type' => 'SERVICE', 'service_duration_minutes' => 60]);
    }

    public function testUpsertUpdatesWhenRecordExists(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([['stock_id' => 'SKU001']]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['available_for_booking' => 1]);
    }

    public function testUpsertIgnoresUnknownColumns(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->logicalNot($this->stringContains('bogus_column')),
                $this->anything()
            );

        $this->dao->upsert('SKU001', ['product_type' => 'REGULAR', 'bogus_column' => 'x']);
    }

    public function testDeleteRemovesRecord(): void
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
