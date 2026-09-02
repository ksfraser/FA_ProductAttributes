<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\GenerateChildAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class GenerateChildActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $coreDao       = $this->createMock(ProductAttributesDao::class);
        $combosDao     = $this->createMock(CombosDao::class);
        $db            = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleWithNoCombosInstructsToGenerateFirst(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')->willReturn([]);
        $combosDao->method('listPoolChildStockIds')->willReturn([]);
        $combosDao->method('listChildrenByParent')->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('Generate Combinations', $result);
        $this->assertStringContainsString(' first', $result);
    }

    public function testHandleCreatesUninstantiatedCombosAndStampsPool(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->expects($this->once())
            ->method('setProductParent')
            ->with('SHIRT-red-m', 'SHIRT');

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([
                ['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => null],
            ]);
        $combosDao->method('listPoolChildStockIds')->willReturn([]);
        $combosDao->method('listChildrenByParent')->willReturn([]);

        $combosDao->expects($this->once())
            ->method('markInstantiated')
            ->with(1, 'SHIRT-red-m');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        // No stock_master row exists yet for the target child.
        $db->method('query')->willReturn([]);

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('1 created', $result);
    }

    public function testHandleDeletesHistorylessOrphanChild(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);

        // Pool holds SHIRT-red; hierarchy holds an unrepresented historyless orphan.
        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red']]);
        $combosDao->method('listPoolChildStockIds')->willReturn(['SHIRT-red']);
        $combosDao->method('listChildrenByParent')->willReturn(['SHIRT-old']);
        $combosDao->method('childHasHistory')->with('SHIRT-old')->willReturn(false);

        $combosDao->expects($this->once())
            ->method('removeChild')
            ->with('SHIRT-old');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]); // child exists in stock_master

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('1 removed', $result);
    }

    public function testHandleInactivatesHistoryWithZeroStockOrphan(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red']]);
        $combosDao->method('listPoolChildStockIds')->willReturn(['SHIRT-red']);
        $combosDao->method('listChildrenByParent')->willReturn(['SHIRT-gone']);
        $combosDao->method('childHasHistory')->with('SHIRT-gone')->willReturn(true);
        $combosDao->method('childQtyOnHand')->with('SHIRT-gone')->willReturn(0.0);

        $combosDao->expects($this->once())
            ->method('setChildInactive')
            ->with('SHIRT-gone');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]);

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('1 inactivated', $result);
    }

    public function testHandleBlocksHistoryWithStockOrphan(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red']]);
        $combosDao->method('listPoolChildStockIds')->willReturn(['SHIRT-red']);
        $combosDao->method('listChildrenByParent')->willReturn(['SHIRT-stocked']);
        $combosDao->method('childHasHistory')->with('SHIRT-stocked')->willReturn(true);
        $combosDao->method('childQtyOnHand')->with('SHIRT-stocked')->willReturn(5.0);

        // Nothing destructive may run.
        $combosDao->expects($this->never())->method('removeChild');
        $combosDao->expects($this->never())->method('setChildInactive');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]);

        $action = new GenerateChildAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('With stock', $result);
    }
}