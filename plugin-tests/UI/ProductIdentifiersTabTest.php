<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductIdentifiersTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use PHPUnit\Framework\TestCase;

class ProductIdentifiersTabTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductIdentifiersTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductIdentifiersDao::class);
        $this->tab = new ProductIdentifiersTab($this->dao);
    }

    public function testRenderOutputsForm(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function testRenderContainsHiddenActionInput(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="upsert_identifiers"', $html);
    }

    public function testRenderContainsHiddenStockIdInput(): void
    {
        $this->dao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="stock_id"', $html);
        $this->assertStringContainsString('SKU001', $html);
    }

    public function testRenderPrePopulatesExistingData(): void
    {
        $this->dao->method('get')->willReturn(['brand' => 'Acme Corp', 'mpn' => 'ACM-X1']);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('Acme Corp', $html);
        $this->assertStringContainsString('ACM-X1', $html);
    }

    public function testRenderRegularFieldsWhenStockIdEmpty(): void
    {
        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('name="brand"', $html);
    }

    public function testRenderContainsBrandField(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="brand"', $html);
    }

    public function testRenderContainsMpnField(): void
    {
        $this->dao->method('get')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('name="mpn"', $html);
    }

    public function testRenderEscapesSpecialCharactersInData(): void
    {
        $this->dao->method('get')->willReturn(['brand' => '<script>alert("xss")</script>']);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
