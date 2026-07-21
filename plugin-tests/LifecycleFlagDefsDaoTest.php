<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class LifecycleFlagDefsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var LifecycleFlagDefsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->dao = new LifecycleFlagDefsDao($this->db);
    }

    public function testListFlagsQueriesAllFlags(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `0_product_lifecycle_flag_defs` ORDER BY sort_order ASC, id ASC')
            ->willReturn([]);

        $this->assertSame([], $this->dao->listFlags());
    }

    public function testListActiveFlagsQueriesActiveOnly(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->stringContains('WHERE active = 1'))
            ->willReturn([]);

        $this->dao->listActiveFlags();
    }

    public function testGetFlagReturnsNullWhenNotFound(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $this->assertNull($this->dao->getFlag(999));
    }

    public function testGetFlagReturnsRow(): void
    {
        $row = ['id' => 1, 'code' => 'featured', 'label' => 'Featured'];
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([$row]);

        $this->assertSame($row, $this->dao->getFlag(1));
    }

    public function testUpsertFlagReturnsZeroWhenCodeMissing(): void
    {
        $this->db->expects($this->never())->method('query');
        $this->assertSame(0, $this->dao->upsertFlag(['label' => 'Test']));
    }

    public function testUpsertFlagReturnsZeroWhenLabelMissing(): void
    {
        $this->db->expects($this->never())->method('query');
        $this->assertSame(0, $this->dao->upsertFlag(['code' => 'test']));
    }

    public function testUpsertFlagInsertsNewFlag(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('INSERT INTO'));
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(5);

        $id = $this->dao->upsertFlag(['code' => 'new_flag', 'label' => 'New Flag']);
        $this->assertSame(5, $id);
    }

    public function testUpsertFlagUpdatesExistingFlag(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['id' => 1]]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('UPDATE'));

        $id = $this->dao->upsertFlag(['code' => 'existing', 'label' => 'Existing Flag']);
        $this->assertSame(1, $id);
    }

    public function testDeleteFlagDeletesAssignmentsThenFlag(): void
    {
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->stringContains('DELETE FROM `0_product_lifecycle_flag_assignments`'), $this->anything()],
                [$this->stringContains('DELETE FROM `0_product_lifecycle_flag_defs`'), $this->anything()]
            );

        $this->dao->deleteFlag(3);
    }

    public function testGetAssignedFlagIdsReturnsIds(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['flag_id' => 1], ['flag_id' => 2]]);

        $this->assertSame([1, 2], $this->dao->getAssignedFlagIds('SKU001'));
    }

    public function testGetAssignedFlagIdsReturnsEmpty(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->assertSame([], $this->dao->getAssignedFlagIds('SKU001'));
    }

    public function testSetAssignedFlagsDeletesThenInserts(): void
    {
        $this->db->expects($this->exactly(3))
            ->method('execute');

        $this->dao->setAssignedFlags('SKU001', [1, 2]);
    }

    public function testSetAssignedFlagsSkipsZeroIds(): void
    {
        $this->db->expects($this->exactly(3))
            ->method('execute');

        $this->dao->setAssignedFlags('SKU001', [1, 0, 2, -1]);
    }

    public function testDeleteAssignmentsDeletesAllForProduct(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE'),
                $this->callback(function ($p) {
                    return $p['stock_id'] === 'SKU001';
                })
            );

        $this->dao->deleteAssignments('SKU001');
    }
}
