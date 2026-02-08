<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\ActionHandler;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ActionHandlerTest extends TestCase
{
    public function testHandleUpsertCategory(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $variationsDao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $variationsDao->expects($this->once())
            ->method('upsertCategory')
            ->with('COLOR', 'Color', 'Product colors', 1, true, 0);

        $productAttributesDao = $this->createMock(ProductAttributesDao::class);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('query')
            ->willReturn([['cnt' => 1]]);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');

        $handler = new ActionHandler($variationsDao, $productAttributesDao, $dbAdapter);

        $result = $handler->handle('upsert_category', [
            'code' => 'COLOR',
            'label' => 'Color',
            'description' => 'Product colors',
            'sort_order' => '1',
            'active' => 'on'
        ]);

        $this->assertEquals("Category saved successfully", $result);
    }

    public function testHandleAddAssignment(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $productAttributesDao = $this->createMock(ProductAttributesDao::class);
        $productAttributesDao->expects($this->once())
            ->method('addAssignment')
            ->with('TEST123', 456, 789, 1);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($variationsDao, $productAttributesDao, $dbAdapter);

        $result = $handler->handle('add_assignment', [
            'stock_id' => 'TEST123',
            'category_id' => '456',
            'value_id' => '789',
            'sort_order' => '1'
        ]);

        $this->assertEquals("Added assignment", $result);
    }

    public function testHandleUnknownAction(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $productAttributesDao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($variationsDao, $productAttributesDao, $dbAdapter);

        $result = $handler->handle('unknown_action', []);

        $this->assertNull($result);
    }

    public function testHandleWithException(): void
    {
        $variationsDao = $this->createMock(VariationsDao::class);
        $productAttributesDao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($variationsDao, $productAttributesDao, $dbAdapter);

        $result = $handler->handle('upsert_category', [
            'code' => '',
            'label' => ''
        ]);

        $this->assertNull($result); // Exception should be caught and null returned
    }
}