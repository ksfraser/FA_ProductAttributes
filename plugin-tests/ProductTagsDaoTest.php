<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductTagsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductTagsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductTagsDao($this->db);
    }

    // ── listTags() ────────────────────────────────────────────────────────────

    public function testListTagsReturnsEmptyArrayWhenNoTags(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->listTags();

        $this->assertSame([], $result);
    }

    public function testListTagsReturnsRows(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
            ['id' => 2, 'name' => 'New',     'slug' => 'new'],
        ];
        $this->db->method('query')->willReturn($rows);

        $result = $this->dao->listTags();

        $this->assertCount(2, $result);
        $this->assertSame('On Sale', $result[0]['name']);
    }

    // ── getTag() ──────────────────────────────────────────────────────────────

    public function testGetTagReturnsNullWhenNotFound(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->getTag(99);

        $this->assertNull($result);
    }

    public function testGetTagReturnsRow(): void
    {
        $row = ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->getTag(1);

        $this->assertSame($row, $result);
    }

    // ── upsertTag() – INSERT path ─────────────────────────────────────────────

    public function testUpsertTagInsertsWhenTagIdIsZero(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('INSERT INTO'), $this->anything());

        $this->dao->upsertTag('New Tag', 'new-tag', 0);
    }

    public function testUpsertTagUpdatesWhenTagIdIsPositive(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('UPDATE'), $this->anything());

        $this->dao->upsertTag('Updated', 'updated', 5);
    }

    // ── deleteTag() ───────────────────────────────────────────────────────────

    public function testDeleteTagDeletesAssignmentsFirst(): void
    {
        $calls = [];
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->with(
                $this->callback(function ($sql) use (&$calls) {
                    $calls[] = $sql;
                    return true;
                }),
                $this->anything()
            );

        $this->dao->deleteTag(3);

        // First call should clear assignments, second should delete the tag
        $this->assertStringContainsString('product_tag_assignments', $calls[0]);
        $this->assertStringContainsString('product_tags', $calls[1]);
    }

    // ── getProductTags() ──────────────────────────────────────────────────────

    public function testGetProductTagsReturnsEmptyArrayWhenNoAssignments(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->getProductTags('SKU001');

        $this->assertSame([], $result);
    }

    public function testGetProductTagsUsesJoinQuery(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->stringContains('JOIN'), $this->anything())
            ->willReturn([]);

        $this->dao->getProductTags('SKU001');
    }

    // ── addAssignment() ───────────────────────────────────────────────────────

    public function testAddAssignmentChecksForDuplicateFirst(): void
    {
        // Already exists → should NOT insert
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['stock_id' => 'SKU001', 'tag_id' => 1]]);

        $this->db->expects($this->never())->method('execute');

        $this->dao->addAssignment('SKU001', 1);
    }

    public function testAddAssignmentInsertsWhenNotExists(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('INSERT INTO'), $this->anything());

        $this->dao->addAssignment('SKU001', 1);
    }

    // ── removeAssignment() ────────────────────────────────────────────────────

    public function testRemoveAssignmentExecutesDelete(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->removeAssignment('SKU001', 1);
    }
}
