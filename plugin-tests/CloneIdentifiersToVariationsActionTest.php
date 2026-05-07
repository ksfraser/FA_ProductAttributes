<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\CloneIdentifiersToVariationsAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use PHPUnit\Framework\TestCase;

class CloneIdentifiersToVariationsActionTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var CloneIdentifiersToVariationsAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductIdentifiersDao::class);
        $this->action = new CloneIdentifiersToVariationsAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('get');

        $result = $this->action->handle([]);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenStockIdBlank(): void
    {
        $this->dao->expects($this->never())->method('get');

        $result = $this->action->handle(['stock_id' => '   ']);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenNoVariationsSelected(): void
    {
        $this->dao->expects($this->never())->method('get');

        $result = $this->action->handle(['stock_id' => 'PARENT']);

        $this->assertSame('No variations selected', $result);
    }

    public function testHandleReturnErrorWhenVariationsArrayIsEmpty(): void
    {
        $this->dao->expects($this->never())->method('get');

        $result = $this->action->handle(['stock_id' => 'PARENT', 'variation_stock_ids' => []]);

        $this->assertSame('No variations selected', $result);
    }

    public function testHandleReturnErrorWhenParentHasNoIdentifiers(): void
    {
        $this->dao->expects($this->once())->method('get')->with('PARENT')->willReturn(null);
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED'],
        ]);

        $this->assertSame('Parent product has no identifier data to clone', $result);
    }

    public function testHandleClonesParentIdentifiersToEachSelectedVariation(): void
    {
        $parentData = ['stock_id' => 'PARENT', 'brand' => 'Acme', 'mpn' => 'X1'];

        $this->dao->expects($this->once())->method('get')->with('PARENT')->willReturn($parentData);

        $this->dao->expects($this->exactly(2))
            ->method('upsert')
            ->withConsecutive(
                ['PARENT-RED',  $this->logicalNot($this->arrayHasKey('stock_id'))],
                ['PARENT-BLUE', $this->logicalNot($this->arrayHasKey('stock_id'))]
            );

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED', 'PARENT-BLUE'],
        ]);

        $this->assertStringContainsString('2', $result);
    }

    public function testHandleStripsStockIdBeforeCloning(): void
    {
        $parentData = ['stock_id' => 'PARENT', 'brand' => 'Acme'];

        $this->dao->method('get')->willReturn($parentData);

        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('PARENT-RED', $this->logicalNot($this->arrayHasKey('stock_id')));

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED'],
        ]);
    }

    public function testHandleIgnoresBlankVariationIds(): void
    {
        $parentData = ['stock_id' => 'PARENT', 'brand' => 'Acme'];
        $this->dao->method('get')->willReturn($parentData);

        $this->dao->expects($this->once())->method('upsert');

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED', '   ', ''],
        ]);
    }
}
