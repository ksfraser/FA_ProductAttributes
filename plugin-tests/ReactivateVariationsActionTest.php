<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Actions\ReactivateVariationsAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ReactivateVariationsActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new ReactivateVariationsAction($dao, $db);
        $result = $action->handle(['stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleReactivatesParentAndAllVariations(): void
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

        // execute: 1 for parent + 2 for variations
        $db->expects($this->exactly(3))->method('execute');

        $action = new ReactivateVariationsAction($dao, $db);
        $result = $action->handle(['stock_id' => 'PARENT-001']);

        $this->assertStringContainsString('PARENT-001', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function testHandleWithNoVariationsReactivatesOnlyParent(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getProductVariations')->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())->method('execute');

        $action = new ReactivateVariationsAction($dao, $db);
        $result = $action->handle(['stock_id' => 'PARENT-001']);

        $this->assertStringContainsString('0', $result);
    }

    public function testHandleMissingStockIdKey(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new ReactivateVariationsAction($dao, $db);
        $result = $action->handle([]);

        $this->assertStringContainsString('Invalid', $result);
    }
}
