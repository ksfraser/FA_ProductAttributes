<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\AssignmentsTab;
use PHPUnit\Framework\TestCase;

class AssignmentsTabTest extends TestCase
{
    public function testRenderDisplaysProductSelection(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getProductsByType')
            ->with(['simple', 'variable'])
            ->willReturn([
                [
                    'stock_id' => 'PROD001',
                    'description' => 'Test Product 1'
                ],
                [
                    'stock_id' => 'PROD002',
                    'description' => 'Test Product 2'
                ]
            ]);

        // Since no stock_id is selected, these methods should not be called
        $dao->expects($this->never())
            ->method('listCategoryAssignments');
        $dao->expects($this->never())
            ->method('listCategories');

        $tab = new AssignmentsTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Check that the output contains expected elements
        $this->assertStringContainsString('Product Category Assignments', $output);
        $this->assertStringContainsString('Select product', $output);
        $this->assertStringContainsString('PROD001', $output);
        $this->assertStringContainsString('Test Product 1', $output);
        $this->assertStringContainsString('PROD002', $output);
        $this->assertStringContainsString('Test Product 2', $output);
    }

    public function testRenderDisplaysAssignmentsForSelectedProduct(): void
    {
        // Set stock_id in GET
        $_GET['stock_id'] = 'PROD001';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getProductsByType')
            ->with(['simple', 'variable'])
            ->willReturn([
                [
                    'stock_id' => 'PROD001',
                    'description' => 'Test Product 1'
                ]
            ]);

        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('PROD001')
            ->willReturn([
                ['id' => 1, 'category_id' => 1]
            ]);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color'
                ],
                [
                    'id' => 2,
                    'code' => 'SIZE',
                    'label' => 'Size'
                ]
            ]);

        $tab = new AssignmentsTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Clean up
        unset($_GET['stock_id']);

        // Check that the output contains expected elements
        $this->assertStringContainsString('Product Category Assignments', $output);
        $this->assertStringContainsString('PROD001', $output);
        $this->assertStringContainsString('COLOR', $output);
        $this->assertStringContainsString('SIZE', $output);
    }

    public function testRenderWithEmptyProductsList(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getProductsByType')
            ->with(['simple', 'variable'])
            ->willReturn([]);

        $dao->expects($this->never())
            ->method('listCategoryAssignments');
        $dao->expects($this->never())
            ->method('listCategories');

        $tab = new AssignmentsTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Check that the output contains expected elements
        $this->assertStringContainsString('Product Category Assignments', $output);
        $this->assertStringContainsString('Select product', $output);
    }

    public function testRenderWithPostStockId(): void
    {
        // Set stock_id in POST
        $_POST['stock_id'] = 'PROD002';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getProductsByType')
            ->with(['simple', 'variable'])
            ->willReturn([
                [
                    'stock_id' => 'PROD001',
                    'description' => 'Test Product 1'
                ],
                [
                    'stock_id' => 'PROD002',
                    'description' => 'Test Product 2'
                ]
            ]);

        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('PROD002')
            ->willReturn([]);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color'
                ]
            ]);

        $tab = new AssignmentsTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Clean up
        unset($_POST['stock_id']);

        // Check that PROD002 is selected
        $this->assertStringContainsString('PROD002', $output);
        $this->assertStringContainsString('selected', $output);
    }
}