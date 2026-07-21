<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use FrontAccounting\ProductAttributes\Variations\Dao\VariationsDao;
use FrontAccounting\ProductAttributes\Variations\Service\FrontAccountingVariationService;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildAction;
use Ksfraser\FA_ProductAttributes\Actions\GenerateVariationsAction;
use Ksfraser\FA_ProductAttributes\Actions\UpdateProductTypesAction;
use Ksfraser\FA_ProductAttributes\Tabs\VariationsTab;
use PHPUnit\Framework\TestCase;

class VariationsTabTest extends TestCase
{
    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var VariationsTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(VariationsDao::class);
        $this->tab = new VariationsTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_variations', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_variations', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Variations', $this->tab->getTabLabel());
    }

    public function testRenderTabContentEmptyStockId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())->method('listCategories')->willReturn([]);
        $this->dao->expects($this->once())->method('getProductParent')->willReturn(null);

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringContainsString('Variation Categories', $output);
    }

    public function testHandleSaveDoesNothing(): void
    {
        $this->tab->handleSave('SKU001', []);
        $this->assertTrue(true);
    }

    public function testHandleDeleteClearsParentRelationship(): void
    {
        $this->dao->expects($this->once())
            ->method('clearParentRelationship')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
