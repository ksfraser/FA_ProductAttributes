<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductCustomAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductCustomAttributesDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductCustomAttributesDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductCustomAttributesDao($this->db);
    }

    // ── get() ─────────────────────────────────────────────────────────────────

    public function testGetReturnsEmptyArrayWhenNoRecords(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->get('SKU001');

        $this->assertSame([], $result);
    }

    public function testGetReturnsRows(): void
    {
        $rows = [
            ['stock_id' => 'SKU001', 'attr_key' => 'material_note', 'attr_value' => 'recycled'],
            ['stock_id' => 'SKU001', 'attr_key' => 'origin', 'attr_value' => 'CA'],
        ];
        $this->db->method('query')->willReturn($rows);

        $result = $this->dao->get('SKU001');

        $this->assertSame($rows, $result);
    }

    // ── getValue() ────────────────────────────────────────────────────────────

    public function testGetValueReturnsNullWhenMissing(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->getValue('SKU001', 'origin'));
    }

    public function testGetValueReturnsValue(): void
    {
        $this->db->method('query')->willReturn([['attr_value' => 'CA']]);

        $this->assertSame('CA', $this->dao->getValue('SKU001', 'origin'));
    }

    // ── set() ─────────────────────────────────────────────────────────────────

    public function testSetUpsertsValue(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('ON DUPLICATE KEY UPDATE'),
                ['stock_id' => 'SKU001', 'attr_key' => 'origin', 'attr_value' => 'CA']
            );

        $this->dao->set('SKU001', 'origin', 'CA');
    }

    // ── remove() / removeAll() ────────────────────────────────────────────────

    public function testRemoveDeletesSingleKey(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001', 'attr_key' => 'origin']
            );

        $this->dao->remove('SKU001', 'origin');
    }

    public function testRemoveAllDeletesForStock(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001']
            );

        $this->dao->removeAll('SKU001');
    }

    // ── sync() ────────────────────────────────────────────────────────────────

    public function testSyncDeletesThenInsertsEachAttribute(): void
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
                    ['stock_id' => 'SKU001', 'attr_key' => 'origin', 'attr_value' => 'CA'],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'SKU001', 'attr_key' => 'material_note', 'attr_value' => 'recycled'],
                ]
            );

        $this->dao->sync('SKU001', ['origin' => 'CA', 'material_note' => 'recycled']);
    }

    public function testSyncSkipsEmptyAttributeValues(): void
    {
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->stringContains('DELETE FROM'),
                    ['stock_id' => 'SKU001'],
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    ['stock_id' => 'SKU001', 'attr_key' => 'origin', 'attr_value' => 'CA'],
                ]
            );

        $this->dao->sync('SKU001', ['origin' => 'CA', 'empty_one' => '', 'null_one' => null]);
    }
}
