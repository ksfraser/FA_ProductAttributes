<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductLifecycleDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductLifecycleDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductLifecycleDao($this->db);
    }

    // ── get() ─────────────────────────────────────────────────────────────────

    public function testGetReturnsNullWhenNoRecord(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->get('SKU001');

        $this->assertNull($result);
    }

    public function testGetReturnsRowWhenRecordExists(): void
    {
        $row = ['stock_id' => 'SKU001', 'status' => 'active', 'is_featured' => 1];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->get('SKU001');

        $this->assertSame($row, $result);
    }

    // ── upsert() ─────────────────────────────────────────────────────────────

    public function testUpsertExecutesInsertWhenNoExistingRecord(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['status' => 'active', 'is_featured' => 1]);
    }

    public function testUpsertExecutesUpdateWhenRecordExists(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['stock_id' => 'SKU001']]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['status' => 'discontinued']);
    }

    public function testUpsertIgnoresUnknownColumns(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->anything(),
                $this->logicalNot($this->arrayHasKey('invalid_field'))
            );

        $this->dao->upsert('SKU001', ['status' => 'active', 'invalid_field' => 'x']);
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDeleteExecutesDeleteStatement(): void
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
