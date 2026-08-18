<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Tabs\WarrantyTab;
use PHPUnit\Framework\TestCase;

class WarrantyTabTest extends TestCase
{
    /** @var ProductWarrantyDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var WarrantyTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductWarrantyDao::class);
        $this->tab = new WarrantyTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_warranty', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_warranty', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Warranty', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['warranty_type' => 'manufacturer']);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }

    /**
     * Regression: Verify no <form> tag is rendered (fix for nested form bug #15/#16).
     * Tabs are rendered inside FA's main item form; an inner <form> creates invalid HTML.
     */
    public function testRenderDoesNotContainFormTag(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn([
                'warranty_type' => 'manufacturer',
                'manufacturer_duration' => 12,
                'warranty_notes' => 'Test coverage',
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<form', $output, 'Tab must not render a <form> tag to avoid nested forms inside FA item form');
        $this->assertStringNotContainsString('</form>', $output);
    }

    public function testRenderContainsWarrantyFields(): void
    {
        $this->dao->expects($this->once())
            ->method('get')
            ->with('SKU002')
            ->willReturn([
                'warranty_type' => 'lifetime',
                'lifetime_notes' => 'Lifetime satisfaction guaranteed',
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU002');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="warranty_type"', $output);
        $this->assertStringContainsString('value="lifetime"', $output);
        $this->assertStringContainsString('name="lifetime_notes"', $output);
        $this->assertStringContainsString('Lifetime satisfaction guaranteed', $output);
    }

    public function testRenderWithEmptyStockIdShowsDefaults(): void
    {
        $this->dao->expects($this->never())->method('get');

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="warranty_type"', $output);
        $this->assertStringContainsString('value="none"', $output);
        $this->assertStringNotContainsString('name="stock_id"', $output);
    }

    /**
     * Regression: warranty must save via the dedicated Save button without a
     * hard refresh (GitHub issue #15 / #24).
     */
    public function testPostSaveWarrantyPersistsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action'           => 'save_product_warranty',
            'pa_warranty_save' => 'Save',
            'warranty_type'    => 'manufacturer',
        ];

        $this->dao->expects($this->once())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Warranty', $output);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    public function testPostWithoutWarrantySaveButtonDoesNotPersist(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action'        => 'save_product_warranty',
            'warranty_type' => 'extended',
        ];

        $this->dao->expects($this->never())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
