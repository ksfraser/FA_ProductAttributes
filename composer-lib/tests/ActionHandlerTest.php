<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\ActionHandler;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ActionHandlerTest extends TestCase
{
    public function testHandleUpsertCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('upsertCategory')
            ->with('COLOR', 'Color', 'Product colors', 1, true, 0);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('query')
            ->willReturn([['cnt' => 1]]);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');

        $handler = new ActionHandler($dao, $dbAdapter);

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
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('addAssignment')
            ->with('TEST123', 456, 789, 1);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($dao, $dbAdapter);

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
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($dao, $dbAdapter);

        $result = $handler->handle('unknown_action', []);

        $this->assertNull($result);
    }

    public function testHandleWithException(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $handler = new ActionHandler($dao, $dbAdapter);

        $result = $handler->handle('upsert_category', [
            'code' => '',
            'label' => ''
        ]);

        $this->assertNull($result); // Exception should be caught and null returned
    }
}