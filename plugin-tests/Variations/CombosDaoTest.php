<?php

use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class CombosDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var CombosDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new CombosDao($this->db);
    }

    public function testSyncCombosInsertsOnlyNew(): void
    {
        $this->db->method('query')->willReturn([]); // none exist yet

        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($sql, $params) { return null; });

        $added = $this->dao->syncCombos('SHIRT', [
            ['value_set_key' => '10,20', 'slug_key' => 'red-m'],
            ['value_set_key' => '11,20', 'slug_key' => 'blue-m'],
        ]);

        $this->assertSame(2, $added);
    }

    public function testSyncCombosSkipsExisting(): void
    {
        $this->db->method('query')->willReturn([['id' => 5]]);

        // No inserts should fire; a duplicate value_set_key is present.
        $this->db->expects($this->never())->method('execute');

        $added = $this->dao->syncCombos('SHIRT', [
            ['value_set_key' => '10,20', 'slug_key' => 'red-m'],
        ]);

        $this->assertSame(0, $added);
    }

    public function testListPoolChildStockIdsFiltersBlanks(): void
    {
        $this->db->method('query')->willReturn([
            ['child_stock_id' => 'SHIRT-red'],
            ['child_stock_id' => ''],
            ['child_stock_id' => null],
        ]);

        $ids = $this->dao->listPoolChildStockIds('SHIRT');

        $this->assertSame(['SHIRT-red'], $ids);
    }

    public function testListChildrenByParent(): void
    {
        $this->db->method('query')->willReturn([
            ['child_stock_id' => 'SHIRT-red'],
            ['child_stock_id' => 'SHIRT-blue'],
        ]);

        $this->assertSame(['SHIRT-red', 'SHIRT-blue'], $this->dao->listChildrenByParent('SHIRT'));
    }

    public function testChildHasHistory(): void
    {
        $this->db->method('query')->willReturn([['1' => 1]]);
        $this->assertTrue($this->dao->childHasHistory('SHIRT-red'));

        $nonHist = $this->createMock(DbAdapterInterface::class);
        $nonHist->method('getTablePrefix')->willReturn('fa_');
        $nonHist->method('query')->willReturn([]);
        $this->assertFalse((new CombosDao($nonHist))->childHasHistory('SHIRT-new'));
    }

    public function testChildQtyOnHandSumsMoves(): void
    {
        $this->db->method('query')
            ->willReturn([['qty' => '3.500000']]);

        $this->assertSame(3.5, $this->dao->childQtyOnHand('SHIRT-red'));
    }

    public function testRemoveChildCleansAllReferences(): void
    {
        $this->db->expects($this->exactly(4))
            ->method('execute')
            ->willReturnCallback(function ($sql) { return null; });

        $this->dao->removeChild('SHIRT-red');
    }
}