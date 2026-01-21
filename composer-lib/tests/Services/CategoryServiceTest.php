<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * @covers \Ksfraser\FA_ProductAttributes\Services\CategoryService
 */
class CategoryServiceTest extends TestCase
{
    /** @var MockObject|ProductAttributesDao */
    private $daoMock;

    /** @var MockObject|DbAdapterInterface */
    private $dbAdapterMock;

    /** @var CategoryService */
    private $service;

    protected function setUp(): void
    {
        $this->daoMock = $this->createMock(ProductAttributesDao::class);
        $this->dbAdapterMock = $this->createMock(DbAdapterInterface::class);
        $this->service = new CategoryService($this->daoMock, $this->dbAdapterMock);
    }

    public function testCreateCategorySuccess(): void
    {
        $input = [
            'code' => 'COLOR',
            'label' => 'Color',
            'description' => 'Product colors',
            'sort_order' => 1,
            'active' => true
        ];

        $expectedCategory = [
            'id' => 1,
            'code' => 'COLOR',
            'label' => 'Color',
            'description' => 'Product colors',
            'sort_order' => 1,
            'active' => 1
        ];

        $this->daoMock->expects($this->exactly(2))
            ->method('listCategories')
            ->willReturnOnConsecutiveCalls([], [$expectedCategory]);

        $result = $this->service->createCategory($input);

        $this->assertEquals($expectedCategory, $result);
    }

    public function testCreateCategoryValidationError(): void
    {
        $input = [
            'code' => '',
            'label' => 'Color'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code is required');

        $this->service->createCategory($input);
    }

    public function testCreateCategoryDuplicateCode(): void
    {
        $input = [
            'code' => 'COLOR',
            'label' => 'Color'
        ];

        $existingCategories = [
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Existing Color']
        ];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn($existingCategories);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Category code 'COLOR' already exists. Use update to modify it.");

        $this->service->createCategory($input);
    }

    public function testUpdateCategorySuccess(): void
    {
        $categoryId = 1;
        $input = [
            'code' => 'COLOR_NEW',
            'label' => 'New Color',
            'description' => 'Updated description',
            'sort_order' => 2,
            'active' => false
        ];

        $existingCategories = [
            ['id' => 1, 'code' => 'COLOR_OLD', 'label' => 'Old Color']
        ];

        $updatedCategory = [
            'id' => 1,
            'code' => 'COLOR_NEW',
            'label' => 'New Color',
            'description' => 'Updated description',
            'sort_order' => 2,
            'active' => 0
        ];

        $this->daoMock->expects($this->exactly(2))
            ->method('listCategories')
            ->willReturnOnConsecutiveCalls($existingCategories, [$updatedCategory]);

        $result = $this->service->updateCategory($categoryId, $input);

        $this->assertEquals($updatedCategory, $result);
    }

    public function testUpdateCategoryNotFound(): void
    {
        $categoryId = 999;
        $input = ['code' => 'COLOR', 'label' => 'Color'];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'OTHER']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category not found');

        $this->service->updateCategory($categoryId, $input);
    }

    public function testDeleteCategoryHardDelete(): void
    {
        $categoryId = 1;

        $existingCategories = [
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color']
        ];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn($existingCategories);

        $this->dbAdapterMock->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 0]]); // Not in use

        $this->daoMock->expects($this->once())
            ->method('deleteCategory')
            ->with(1);

        $result = $this->service->deleteCategory($categoryId);

        $this->assertEquals([
            'deleted' => true,
            'hard_delete' => true,
            'message' => 'Category deleted successfully'
        ], $result);
    }

    public function testDeleteCategorySoftDelete(): void
    {
        $categoryId = 1;

        $existingCategories = [
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color']
        ];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn($existingCategories);

        $this->dbAdapterMock->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 1]]); // In use

        $this->daoMock->expects($this->once())
            ->method('upsertCategory')
            ->with('COLOR', 'Color', '', 0, false, 1); // Deactivate

        $result = $this->service->deleteCategory($categoryId);

        $this->assertEquals([
            'deleted' => true,
            'hard_delete' => false,
            'message' => 'Category deactivated successfully (in use by products)'
        ], $result);
    }
}