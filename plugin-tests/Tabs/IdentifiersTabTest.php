<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Tabs\IdentifiersTab;
use PHPUnit\Framework\TestCase;

class IdentifiersTabTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var IdentifiersTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductIdentifiersDao::class);
        $this->tab = new IdentifiersTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_identifiers', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_identifiers', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Identifiers', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['brand' => 'Acme']);
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
     * header('Location') hard refresh.
     */
    public function testPostSaveIdentifiersPersistsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_product_identifiers', 'brand' => 'Acme'];

        $this->dao->expects($this->once())
            ->method('upsert');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="pa_identifiers_save"', $output);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
