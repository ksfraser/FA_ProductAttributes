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
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
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
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                [
                    'id' => 42,
                    'category_id' => 1,
                    'category_label' => 'Color',
                    'value_id' => 10,
                    'value_label' => 'Red',
                    'sort_order' => 0,
                ]
            ]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'COLOR', 'label' => 'Color']
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'Product Attributes') !== false);
        $this->assertTrue(strpos($result, 'Color') !== false);
        $this->assertTrue(strpos($result, 'Red') !== false);
        $this->assertTrue(strpos($result, 'pa_delete_row_submit') !== false, 'Should have delete button');
        $this->assertTrue(strpos($result, 'Add Assignment') !== false, 'Should have Add Assignment section');
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

    public function testRenderProductAttributesTabPreselectsProductCondition(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([
                ['id' => 71, 'code' => 'new', 'label' => 'New', 'sort_order' => 10],
                ['id' => 73, 'code' => 'as_is', 'label' => 'As Is', 'sort_order' => 70],
            ]);
        $dao->expects($this->once())
            ->method('getProductCondition')
            ->with('TEST123')
            ->willReturn(73);
        $dao->expects($this->never())
            ->method('getDefaultCondition');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'Condition') !== false, 'Should render Condition box');
        $this->assertTrue(strpos($result, 'name="pa_condition" value="71"') !== false, 'Should have New radio');
        $this->assertTrue(strpos($result, 'name="pa_condition" value="73" checked') !== false, 'Product condition should be checked');
        $this->assertTrue(strpos($result, 'name="pa_condition" value="71" id="pa_condition_71"') !== false, 'New radio should be unchecked');
    }

    public function testRenderProductAttributesTabFallsBackToDefaultCondition(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([
                ['id' => 71, 'code' => 'new', 'label' => 'New', 'sort_order' => 10],
            ]);
        $dao->expects($this->once())
            ->method('getProductCondition')
            ->with('TEST123')
            ->willReturn(null);
        $dao->expects($this->once())
            ->method('getDefaultCondition')
            ->willReturn(71);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'name="pa_condition" value="71" checked') !== false, 'Default condition should be checked');
    }

    public function testRenderProductAttributesTabSkipsConditionBoxWhenNoActiveConditions(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([]);
        $dao->expects($this->never())
            ->method('getProductCondition');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'name="pa_condition"') === false, 'No radios when no active conditions');
    }

    public function testSaveProductAttributesPersistsCondition(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', 73);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'pa_condition' => '73',
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesClearsConditionWhenEmptySelection(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', null);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'pa_condition' => '',
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesSkipsConditionWhenFieldAbsent(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('setProductCondition');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'attribute_values' => [],
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testDeleteProductAttributesClearsCondition(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', null);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }
}