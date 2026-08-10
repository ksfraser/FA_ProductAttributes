<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductModifiersDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductModifiersDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductModifiersDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductModifiersDao($this->db);
    }

    // ── Modifier lists ────────────────────────────────────────────────────────

    public function testListListsReturnsRows(): void
    {
        $rows = [['id' => 1, 'name' => 'Toppings'], ['id' => 2, 'name' => 'Size']];
        $this->db->method('query')->willReturn($rows);

        $this->assertSame($rows, $this->dao->listLists());
    }

    public function testGetListReturnsRow(): void
    {
        $row = ['id' => 1, 'name' => 'Toppings'];
        $this->db->method('query')->willReturn([$row]);

        $this->assertSame($row, $this->dao->getList(1));
    }

    public function testGetListReturnsNullWhenMissing(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->getList(99));
    }

    public function testUpsertListInsertsNewList(): void
    {
        $this->db->method('lastInsertId')->willReturn(7);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) {
                    return isset($params['name']) && $params['name'] === 'Toppings'
                        && isset($params['selection_type']) && $params['selection_type'] === 'MULTIPLE';
                })
            );

        $id = $this->dao->upsertList(['name' => 'Toppings', 'selection_type' => 'MULTIPLE']);

        $this->assertSame(7, $id);
    }

    public function testUpsertListUpdatesExistingList(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('id')
            );

        $id = $this->dao->upsertList(['name' => 'Toppings'], 3);

        $this->assertSame(3, $id);
    }

    public function testUpsertListIgnoresUnknownColumns(): void
    {
        $this->db->method('lastInsertId')->willReturn(1);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->logicalNot($this->stringContains('bogus_column')),
                $this->anything()
            );

        $this->dao->upsertList(['name' => 'X', 'bogus_column' => 'y']);
    }

    public function testDeleteListRemovesAssignmentsModifiersAndList(): void
    {
        $this->db->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [$this->stringContains('DELETE FROM'), ['modifier_list_id' => 4]],
                [$this->stringContains('DELETE FROM'), ['modifier_list_id' => 4]],
                [$this->stringContains('DELETE FROM'), ['id' => 4]]
            );

        $this->dao->deleteList(4);
    }

    // ── Modifiers ─────────────────────────────────────────────────────────────

    public function testListModifiersReturnsRows(): void
    {
        $rows = [['id' => 1, 'name' => 'Extra cheese']];
        $this->db->method('query')->willReturn($rows);

        $this->assertSame($rows, $this->dao->listModifiers(4));
    }

    public function testUpsertModifierInserts(): void
    {
        $this->db->method('lastInsertId')->willReturn(9);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) {
                    return $params['modifier_list_id'] === 4 && $params['name'] === 'Extra cheese';
                })
            );

        $id = $this->dao->upsertModifier(['modifier_list_id' => 4, 'name' => 'Extra cheese']);

        $this->assertSame(9, $id);
    }

    public function testUpsertModifierUpdates(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('id')
            );

        $id = $this->dao->upsertModifier(['name' => 'Extra cheese', 'price' => 1.50], 5);

        $this->assertSame(5, $id);
    }

    public function testDeleteModifierExecutesDelete(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['id' => 5]
            );

        $this->dao->deleteModifier(5);
    }

    // ── Stock assignments ─────────────────────────────────────────────────────

    public function testGetListsForStockJoinsAssignments(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('product_modifier_lists'),
                $this->arrayHasKey('stock_id')
            )
            ->willReturn([['id' => 1, 'name' => 'Toppings', 'sort_order' => 2]]);

        $result = $this->dao->getListsForStock('SKU001');

        $this->assertCount(1, $result);
        $this->assertSame('Toppings', $result[0]['name']);
    }

    public function testAssignToListUpsertsRow(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('ON DUPLICATE KEY UPDATE'),
                ['stock_id' => 'SKU001', 'modifier_list_id' => 4, 'sort_order' => 2]
            );

        $this->dao->assignToList('SKU001', 4, 2);
    }

    public function testUnassignFromListDeletesRow(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001', 'modifier_list_id' => 4]
            );

        $this->dao->unassignFromList('SKU001', 4);
    }

    public function testSyncStockAssignmentsReplacesAll(): void
    {
        $this->db->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->stringContains('DELETE FROM'),
                    ['stock_id' => 'SKU001'],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'SKU001', 'modifier_list_id' => 1, 'sort_order' => 0],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'SKU001', 'modifier_list_id' => 2, 'sort_order' => 1],
                ]
            );

        $this->dao->syncStockAssignments('SKU001', [1, 2]);
    }
}
