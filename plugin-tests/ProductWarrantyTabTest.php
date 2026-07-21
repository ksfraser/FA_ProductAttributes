<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\UI\ProductWarrantyTab;
use PHPUnit\Framework\TestCase;

class ProductWarrantyTabTest extends TestCase
{
    /** @var ProductWarrantyDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductWarrantyTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductWarrantyDao::class);
        $this->tab = new ProductWarrantyTab($this->dao);
    }

    public function testRenderOutputsHiddenStockId(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="stock_id"', $output);
        $this->assertStringContainsString('value="SKU001"', $output);
    }

    public function testRenderShowsNoneSelectedWhenNoData(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString(' checked id="warranty_type_none"', $output);
    }

    public function testRenderShowsCorrectTypeSelected(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn(['warranty_type' => 'lifetime']);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString(' checked id="warranty_type_lifetime"', $output);
    }

    public function testRenderEmptyStockIdDoesNotCallDao(): void
    {
        $this->dao->expects($this->never())->method('get');

        ob_start();
        $this->tab->render('');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="stock_id"', $output);
    }

    public function testRenderOutputsDurationFields(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([
                'manufacturer_duration' => '12',
                'extended_duration'     => '24',
                'third_party_duration'  => '36',
            ]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('value="12"', $output);
        $this->assertStringContainsString('value="24"', $output);
        $this->assertStringContainsString('value="36"', $output);
    }

    public function testRenderOutputsDurationUnitSelect(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([
                'manufacturer_duration_unit' => 'years',
                'extended_duration_unit'     => 'days',
                'third_party_duration_unit'  => 'months',
            ]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('value="years" selected', $output);
        $this->assertStringContainsString('value="days" selected', $output);
        $this->assertStringContainsString('value="months" selected', $output);
    }

    public function testRenderOutputsNotes(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([
                'lifetime_notes' => 'Lifetime coverage',
                'warranty_notes' => 'Full warranty terms',
            ]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Lifetime coverage', $output);
        $this->assertStringContainsString('Full warranty terms', $output);
    }

    public function testSelectFieldDefaultsToDaysMonthsYears(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Days', $output);
        $this->assertStringContainsString('Months', $output);
        $this->assertStringContainsString('Years', $output);
    }
}
