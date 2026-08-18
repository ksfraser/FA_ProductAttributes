<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ShippingAttributesTab;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use PHPUnit\Framework\TestCase;

class ShippingAttributesTabTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ShippingAttributesTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ShippingAttributesDao::class);
        $this->tab = new ShippingAttributesTab($this->dao);
    }

    // ── basic structure ────────────────────────────────────────────────────────

    public function testRenderOutputsForm(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('name="stock_id"', $html);
        $this->assertStringContainsString('value="save_shipping_attributes"', $html);
    }

    public function testRenderOutputsAllFieldsets(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="length"',          $html);
        $this->assertStringContainsString('name="weight"',          $html);
        $this->assertStringContainsString('name="is_hazardous"',    $html);
        $this->assertStringContainsString('name="is_fragile"',      $html);
        $this->assertStringContainsString('name="temperature_sensitive"', $html);
        $this->assertStringContainsString('name="hs_code"',         $html);
    }

    // ── dim_unit select ────────────────────────────────────────────────────────

    public function testRenderContainsDimUnitOptions(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="dim_unit"', $html);
        $this->assertStringContainsString('value="cm"',      $html);
        $this->assertStringContainsString('value="in"',      $html);
    }

    public function testRenderContainsWeightUnitOptions(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="weight_unit"', $html);
        $this->assertStringContainsString('value="kg"',         $html);
        $this->assertStringContainsString('value="lb"',         $html);
    }

    // ── pre-population ─────────────────────────────────────────────────────────

    public function testRenderPopulatesExistingDimensions(): void
    {
        $this->dao->method('get')->willReturn([
            'stock_id'   => 'SKU001',
            'length'     => '45.500',
            'width'      => '30.000',
            'height'     => '15.000',
            'dim_unit'   => 'cm',
            'weight'     => '2.750',
            'weight_unit'=> 'kg',
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="45.5"',  $html);
        $this->assertStringContainsString('value="30"',    $html);
        $this->assertStringContainsString('value="15"',    $html);
        $this->assertStringContainsString('value="2.75"',  $html);
    }

    public function testRenderPreSelectsImperialUnit(): void
    {
        $this->dao->method('get')->willReturn([
            'dim_unit' => 'in',
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        // The 'in' option should be selected
        $this->assertMatchesRegularExpression('/<option[^>]*value="in"[^>]*selected/', $html);
    }

    // ── hazmat ─────────────────────────────────────────────────────────────────

    public function testRenderShowsHazmatDataWhenSet(): void
    {
        $this->dao->method('get')->willReturn([
            'is_hazardous'         => 1,
            'hazmat_class'         => '3',
            'un_number'            => '1170',
            'proper_shipping_name' => 'Ethanol',
            'packing_group'        => 'II',
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="3"',       $html);
        $this->assertStringContainsString('value="1170"',    $html);
        $this->assertStringContainsString('Ethanol',         $html);
        // packing group II should be selected
        $this->assertMatchesRegularExpression('/<option[^>]*value="II"[^>]*selected/', $html);
    }

    public function testRenderHazmatCheckboxCheckedWhenHazardous(): void
    {
        $this->dao->method('get')->willReturn(['is_hazardous' => 1]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/name="is_hazardous"[^>]*checked/', $html);
    }

    // ── handling flags ─────────────────────────────────────────────────────────

    public function testRenderStackableCheckedByDefaultWhenNoData(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/name="is_stackable"[^>]*checked/', $html);
    }

    public function testRenderFragileUncheckedByDefault(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        // is_fragile checkbox should NOT be checked by default
        $this->assertDoesNotMatchRegularExpression('/name="is_fragile"[^>]*checked/', $html);
    }

    // ── empty stock_id ─────────────────────────────────────────────────────────

    public function testRenderWithEmptyStockIdDoesNotCallDao(): void
    {
        $this->dao->expects($this->never())->method('get');

        ob_start();
        $this->tab->render('');
        ob_end_clean();
    }

    // ── XSS safety ─────────────────────────────────────────────────────────────

    public function testRenderEscapesCustomsDescriptionForXss(): void
    {
        $this->dao->method('get')->willReturn([
            'customs_description' => '<script>alert(1)</script>',
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderEscapesStockIdForXss(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('<script>alert(1)</script>');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}
