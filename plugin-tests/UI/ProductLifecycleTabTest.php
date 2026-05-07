<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductLifecycleTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use PHPUnit\Framework\TestCase;

class ProductLifecycleTabTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductLifecycleTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductLifecycleDao::class);
        $this->tab = new ProductLifecycleTab($this->dao);
    }

    public function testRenderOutputsForm(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('<form', $html);
    }

    public function testRenderContainsHiddenActionInput(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="upsert_lifecycle"', $html);
    }

    public function testRenderContainsStatusSelectElement(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="status"', $html);
    }

    public function testRenderAllStatusOptionsPresent(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="active"', $html);
        $this->assertStringContainsString('value="draft"', $html);
        $this->assertStringContainsString('value="discontinued"', $html);
        $this->assertStringContainsString('value="archived"', $html);
    }

    public function testRenderShowsCurrentStatusAsSelected(): void
    {
        $this->dao->method('get')->willReturn(['status' => 'discontinued']);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/value=\"discontinued\"[^>]*selected/', $html);
    }

    public function testRenderContainsBooleanFlagCheckboxes(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="is_special_order"', $html);
        $this->assertStringContainsString('name="is_clearance"', $html);
        $this->assertStringContainsString('name="is_featured"', $html);
    }

    public function testRenderChecksActiveFlagsFromData(): void
    {
        $this->dao->method('get')->willReturn(['is_featured' => 1, 'is_clearance' => 0]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertMatchesRegularExpression('/name="is_featured"[^>]*checked/', $html);
    }

    public function testRenderContainsDateInputs(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="available_from"', $html);
        $this->assertStringContainsString('name="discontinue_on"', $html);
    }

    public function testRenderPrePopulatesDates(): void
    {
        $this->dao->method('get')->willReturn([
            'available_from' => '2025-03-01',
            'discontinue_on' => '2026-01-01',
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('2025-03-01', $html);
        $this->assertStringContainsString('2026-01-01', $html);
    }
}
