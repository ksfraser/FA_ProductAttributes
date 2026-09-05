<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildProductAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class CreateChildProductActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $coreDao       = $this->createMock(ProductAttributesDao::class);
        $combosDao     = $this->createMock(CombosDao::class);
        $db            = $this->createMock(DbAdapterInterface::class);

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
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

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
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

        // Full PA clone: the child must get its concrete value assignments.
        $coreDao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['SHIRT-red-m', 1, 10, 1, 'SHIRT'],
                ['SHIRT-red-m', 2, 20, 2, 'SHIRT']
            );

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([
                ['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => null,
                 'value_set' => [
                     ['category_id' => 1, 'value_id' => 10, 'slug' => 'red'],
                     ['category_id' => 2, 'value_id' => 20, 'slug' => 'm'],
                 ]],
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

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
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

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red', 'value_set' => []]]);
        $combosDao->method('listPoolChildStockIds')->willReturn(['SHIRT-red']);
        $combosDao->method('listChildrenByParent')->willReturn(['SHIRT-old']);
        $combosDao->method('childHasHistory')->with('SHIRT-old')->willReturn(false);

        $combosDao->expects($this->once())
            ->method('removeChild')
            ->with('SHIRT-old');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]); // child exists in stock_master

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
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
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red', 'value_set' => []]]);
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

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
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
            ->willReturn([['id' => 1, 'slug_key' => 'red-m', 'child_stock_id' => 'SHIRT-red', 'value_set' => []]]);
        $combosDao->method('listPoolChildStockIds')->willReturn(['SHIRT-red']);
        $combosDao->method('listChildrenByParent')->willReturn(['SHIRT-stocked']);
        $combosDao->method('childHasHistory')->with('SHIRT-stocked')->willReturn(true);
        $combosDao->method('childQtyOnHand')->with('SHIRT-stocked')->willReturn(5.0);

        $combosDao->expects($this->never())->method('removeChild');
        $combosDao->expects($this->never())->method('setChildInactive');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]);

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('With stock', $result);
    }

    public function testHandleAdoptsUnlinkedExistingChildAndRepairsAssignments(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);
        $variationsDao->expects($this->once())
            ->method('copyParentCategoryAssignments')
            ->with('SHIRT-red-m', 'SHIRT');
        $variationsDao->expects($this->once())
            ->method('setParentRelationship')
            ->with('SHIRT-red-m', 'SHIRT');

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->expects($this->once())
            ->method('setProductParent')
            ->with('SHIRT-red-m', 'SHIRT');
        $coreDao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['SHIRT-red-m', 1, 10, 1, 'SHIRT'],
                ['SHIRT-red-m', 2, 20, 2, 'SHIRT']
            );

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([
                ['id' => 7, 'slug_key' => 'red-m', 'child_stock_id' => null,
                 'value_set' => [
                     ['category_id' => 1, 'value_id' => 10, 'slug' => 'red'],
                     ['category_id' => 2, 'value_id' => 20, 'slug' => 'm'],
                 ]],
            ]);
        $combosDao->method('listPoolChildStockIds')->willReturn([]);
        $combosDao->method('listChildrenByParent')->willReturn([]);
        $combosDao->expects($this->once())
            ->method('markInstantiated')
            ->with(7, 'SHIRT-red-m');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        // stock_master row exists; no parent-linked assignments on the child yet.
        $db->method('query')->willReturnCallback(function ($sql) {
            return (strpos($sql, 'stock_master') !== false) ? [['1' => 1]] : [];
        });

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('0 created', $result);
    }

    public function testHandleAdoptsLinkedExistingChildWithoutRepair(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->method('getProductParent')->willReturn(null);
        $variationsDao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);
        $variationsDao->expects($this->never())->method('copyParentCategoryAssignments');
        $variationsDao->expects($this->never())->method('setParentRelationship');

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->expects($this->never())->method('setProductParent');
        $coreDao->expects($this->never())->method('addAssignment');

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->method('listCombos')
            ->willReturn([
                ['id' => 3, 'slug_key' => 'red-m', 'child_stock_id' => null,
                 'value_set' => [
                     ['category_id' => 1, 'value_id' => 10, 'slug' => 'red'],
                     ['category_id' => 2, 'value_id' => 20, 'slug' => 'm'],
                 ]],
            ]);
        $combosDao->method('listPoolChildStockIds')->willReturn([]);
        $combosDao->method('listChildrenByParent')->willReturn([]);
        $combosDao->expects($this->once())
            ->method('markInstantiated')
            ->with(3, 'SHIRT-red-m');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        // Child exists AND is already parent-linked: adopt only, no repair.
        $db->method('query')->willReturn([['1' => 1]]);

        $action = new CreateChildProductAction($variationsDao, $coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('0 created', $result);
    }
}