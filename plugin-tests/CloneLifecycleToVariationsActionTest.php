<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\CloneLifecycleToVariationsAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use PHPUnit\Framework\TestCase;

class CloneLifecycleToVariationsActionTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var CloneLifecycleToVariationsAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductLifecycleDao::class);
        $this->action = new CloneLifecycleToVariationsAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('get');

        $result = $this->action->handle([]);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenNoVariationsSelected(): void
    {
        $result = $this->action->handle(['stock_id' => 'PARENT']);

        $this->assertSame('No variations selected', $result);
    }

    public function testHandleReturnErrorWhenParentHasNoLifecycleData(): void
    {
        $this->dao->method('get')->willReturn(null);

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED'],
        ]);

        $this->assertSame('Parent product has no lifecycle data to clone', $result);
    }

    public function testHandleClonesLifecycleToEachSelectedVariation(): void
    {
        $parentData = ['stock_id' => 'PARENT', 'status' => 'active', 'is_featured' => 1];

        $this->dao->method('get')->willReturn($parentData);

        $this->dao->expects($this->exactly(2))
            ->method('upsert')
            ->withConsecutive(
                ['PARENT-RED',  $this->anything()],
                ['PARENT-BLUE', $this->anything()]
            );

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED', 'PARENT-BLUE'],
        ]);

        $this->assertStringContainsString('2', $result);
    }

    public function testHandleStripsStockIdBeforeCloning(): void
    {
        $this->dao->method('get')->willReturn(['stock_id' => 'PARENT', 'status' => 'draft']);

        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('VAR1', $this->logicalNot($this->arrayHasKey('stock_id')));

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['VAR1'],
        ]);
    }
}
