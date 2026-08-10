<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductRelatedProductsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductRelatedProductsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductRelatedProductsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductRelatedProductsDao($this->db);
    }

    // ── list() / getAll() ─────────────────────────────────────────────────────

    public function testListReturnsRows(): void
    {
        $rows = [['id' => 1, 'stock_id' => 'A', 'related_stock_id' => 'B', 'relation_type' => 'upsell']];
        $this->db->method('query')->willReturn($rows);

        $this->assertSame($rows, $this->dao->list('A', 'upsell'));
    }

    public function testGetAllReturnsAllTypes(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('WHERE stock_id = :stock_id'),
                $this->arrayHasKey('stock_id')
            )
            ->willReturn([['relation_type' => 'upsell']]);

        $result = $this->dao->getAll('A');

        $this->assertCount(1, $result);
    }

    // ── add() / remove() ──────────────────────────────────────────────────────

    public function testAddUpsertsRow(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('ON DUPLICATE KEY UPDATE'),
                $this->arrayHasKey('related_stock_id')
            );

        $this->dao->add('A', 'B', 'upsell', 1);
    }

    public function testRemoveById(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['id' => 5]
            );

        $this->dao->remove(5);
    }

    public function testRemoveByPair(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'A', 'related_stock_id' => 'B', 'relation_type' => 'upsell']
            );

        $this->dao->removeByPair('A', 'B', 'upsell');
    }

    // ── sync() ────────────────────────────────────────────────────────────────

    public function testSyncReplacesAllForType(): void
    {
        $this->db->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->stringContains('DELETE FROM'),
                    ['stock_id' => 'A', 'relation_type' => 'upsell'],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'A', 'related_stock_id' => 'B', 'relation_type' => 'upsell', 'sort_order' => 0],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'A', 'related_stock_id' => 'C', 'relation_type' => 'upsell', 'sort_order' => 1],
                ]
            );

        $this->dao->sync('A', 'upsell', ['B', 'C']);
    }
}
