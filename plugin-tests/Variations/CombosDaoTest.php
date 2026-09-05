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

    /** @var array<int, array<string,mixed>> Captured INSERT params during tests. */
    private $insertParams = [];

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new CombosDao($this->db);
    }

    public function testSyncCombosInsertsOnlyNew(): void
    {
        $this->db->method('query')->willReturn([]); // none exist yet

        $this->insertParams = [];
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($sql, $params) {
                $this->insertParams[] = $params;
                return null;
            });

        $added = $this->dao->syncCombos('SHIRT', [
            [
                'value_set_key' => '10,20',
                'slug_key' => 'red-m',
                'value_set' => [
                    ['category_id' => 1, 'value_id' => 10, 'slug' => 'red'],
                    ['category_id' => 2, 'value_id' => 20, 'slug' => 'm'],
                ],
            ],
            [
                'value_set_key' => '11,20',
                'slug_key' => 'blue-m',
                'value_set' => [
                    ['category_id' => 1, 'value_id' => 11, 'slug' => 'blue'],
                    ['category_id' => 2, 'value_id' => 20, 'slug' => 'm'],
                ],
            ],
        ]);

        $this->assertSame(2, $added);
        // The per-value combo is persisted (JSON) so Create Child can record it.
        $this->assertCount(2, $this->insertParams);
        $vs = $this->insertParams[0]['vs'];
        $this->assertNotNull($vs);
        $this->assertStringContainsString('"category_id"', (string)$vs);
        $this->assertStringContainsString('"value_id":10', (string)$vs);
    }

    public function testListCombosDecodesValueSet(): void
    {
        $this->db->method('query')->willReturn([[
            'id' => 1,
            'parent_stock_id' => 'SHIRT',
            'value_set_key' => '10,20',
            'slug_key' => 'red-m',
            'value_set' => '[{"category_id":1,"value_id":10,"slug":"red"},{"category_id":2,"value_id":20,"slug":"m"}]',
            'child_stock_id' => null,
        ]]);

        $combos = $this->dao->listCombos('SHIRT');

        $this->assertSame(1, count($combos));
        $this->assertSame(2, count($combos[0]['value_set']));
        $this->assertSame(10, (int)$combos[0]['value_set'][0]['value_id']);

        // HTML-escaped legacy payloads (e.g. &quot; entities) still decode.
        $escaped = $this->createMock(DbAdapterInterface::class);
        $escaped->method('getTablePrefix')->willReturn('fa_');
        $escaped->method('query')->willReturn([
            ['id' => 3, 'value_set_key' => '10,20', 'slug_key' => 'red-m', 'child_stock_id' => null,
             'value_set' => '[{&quot;category_id&quot;:1,&quot;value_id&quot;:10,&quot;slug&quot;:&quot;red&quot;}]'],
        ]);
        $escapedCombos = (new CombosDao($escaped))->listCombos('SHIRT');
        $this->assertSame(1, count($escapedCombos[0]['value_set']));
        $this->assertSame(1, (int)$escapedCombos[0]['value_set'][0]['category_id']);
        $this->assertSame(10, (int)$escapedCombos[0]['value_set'][0]['value_id']);

        // A null/blank value_set decodes to an empty array rather than erroring.
        $blank = $this->createMock(DbAdapterInterface::class);
        $blank->method('getTablePrefix')->willReturn('fa_');
        $blank->method('query')->willReturn([
            ['id' => 2, 'value_set_key' => '1', 'slug_key' => 'x', 'value_set' => null, 'child_stock_id' => null],
        ]);
        $blankCombos = (new CombosDao($blank))->listCombos('SHIRT');
        $this->assertSame([], $blankCombos[0]['value_set']);
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