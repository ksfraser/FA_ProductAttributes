<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\CategoriesTab;
use PHPUnit\Framework\TestCase;

class CategoriesTabTest extends TestCase
{
    public function testRenderDisplaysCategoriesTable(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color',
                    'description' => 'Product colors',
                    'sort_order' => 1,
                    'active' => 1
                ],
                [
                    'id' => 2,
                    'code' => 'SIZE',
                    'label' => 'Size',
                    'description' => 'Product sizes',
                    'sort_order' => 2,
                    'active' => 0
                ]
            ]);

        $tab = new CategoriesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Check notifications
        $this->assertContains('CategoriesTab render() called', $GLOBALS['test_notifications']);
        $this->assertContains('Categories found: 2', $GLOBALS['test_notifications']);

        // Check that the output contains expected elements
        $this->assertStringContainsString('COLOR', $output);
        $this->assertStringContainsString('Color', $output);
        $this->assertStringContainsString('Product colors', $output);
        $this->assertStringContainsString('SIZE', $output);
        $this->assertStringContainsString('Size', $output);
        $this->assertStringContainsString('Product sizes', $output);
    }

    public function testRenderWithEmptyCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        $tab = new CategoriesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Check notifications
        $this->assertContains('CategoriesTab render() called', $GLOBALS['test_notifications']);
        $this->assertContains('Categories found: 0', $GLOBALS['test_notifications']);
    }

    public function testRenderWithEditMode(): void
    {
        // Set up GET parameter for edit mode
        $_GET['edit_category_id'] = '1';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color',
                    'description' => 'Product colors',
                    'sort_order' => 1,
                    'active' => 1
                ]
            ]);

        $tab = new CategoriesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Clean up
        unset($_GET['edit_category_id']);

        // Check notifications
        $this->assertContains('CategoriesTab render() called', $GLOBALS['test_notifications']);
        $this->assertContains('Categories found: 1', $GLOBALS['test_notifications']);

        // Check that the output contains expected elements
        $this->assertStringContainsString('COLOR', $output);
    }
}