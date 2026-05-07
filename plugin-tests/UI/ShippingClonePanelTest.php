<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ShippingClonePanel;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use PHPUnit\Framework\TestCase;

class ShippingClonePanelTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingDao;

    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    /** @var ShippingClonePanel */
    private $panel;

    protected function setUp(): void
    {
        $this->shippingDao   = $this->createMock(ShippingAttributesDao::class);
        $this->variationsDao = $this->createMock(VariationsDao::class);
        $this->panel = new ShippingClonePanel($this->shippingDao, $this->variationsDao);
    }

    // ── empty / no-variation cases ────────────────────────────────────────────

    public function testRenderOutputsNothingWhenEmptyStockId(): void
    {
        $this->variationsDao->expects($this->never())->method('getProductVariations');

        ob_start();
        $this->panel->render('');
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function testRenderOutputsNothingWhenNoVariations(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    // ── structure ─────────────────────────────────────────────────────────────

    public function testRenderOutputsFieldsetWhenVariationsExist(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('<fieldset>', $html);
        $this->assertStringContainsString('</fieldset>', $html);
    }

    public function testRenderContainsCorrectHiddenActionInput(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="clone_shipping_to_variations"', $html);
    }

    public function testRenderContainsParentStockIdHiddenInput(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="stock_id"', $html);
        $this->assertStringContainsString('value="PARENT"', $html);
    }

    // ── checkboxes ────────────────────────────────────────────────────────────

    public function testRenderOutputsCheckboxForEachVariation(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
            ['stock_id' => 'PARENT-BLUE'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('PARENT-RED',  $html);
        $this->assertStringContainsString('PARENT-BLUE', $html);
        $this->assertStringContainsString('name="variation_stock_ids[]"', $html);
    }

    public function testRenderOutputsSelectAllCheckbox(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('id="clone-select-all"', $html);
    }

    // ── shipping summary per variation ────────────────────────────────────────

    public function testRenderShowsNoneForVariationWithNoShipping(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-S'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('None', $html);
    }

    public function testRenderShowsWeightSummaryForVariationWithShipping(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-LARGE'],
        ]);
        $this->shippingDao->method('get')->with('PARENT-LARGE')->willReturn([
            'weight'      => '2.500',
            'weight_unit' => 'kg',
        ]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('2.5 kg', $html);
    }

    public function testRenderShowsDimensionsSummaryWhenAllPresent(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-XL'],
        ]);
        $this->shippingDao->method('get')->with('PARENT-XL')->willReturn([
            'length'   => '40.000',
            'width'    => '30.000',
            'height'   => '20.000',
            'dim_unit' => 'cm',
        ]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('40x30x20 cm', $html);
    }

    public function testRenderShowsHazmatWhenHazardous(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-DG'],
        ]);
        $this->shippingDao->method('get')->with('PARENT-DG')->willReturn([
            'is_hazardous' => 1,
        ]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('Hazmat', $html);
    }

    // ── XSS safety ────────────────────────────────────────────────────────────

    public function testRenderEscapesVariationStockIdForXss(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => '<script>alert(1)</script>'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderEscapesParentStockIdForXss(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('<script>bad</script>');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $html);
    }
}
