<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\CloneShippingToVariationsAction;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use PHPUnit\Framework\TestCase;

class CloneShippingToVariationsActionTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var CloneShippingToVariationsAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ShippingAttributesDao::class);
        $this->action = new CloneShippingToVariationsAction($this->dao);
    }

    // ── validation ────────────────────────────────────────────────────────────

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

    public function testHandleReturnErrorWhenParentHasNoShippingAttributes(): void
    {
        $this->dao->expects($this->once())->method('get')->with('PARENT')->willReturn(null);
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED'],
        ]);

        $this->assertSame('Parent product has no shipping attributes to clone', $result);
    }

    // ── successful cloning ────────────────────────────────────────────────────

    public function testHandleClonesParentShippingToEachSelectedVariation(): void
    {
        $parentShipping = [
            'stock_id'    => 'PARENT',
            'weight'      => 1.5,
            'weight_unit' => 'kg',
        ];
        $this->dao->method('get')->with('PARENT')->willReturn($parentShipping);

        $this->dao->expects($this->exactly(2))->method('upsert');

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED', 'PARENT-BLUE'],
        ]);

        $this->assertStringContainsString('2', $result);
    }

    public function testHandleStripsStockIdBeforeCloning(): void
    {
        $parentShipping = [
            'stock_id'    => 'PARENT',
            'weight'      => 1.5,
            'weight_unit' => 'kg',
        ];
        $this->dao->method('get')->willReturn($parentShipping);

        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('PARENT-RED', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-RED'],
        ]);

        $this->assertArrayNotHasKey('stock_id', $captured);
        $this->assertSame(1.5, $captured['weight']);
        $this->assertSame('kg', $captured['weight_unit']);
    }

    public function testHandleReturnsCountInMessage(): void
    {
        $parentShipping = ['stock_id' => 'PARENT', 'weight' => 1.0];
        $this->dao->method('get')->willReturn($parentShipping);

        $result = $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-S', 'PARENT-M', 'PARENT-L'],
        ]);

        $this->assertStringContainsString('3', $result);
    }

    public function testHandleIgnoresBlankVariationIds(): void
    {
        $parentShipping = ['stock_id' => 'PARENT', 'weight' => 1.0];
        $this->dao->method('get')->willReturn($parentShipping);

        // Only 'PARENT-S' is non-blank; the empty strings are filtered out
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('PARENT-S', $this->isType('array'));

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-S', '   ', ''],
        ]);
    }

    public function testHandlePassesAllNonKeyFieldsToUpsert(): void
    {
        $parentShipping = [
            'stock_id'             => 'PARENT',
            'weight'               => 2.5,
            'weight_unit'          => 'kg',
            'length'               => 30.0,
            'width'                => 20.0,
            'height'               => 10.0,
            'dim_unit'             => 'cm',
            'is_hazardous'         => 1,
            'hazmat_class'         => '3',
            'packing_group'        => 'II',
            'is_fragile'           => 0,
            'is_stackable'         => 1,
            'temperature_sensitive'=> 0,
        ];
        $this->dao->method('get')->willReturn($parentShipping);

        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('PARENT-LARGE', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle([
            'stock_id'            => 'PARENT',
            'variation_stock_ids' => ['PARENT-LARGE'],
        ]);

        $this->assertSame(2.5,  $captured['weight']);
        $this->assertSame(30.0, $captured['length']);
        $this->assertSame(1,    $captured['is_hazardous']);
        $this->assertSame('II', $captured['packing_group']);
        $this->assertArrayNotHasKey('stock_id', $captured);
    }
}
