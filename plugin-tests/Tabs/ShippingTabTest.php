<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Tabs\ShippingTab;
use PHPUnit\Framework\TestCase;

class ShippingTabTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ShippingTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ShippingAttributesDao::class);
        $this->tab = new ShippingTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('shipping_attributes', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('shipping_attributes', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Shipping', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['length' => '30']);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }

    /**
     * Regression: POST save must persist via the tab handler without a
     * header('Location') hard refresh (GitHub issue #3 / #5 / #24).
     */
    public function testPostSaveShippingAttributesPersistsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_shipping_attributes', 'length' => '30', 'pa_shipping_save' => 'Save'];

        $this->dao->expects($this->once())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="pa_shipping_save"', $output);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    /**
     * Regression: a main-form POST without the tab save button (e.g. changing
     * the product selector) must NOT trigger the shipping save (GitHub
     * issues #16 / #28).
     */
    public function testPostWithoutSaveButtonDoesNotSave(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_shipping_attributes', 'length' => '30', 'stock_id' => 'SKU002'];

        $this->dao->expects($this->never())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('SKU002');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    public function testPostWithEmptyStockIdDoesNotSave(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_shipping_attributes', 'length' => '30', 'pa_shipping_save' => 'Save'];

        $this->dao->expects($this->never())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
