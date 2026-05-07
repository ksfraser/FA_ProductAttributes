<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductIdentifiersDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductIdentifiersDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductIdentifiersDao($this->db);
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
        $row = ['stock_id' => 'SKU001', 'brand' => 'Acme', 'mpn' => 'ACM-001'];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->get('SKU001');

        $this->assertSame($row, $result);
    }

    // ── upsert() – INSERT path ────────────────────────────────────────────────

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

        $this->dao->upsert('SKU001', ['brand' => 'Acme']);
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

        $this->dao->upsert('SKU001', ['brand' => 'Acme']);
    }

    public function testUpsertIgnoresUnknownColumns(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->logicalNot($this->arrayHasKey('unknown_column'))
            );

        $this->dao->upsert('SKU001', ['brand' => 'Acme', 'unknown_column' => 'x']);
    }

    public function testUpsertConvertsEmptyStringFieldsToNull(): void
    {
        $this->db->method('query')->willReturn([]);

        $captured = null;
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->anything(),
                $this->callback(function ($params) use (&$captured) {
                    $captured = $params;
                    return true;
                })
            );

        $this->dao->upsert('SKU001', ['brand' => '', 'mpn' => 'X']);

        $this->assertNull($captured['brand']);
        $this->assertSame('X', $captured['mpn']);
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
