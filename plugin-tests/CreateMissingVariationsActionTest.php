<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Actions\CreateMissingVariationsAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class CreateMissingVariationsActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $dao     = $this->createMock(VariationsDao::class);
        $coreDao = $this->createMock(ProductAttributesDao::class);
        $db      = $this->createMock(DbAdapterInterface::class);

        $action = new CreateMissingVariationsAction($dao, $coreDao, $db);
        $result = $action->handle(['stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleWithParentNotFoundReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentProductData')->willReturn(null);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $db      = $this->createMock(DbAdapterInterface::class);

        $action = new CreateMissingVariationsAction($dao, $coreDao, $db);
        $result = $action->handle(['stock_id' => 'NONEXISTENT']);

        $this->assertStringContainsString('not found', $result);
    }

    public function testHandleWithNoCategoriesReturnsMessage(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentProductData')->willReturn(['stock_id' => 'P001', 'description' => 'Test']);
        $dao->method('listCategoryAssignments')->willReturn([]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $db      = $this->createMock(DbAdapterInterface::class);

        $action = new CreateMissingVariationsAction($dao, $coreDao, $db);
        $result = $action->handle(['stock_id' => 'P001']);

        $this->assertStringContainsString('No categories', $result);
    }

    public function testHandleCreatesOnlyMissingVariations(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentProductData')
            ->willReturn(['stock_id' => 'SHIRT', 'description' => 'Shirt']);

        $dao->method('listCategoryAssignments')
            ->willReturn([['category_id' => 1]]);

        $dao->method('listActiveValues')
            ->with(1)
            ->willReturn([
                ['slug' => 'red', 'value' => 'Red'],
                ['slug' => 'blue', 'value' => 'Blue'],
            ]);

        // SHIRT-red already exists; SHIRT-blue does not
        $dao->expects($this->once())
            ->method('createChildProduct')
            ->with('SHIRT-blue', $this->isType('array'));

        $dao->expects($this->once())
            ->method('setParentRelationship')
            ->with('SHIRT-blue', 'SHIRT');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')
            ->willReturnCallback(function (string $sql, array $params) {
                // SHIRT-red exists; SHIRT-blue does not
                if ($params['stock_id'] === 'SHIRT-red') {
                    return [['1' => 1]];
                }
                return [];
            });

        $coreDao = $this->createMock(ProductAttributesDao::class);

        $action = new CreateMissingVariationsAction($dao, $coreDao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT']);

        $this->assertStringContainsString('1', $result);
    }
}
