<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\DeleteCategoryAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DeleteCategoryActionTest extends TestCase
{
    public function testHandleWithValidCategoryIdNotInUse(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 123, 'code' => 'COLOR', 'label' => 'Color', 'description' => 'Product colors', 'sort_order' => 1]
            ]);
        $dao->expects($this->once())
            ->method('deleteCategory')
            ->with(123);
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $dbAdapter->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE category_id = :category_id', ['category_id' => 123])
            ->willReturn([['count' => 0]]);

        $action = new DeleteCategoryAction($dao, $dbAdapter);

        $result = $action->handle([
            'category_id' => '123'
        ]);

        $this->assertEquals("Category 'Color' and all its values deleted successfully", $result);
    }

    public function testHandleWithValidCategoryIdInUse(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 123, 'code' => 'COLOR', 'label' => 'Color', 'description' => 'Product colors', 'sort_order' => 1]
            ]);
        $dao->expects($this->once())
            ->method('upsertCategory')
            ->with('COLOR', 'Color', 'Product colors', 1, false, 123);
        $dao->expects($this->never())
            ->method('deleteCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $dbAdapter->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE category_id = :category_id', ['category_id' => 123])
            ->willReturn([['count' => 5]]);

        $action = new DeleteCategoryAction($dao, $dbAdapter);

        $result = $action->handle([
            'category_id' => '123'
        ]);

        $this->assertEquals("Category 'Color' deactivated successfully (in use by products)", $result);
    }

    public function testHandleWithInvalidCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listCategories');
        $dao->expects($this->never())
            ->method('deleteCategory');
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category ID is required");

        $action->handle([
            'category_id' => '0'
        ]);
    }

    public function testHandleWithMissingCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listCategories');
        $dao->expects($this->never())
            ->method('deleteCategory');
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category ID is required");

        $action->handle([]);
    }

    public function testHandleWithNonExistentCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 456, 'code' => 'SIZE', 'label' => 'Size']
            ]);
        $dao->expects($this->never())
            ->method('deleteCategory');
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new DeleteCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category not found");

        $action->handle([
            'category_id' => '123'
        ]);
    }
}