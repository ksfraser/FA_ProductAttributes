<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use PHPUnit\Framework\TestCase;

class ProductAttributesServiceTest extends TestCase
{
    public function testRenderProductAttributesTabWithNoAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'No product attributes assigned') !== false);
        $this->assertTrue(strpos($result, 'Product Attributes') !== false);
    }

    public function testRenderProductAttributesTabWithAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([
                ['id' => 1, 'label' => 'Color']
            ]);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                [
                    'category_id' => 1,
                    'category_label' => 'Color',
                    'value_id' => 10,
                    'value_label' => 'Red'
                ]
            ]);
        $dao->expects($this->exactly(2))
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'COLOR', 'label' => 'Color']
            ]);
        $dao->expects($this->once())
            ->method('getValuesForCategory')
            ->with(1)
            ->willReturn([
                ['id' => 10, 'value' => 'Red']
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'Product Attributes') !== false);
        $this->assertTrue(strpos($result, 'Color') !== false);
        $this->assertTrue(strpos($result, 'Red') !== false);
    }

    public function testSaveProductAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100]
            ]);
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(100);
        $dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['TEST123', 1, 10],
                ['TEST123', 1, 11]
            );

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'attribute_values' => [
                1 => [10, 11]
            ]
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesWithEmptyData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listAssignments');
        $dao->expects($this->never())
            ->method('deleteAssignment');
        $dao->expects($this->never())
            ->method('addAssignment');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testDeleteProductAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100],
                ['id' => 101]
            ]);
        $dao->expects($this->exactly(2))
            ->method('deleteAssignment')
            ->withConsecutive(
                [100],
                [101]
            );

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }

    public function testSaveProductAttributesWithCategoryAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // Mock current assignments
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([
                ['id' => 1, 'label' => 'Color'],
                ['id' => 2, 'label' => 'Size']
            ]);

        // Mock removing assignments
        $dao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('TEST123', 2);

        // Mock adding assignments
        $dao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('TEST123', 3);

        // Mock existing individual assignments
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'assigned_categories' => ['1', '3'], // Keep 1, remove 2, add 3
            'attribute_values' => []
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesWithIndividualValues(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // Mock current assignments
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([]);

        // Mock existing individual assignments
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100, 'category_id' => 1, 'value_id' => 10]
            ]);

        // Mock deleting existing assignments
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(100);

        // Mock adding new assignments
        $dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['TEST123', 1, 20],
                ['TEST123', 1, 30]
            );

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'assigned_categories' => [],
            'attribute_values' => [
                1 => ['20', '30'] // Two values for category 1
            ]
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testDeleteProductAttributesDetailed(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // Mock getting assignments to delete
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100],
                ['id' => 101]
            ]);

        // Mock deleting assignments
        $dao->expects($this->exactly(2))
            ->method('deleteAssignment')
            ->withConsecutive([100], [101]);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }
}