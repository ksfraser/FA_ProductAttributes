<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductAttributesTabUI;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class ProductAttributesTabUITest extends TestCase
{
    private function buildDao(
        array $assignments = [],
        array $categoryAssignments = [],
        ?string $parent = null,
        array $allProducts = []
    ): ProductAttributesDao {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->method('listAssignments')->willReturn($assignments);
        $dao->method('listCategoryAssignments')->willReturn($categoryAssignments);
        $dao->method('getProductParent')->willReturn($parent);
        $dao->method('getAllProducts')->willReturn($allProducts);
        return $dao;
    }

    public function testRenderMainTabContainsForm(): void
    {
        $dao = $this->buildDao();
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('SKU001');

        $this->assertStringContainsString('parent_stock_id', $html);
        $this->assertStringContainsString('SKU001', $html);
    }

    public function testRenderMainTabShowsNoAssignmentsMessage(): void
    {
        $dao = $this->buildDao();
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('SKU001');

        $this->assertStringContainsString('No attributes assigned', $html);
    }

    public function testRenderMainTabShowsAssignmentsTable(): void
    {
        $assignments = [
            ['category_label' => 'Color', 'value_label' => 'Red'],
            ['category_label' => 'Size', 'value_label' => 'Large'],
        ];
        $dao = $this->buildDao($assignments);
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('SKU001');

        $this->assertStringContainsString('Color', $html);
        $this->assertStringContainsString('Red', $html);
        $this->assertStringContainsString('Size', $html);
        $this->assertStringContainsString('Large', $html);
        $this->assertStringNotContainsString('No attributes assigned', $html);
    }

    public function testRenderMainTabExcludesSelfFromParentDropdown(): void
    {
        $allProducts = [
            ['stock_id' => 'SKU001', 'description' => 'Self'],
            ['stock_id' => 'PARENT01', 'description' => 'A Parent'],
        ];
        $dao = $this->buildDao([], [], null, $allProducts);
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('SKU001');

        // SKU001 should NOT appear as a selectable parent option (only as the hidden input)
        $this->assertStringContainsString('PARENT01', $html);
        $optionsHtml = substr($html, strpos($html, 'parent_stock_id'));
        $this->assertStringNotContainsString('<option value=\'SKU001\'', $html);
    }

    public function testRenderMainTabSelectsCurrentParent(): void
    {
        $allProducts = [
            ['stock_id' => 'PARENT01', 'description' => 'A Parent'],
            ['stock_id' => 'PARENT02', 'description' => 'Another Parent'],
        ];
        $dao = $this->buildDao([], [], 'PARENT01', $allProducts);
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('SKU001');

        $this->assertStringContainsString("value='PARENT01' selected", $html);
    }

    public function testRenderMainTabEscapesHtml(): void
    {
        $dao = $this->buildDao();
        $ui = new ProductAttributesTabUI($dao);
        $html = $ui->renderMainTab('<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
