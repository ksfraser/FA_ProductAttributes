<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\GenerateCombosAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class GenerateCombosActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $coreDao   = $this->createMock(ProductAttributesDao::class);
        $combosDao = $this->createMock(CombosDao::class);
        $db        = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateCombosAction($coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleRejectsChildProduct(): void
    {
        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn('PARENT');
        $combosDao = $this->createMock(CombosDao::class);
        $db        = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateCombosAction($coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'CHILD']);

        $this->assertStringContainsString('variation of another product', $result);
    }

    public function testHandleWithNoCategoriesReturnsMessage(): void
    {
        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->method('listCategoryAssignments')->willReturn([]);

        $combosDao = $this->createMock(CombosDao::class);
        $db        = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateCombosAction($coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('No categories', $result);
    }

    public function testHandlePersistsCartesianProductToPool(): void
    {
        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->method('listCategoryAssignments')
            ->willReturn([['id' => 1], ['id' => 2]]);

        $coreDao->method('listActiveValues')
            ->willReturnCallback(function (int $categoryId) {
                if ($categoryId === 1) {
                    return [
                        ['id' => 10, 'slug' => 'red', 'value' => 'Red'],
                        ['id' => 11, 'slug' => 'blue', 'value' => 'Blue'],
                    ];
                }
                // category 2
                return [
                    ['id' => 20, 'slug' => 'm', 'value' => 'M'],
                ];
            });

        // Categories 1 and 2 have sort orders to make Royal Order deterministic.
        $coreDao->method('listCategories')
            ->willReturn([
                ['id' => 1, 'sort_order' => 1],
                ['id' => 2, 'sort_order' => 2],
            ]);

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->expects($this->once())
            ->method('syncCombos')
            ->with(
                'SHIRT',
                $this->callback(function (array $combos) {
                    // 2 (color) x 1 (size) = 2 combos
                    $this->assertCount(2, $combos);
                    $keys = array_column($combos, 'value_set_key');
                    $this->assertContains('10,20', $keys);
                    $this->assertContains('11,20', $keys);
                    return true;
                })
            )
            ->willReturn(2);

        $db = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateCombosAction($coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('2 new', $result);
    }

    public function testHandleNoNewCombosReportsUpToDate(): void
    {
        $coreDao = $this->createMock(ProductAttributesDao::class);
        $coreDao->method('getProductParent')->willReturn(null);
        $coreDao->method('listCategoryAssignments')
            ->willReturn([['id' => 1]]);
        $coreDao->method('listActiveValues')
            ->with(1)
            ->willReturn([['id' => 5, 'slug' => 'xl', 'value' => 'XL']]);
        $coreDao->method('listCategories')
            ->willReturn([['id' => 1, 'sort_order' => 1]]);

        $combosDao = $this->createMock(CombosDao::class);
        $combosDao->expects($this->once())
            ->method('syncCombos')
            ->willReturn(0);

        $db = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateCombosAction($coreDao, $combosDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('already up to date', $result);
    }
}