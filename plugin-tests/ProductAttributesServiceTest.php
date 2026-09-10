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
        $this->assertTrue(strpos($result, 'name="pa_delete_row_42"') !== false, 'Should have per-row delete button');
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
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }

    public function testHandleDeleteRowUnassignsCategoryWhenLastValueRemoved(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignmentById')
            ->with(42)
            ->willReturn(['id' => 42, 'stock_id' => 'SKU001', 'category_id' => 6]);
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(42);
        $dao->expects($this->once())
            ->method('countCategoryValueAssignments')
            ->with('SKU001', 6)
            ->willReturn(0);
        $dao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('SKU001', 6);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $this->assertSame('Assignment removed.', $service->handleDeleteRow(42));
    }

    public function testHandleDeleteRowKeepsCategoryWhenValuesRemain(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignmentById')
            ->with(42)
            ->willReturn(['id' => 42, 'stock_id' => 'SKU001', 'category_id' => 6]);
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(42);
        $dao->expects($this->once())
            ->method('countCategoryValueAssignments')
            ->with('SKU001', 6)
            ->willReturn(2);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $this->assertSame('Assignment removed.', $service->handleDeleteRow(42));
    }

    public function testHandleDeleteRowRejectsUnknownRow(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignmentById')
            ->with(999)
            ->willReturn(null);
        $dao->expects($this->never())
            ->method('deleteAssignment');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $this->assertSame('Invalid assignment.', $service->handleDeleteRow(999));
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
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }

    public function testRenderProductAttributesTabDoesNotRenderConditionControls(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'colour', 'label' => 'Colour', 'sort_order' => 10],
            ]);
        $dao->expects($this->never())
            ->method('listConditions');
        $dao->expects($this->never())
            ->method('getProductCondition');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertStringNotContainsString('pa_condition', $result, 'Condition lived on the Lifecycle tab');
        $this->assertStringNotContainsString('save_pa_condition', $result);
        $this->assertStringNotContainsString('Condition</legend>', $result);
        $this->assertStringContainsString('name="add_pa_assignment" value="1"', $result, 'Add Assignment box should use a dedicated submit name');
        $this->assertStringNotContainsString('value="add_pa_assignment"', $result, 'No generic action hidden input leftover');
    }

    public function testRenderWiresCategoryToValueViaAjaxEndpoint(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategoriesWithAssignedValues')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 2, 'code' => 'color', 'label' => 'Color', 'sort_order' => 60],
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertStringContainsString('id="pa_category_select"', $result);
        $this->assertStringContainsString('id="pa_values_box"', $result);
        $this->assertStringContainsString('name="value_ids[]"', $result, 'Multi-value checkboxes (same box as admin page)');
        $this->assertStringContainsString('name="add_all"', $result, 'Add All flag present');
        $this->assertStringNotContainsString('onchange=', $result, 'No inline handler with embedded quotes');
        $this->assertStringNotContainsString('var paValues=', $result, 'Values loaded via the shared ajax endpoint');
        $this->assertStringContainsString('modules/FA_ProductAttributes/public/ajax_get_values.php', $result);
        $this->assertStringContainsString('encodeURIComponent(sel.value)', $result);
        $this->assertStringContainsString('</script>', $result);
    }

    public function testRenderProductAttributesTabKeepsAddAssignmentBox(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'colour', 'label' => 'Colour', 'sort_order' => 10],
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertStringContainsString('Add Assignment', $result);
        $this->assertStringContainsString('name="category_id"', $result);
        $this->assertStringContainsString('name="value_ids[]"', $result);
        $this->assertStringContainsString('name="add_all"', $result);
        $this->assertStringContainsString('name="sort_order"', $result);
        $this->assertStringContainsString('name="add_pa_assignment"', $result);
    }

    public function testRenderProductAttributesTabSkipsConditionUi(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);
        $dao->expects($this->never())
            ->method('listConditions');
        $dao->expects($this->never())
            ->method('getProductCondition');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertStringNotContainsString('pa_condition', $result, 'Condition UI lives on the Lifecycle tab');
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

    public function testSaveProductAttributesPersistsConditionIdFromDropdown(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', 6);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'condition_id' => '6',
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesClearsConditionOnDefaultDropdownSelection(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', null);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'condition_id' => '',
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
            ->method('listCategoryAssignments')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('setProductCondition')
            ->with('TEST123', null);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }

    public function testHandleAddAssignmentWithMultipleValues(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('TEST123', 6);
        $dao->expects($this->once())
            ->method('assignValues')
            ->with('TEST123', [
                ['category_id' => 6, 'value_id' => 13, 'sort_order' => 0],
                ['category_id' => 6, 'value_id' => 14, 'sort_order' => 0],
            ])
            ->willReturn([
                ['category_id' => 6, 'value_id' => 13, 'sort_order' => 0],
                ['category_id' => 6, 'value_id' => 14, 'sort_order' => 0],
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->handleAddAssignment('TEST123', [
            'category_id' => '6',
            'sort_order'  => '0',
            'value_ids'   => ['13', '14', '14'],
        ]);

        $this->assertEquals('2 assignment(s) added.', $result);
    }

    public function testHandleAddAssignmentWithAddAll(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listActiveValues')
            ->with(6)
            ->willReturn([
                ['id' => 13], ['id' => 14], ['id' => 15],
            ]);
        $dao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('TEST123', 6);
        $dao->expects($this->once())
            ->method('assignValues')
            ->with('TEST123', [
                ['category_id' => 6, 'value_id' => 13, 'sort_order' => 2],
                ['category_id' => 6, 'value_id' => 14, 'sort_order' => 2],
                ['category_id' => 6, 'value_id' => 15, 'sort_order' => 2],
            ])
            ->willReturn(array_slice([
                ['category_id' => 6, 'value_id' => 13, 'sort_order' => 2],
                ['category_id' => 6, 'value_id' => 14, 'sort_order' => 2],
                ['category_id' => 6, 'value_id' => 15, 'sort_order' => 2],
            ], 0, 3));

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->handleAddAssignment('TEST123', [
            'category_id' => '6',
            'sort_order'  => '2',
            'add_all'     => '1',
        ]);

        $this->assertEquals('3 assignment(s) added.', $result);
    }

    public function testHandleAddAssignmentRejectsNoSelection(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('addCategoryAssignment');
        $dao->expects($this->never())
            ->method('assignValues');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->handleAddAssignment('TEST123', [
            'category_id' => '6',
            'sort_order'  => '0',
        ]);

        $this->assertEquals('Please select at least one value, or check "Add All".', $result);
    }

    public function testHandleAddAssignmentReportDuplicate(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('TEST123', 6);
        $dao->expects($this->once())
            ->method('assignValues')
            ->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->handleAddAssignment('TEST123', [
            'category_id' => '6',
            'value_ids'   => ['13'],
        ]);

        $this->assertEquals('Those category-value pairs are already assigned.', $result);
    }
}