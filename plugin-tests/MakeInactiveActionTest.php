<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes_Variations\Actions\MakeInactiveAction;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class MakeInactiveActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new MakeInactiveAction($dao, $db);
        $result = $action->handle(['stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleDeactivatesParentAndZeroStockVariations(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getProductVariations')
            ->with('PARENT-001')
            ->willReturn([
                ['stock_id' => 'PARENT-001-RED'],
                ['stock_id' => 'PARENT-001-BLUE'],
            ]);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');

        // execute called 3 times: parent + 2 variations (both at zero stock)
        $db->expects($this->exactly(3))
            ->method('execute');

        // query returns 0 qty for both variations
        $db->method('query')
            ->willReturn([['qty' => 0]]);

        $action = new MakeInactiveAction($dao, $db);
        $result = $action->handle(['stock_id' => 'PARENT-001']);

        $this->assertStringContainsString('2', $result);
    }

    public function testHandleSkipsVariationsWithStock(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getProductVariations')
            ->with('PARENT-001')
            ->willReturn([
                ['stock_id' => 'PARENT-001-RED'],
            ]);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');

        // Only parent deactivation — variation has stock
        $db->expects($this->once())
            ->method('execute');

        // query returns qty > 0 for the variation
        $db->method('query')
            ->willReturn([['qty' => 5]]);

        $action = new MakeInactiveAction($dao, $db);
        $result = $action->handle(['stock_id' => 'PARENT-001']);

        $this->assertStringContainsString('PARENT-001-RED', $result);
    }

    public function testHandleWithNoVariations(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getProductVariations')->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())->method('execute'); // only parent

        $action = new MakeInactiveAction($dao, $db);
        $result = $action->handle(['stock_id' => 'PARENT-001']);

        $this->assertIsString($result);
    }
}
