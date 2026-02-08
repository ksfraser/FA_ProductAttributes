<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\ValuesTab;
use PHPUnit\Framework\TestCase;

class ValuesTabTest extends TestCase
{
    public function testRenderDisplaysValuesForCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color'
                ]
            ]);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([
                [
                    'id' => 1,
                    'value' => 'Red',
                    'slug' => 'red',
                    'sort_order' => 1,
                    'active' => 1
                ],
                [
                    'id' => 2,
                    'value' => 'Blue',
                    'slug' => 'blue',
                    'sort_order' => 2,
                    'active' => 1
                ]
            ]);

        $tab = new ValuesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Check that the output contains expected elements
        $this->assertStringContainsString('COLOR', $output);
        $this->assertStringContainsString('Red', $output);
        $this->assertStringContainsString('Blue', $output);
        $this->assertStringContainsString('red', $output);
        $this->assertStringContainsString('blue', $output);
    }

    public function testRenderWithSpecificCategoryId(): void
    {
        // Set category_id in GET
        $_GET['category_id'] = '2';

        $dao = $this->createMock(ProductAttributesDao::class);
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

        $dao->expects($this->once())
            ->method('listValues')
            ->with(2)
            ->willReturn([
                [
                    'id' => 3,
                    'value' => 'Small',
                    'slug' => 'small',
                    'sort_order' => 1,
                    'active' => 1
                ]
            ]);

        $tab = new ValuesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Clean up
        unset($_GET['category_id']);

        // Check that the output contains expected elements for SIZE category
        $this->assertStringContainsString('SIZE', $output);
        $this->assertStringContainsString('Small', $output);
        $this->assertStringContainsString('small', $output);
    }

    public function testRenderWithEditMode(): void
    {
        $_GET['edit_value_id'] = '1';
        $_GET['category_id'] = '1';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'COLOR',
                    'label' => 'Color'
                ]
            ]);

        $dao->expects($this->exactly(2))
            ->method('listValues')
            ->with(1)
            ->willReturn([
                [
                    'id' => 1,
                    'value' => 'Red',
                    'slug' => 'red',
                    'sort_order' => 1,
                    'active' => 1
                ]
            ]);

        $tab = new ValuesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Clean up
        unset($_GET['edit_value_id'], $_GET['category_id']);

        // Check that the output contains expected elements
        $this->assertStringContainsString('Red', $output);
        $this->assertStringContainsString('red', $output);
    }

    public function testRenderWithEmptyCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        $dao->expects($this->never())
            ->method('listValues');

        $tab = new ValuesTab($dao);

        // Capture output
        ob_start();
        $tab->render();
        $output = ob_get_clean();

        // Should not crash with empty categories
        $this->assertIsString($output);
    }
}