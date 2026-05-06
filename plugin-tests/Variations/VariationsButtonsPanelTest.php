<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\UI;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Variations\UI\VariationsButtonsPanel;
use PHPUnit\Framework\TestCase;

class VariationsButtonsPanelTest extends TestCase
{
    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    protected function setUp(): void
    {
        $this->variationsDao = $this->getMockBuilder(VariationsDao::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstructor(): void
    {
        $panel = new VariationsButtonsPanel($this->variationsDao);
        $this->assertInstanceOf(VariationsButtonsPanel::class, $panel);
    }

    public function testRenderShowsParentButtonsForParentProduct(): void
    {
        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('PARENT001')
            ->willReturn([
                ['stock_id' => 'PARENT001-S-RED', 'description' => 'Variation 1'],
            ]);

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with('PARENT001')
            ->willReturn(['stock_id' => 'PARENT001', 'inactive' => 0]);

        // getProductParent should NOT be called for a parent product
        $this->variationsDao->expects($this->never())
            ->method('getProductParent');

        $panel = new VariationsButtonsPanel($this->variationsDao);

        ob_start();
        $panel->render('PARENT001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Variation Actions', $output);
        $this->assertStringContainsString('Create Variations', $output);
        $this->assertStringContainsString('Make Inactive', $output);
        $this->assertStringContainsString('Create Missing Variations', $output);
        $this->assertStringContainsString('variation_action', $output);
    }

    public function testRenderShowsReactivateButtonForInactiveParent(): void
    {
        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('PARENT001')
            ->willReturn([['stock_id' => 'PARENT001-S-RED', 'description' => 'Variation 1']]);

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with('PARENT001')
            ->willReturn(['stock_id' => 'PARENT001', 'inactive' => 1]);

        $panel = new VariationsButtonsPanel($this->variationsDao);

        ob_start();
        $panel->render('PARENT001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Reactivate Variations', $output);
        $this->assertStringNotContainsString('Make Inactive', $output);
    }

    public function testRenderShowsAssignParentDropdownForSimpleProduct(): void
    {
        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('SIMPLE001')
            ->willReturn([]);

        $this->variationsDao->expects($this->once())
            ->method('getProductParent')
            ->with('SIMPLE001')
            ->willReturn(null);

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with('SIMPLE001')
            ->willReturn(['stock_id' => 'SIMPLE001', 'inactive' => 0]);

        $this->variationsDao->expects($this->once())
            ->method('getParentStockIds')
            ->willReturn([
                ['parent_stock_id' => 'PARENT001'],
                ['parent_stock_id' => 'PARENT002'],
            ]);

        $panel = new VariationsButtonsPanel($this->variationsDao);

        ob_start();
        $panel->render('SIMPLE001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Assign Parent', $output);
        $this->assertStringContainsString('assign_parent_stock_id', $output);
        $this->assertStringContainsString('PARENT001', $output);
        $this->assertStringContainsString('PARENT002', $output);
        $this->assertStringContainsString('assign_parent', $output);
    }

    public function testRenderShowsNoButtonsForVariationProduct(): void
    {
        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('VARIATION001')
            ->willReturn([]);

        $this->variationsDao->expects($this->once())
            ->method('getProductParent')
            ->with('VARIATION001')
            ->willReturn(['stock_id' => 'PARENT001', 'description' => 'Parent Product']);

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with('VARIATION001')
            ->willReturn(['stock_id' => 'VARIATION001', 'inactive' => 0]);

        $panel = new VariationsButtonsPanel($this->variationsDao);

        ob_start();
        $panel->render('VARIATION001');
        $output = ob_get_clean();

        // Should show the panel container
        $this->assertStringContainsString('Variation Actions', $output);
        // But no buttons for variations (their parent manages them)
        $this->assertStringNotContainsString('Create Variations', $output);
        $this->assertStringNotContainsString('Assign Parent', $output);
    }

    public function testRenderHiddenStockIdField(): void
    {
        $this->variationsDao->expects($this->any())
            ->method('getProductVariations')
            ->willReturn([]);

        $this->variationsDao->expects($this->any())
            ->method('getProductParent')
            ->willReturn(null);

        $this->variationsDao->expects($this->any())
            ->method('getParentProductData')
            ->willReturn(['stock_id' => 'TEST001', 'inactive' => 0]);

        $this->variationsDao->expects($this->any())
            ->method('getParentStockIds')
            ->willReturn([]);

        $panel = new VariationsButtonsPanel($this->variationsDao);

        ob_start();
        $panel->render('TEST001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="stock_id"', $output);
        $this->assertStringContainsString('value="TEST001"', $output);
    }
}
