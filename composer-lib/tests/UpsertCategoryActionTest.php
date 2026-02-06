<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertCategoryAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class UpsertCategoryActionTest extends TestCase
{
    public function testHandleCreateWithValidData(): void
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
            ->willReturn('0_');

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $result = $action->handle([
            'code' => 'COLOR',
            'label' => 'Color',
            'description' => 'Product colors',
            'sort_order' => '1',
            'active' => 'on'
        ]);

        $this->assertEquals("Category saved successfully", $result);
    }

    public function testHandleUpdateWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 123, 'code' => 'COLOR', 'label' => 'Color']
            ]);
        $dao->expects($this->once())
            ->method('upsertCategory')
            ->with('COLOR', 'Color Updated', 'Updated description', 2, false, 123);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);
        $dbAdapter->expects($this->once())
            ->method('query')
            ->willReturn([['cnt' => 1]]);
        $dbAdapter->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('0_');

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $result = $action->handle([
            'category_id' => '123',
            'code' => 'COLOR',
            'label' => 'Color Updated',
            'description' => 'Updated description',
            'sort_order' => '2'
        ]);

        $this->assertEquals("Category updated successfully", $result);
    }

    public function testHandleCreateWithMissingCode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listCategories');
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Code and label are required");

        $action->handle([
            'label' => 'Color',
            'description' => 'Product colors'
        ]);
    }

    public function testHandleCreateWithMissingLabel(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listCategories');
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Code and label are required");

        $action->handle([
            'code' => 'COLOR',
            'description' => 'Product colors'
        ]);
    }

    public function testHandleCreateWithDuplicateCode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 456, 'code' => 'COLOR', 'label' => 'Existing Color']
            ]);
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category code 'COLOR' already exists. Use Edit to modify it.");

        $action->handle([
            'code' => 'COLOR',
            'label' => 'New Color',
            'description' => 'Product colors'
        ]);
    }

    public function testHandleUpdateWithDuplicateCode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 123, 'code' => 'OLD_COLOR', 'label' => 'Old Color'],
                ['id' => 456, 'code' => 'COLOR', 'label' => 'Existing Color']
            ]);
        $dao->expects($this->never())
            ->method('upsertCategory');

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new UpsertCategoryAction($dao, $dbAdapter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category code 'COLOR' already exists");

        $action->handle([
            'category_id' => '123',
            'code' => 'COLOR',
            'label' => 'Updated Color',
            'description' => 'Product colors'
        ]);
    }
}