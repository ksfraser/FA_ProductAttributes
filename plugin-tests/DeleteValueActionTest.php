<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\DeleteValueAction;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DeleteValueActionTest extends TestCase
{
    public function testHandleWithValidValueIdNotInUse(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 456, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1]
            ]);
        $dao->expects($this->once())
            ->method('deleteValue')
            ->with(456);
        $dao->expects($this->never())
            ->method('upsertValue');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $dbAdapter->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE value_id = :value_id', ['value_id' => 456])
            ->willReturn([['count' => 0]]);

        $action = new DeleteValueAction($dao, $dbAdapter);

        $result = $action->handle([
            'value_id' => '456',
            'category_id' => '123'
        ]);

        $this->assertEquals("Value 'Red' deleted successfully", $result);
    }

    public function testHandleWithValidValueIdInUse(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 456, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1]
            ]);
        $dao->expects($this->once())
            ->method('upsertValue')
            ->with(123, 'Red', 'red', 1, false, 456);
        $dao->expects($this->never())
            ->method('deleteValue');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $dbAdapter->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE value_id = :value_id', ['value_id' => 456])
            ->willReturn([['count' => 3]]);

        $action = new DeleteValueAction($dao, $dbAdapter);

        $result = $action->handle([
            'value_id' => '456',
            'category_id' => '123'
        ]);

        $this->assertEquals("Value 'Red' deactivated successfully (in use by products)", $result);
    }

    public function testHandleWithInvalidValueId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listValues');
        $dao->expects($this->never())
            ->method('deleteValue');
        $dao->expects($this->never())
            ->method('upsertValue');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteValueAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value ID is required");

        $action->handle([
            'value_id' => '0',
            'category_id' => '123'
        ]);
    }

    public function testHandleWithMissingValueId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listValues');
        $dao->expects($this->never())
            ->method('deleteValue');
        $dao->expects($this->never())
            ->method('upsertValue');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteValueAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value ID is required");

        $action->handle([
            'category_id' => '123'
        ]);
    }

    public function testHandleWithNonExistentValue(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 789, 'value' => 'Blue', 'slug' => 'blue']
            ]);
        $dao->expects($this->never())
            ->method('deleteValue');
        $dao->expects($this->never())
            ->method('upsertValue');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteValueAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value not found");

        $action->handle([
            'value_id' => '456',
            'category_id' => '123'
        ]);
    }
}