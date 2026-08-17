<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use FrontAccounting\ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Tabs\VariationsTab;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class VariationsTabTest extends TestCase
{
    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $coreDao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var VariationsTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(VariationsDao::class);
        $this->coreDao = $this->createMock(ProductAttributesDao::class);
        $this->db      = $this->createMock(DbAdapterInterface::class);
        $this->tab     = new VariationsTab($this->dao, $this->coreDao, $this->db);
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

    /**
     * Regression: Verify no <form> tag is rendered (fix for nested form bug #15/#16).
     */
    public function testRenderDoesNotContainFormTag(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())->method('listCategories')->willReturn([]);
        $this->dao->expects($this->once())->method('getProductParent')->willReturn(null);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<form', $output);
        $this->assertStringNotContainsString('</form>', $output);
    }

    public function testRenderShowsActionButtons(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())->method('listCategories')->willReturn([]);
        $this->dao->expects($this->once())->method('getProductParent')->willReturn(null);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('generate_variations', $output);
        $this->assertStringContainsString('create_child', $output);
    }

    public function testRenderShowsParentInfo(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())->method('listCategories')->willReturn([]);
        $this->dao->expects($this->once())->method('getProductParent')->willReturn([
            'stock_id' => 'PARENT001',
            'description' => 'Parent Product',
        ]);

        ob_start();
        $this->tab->renderTabContent('CHILD001');
        $output = ob_get_clean();

        $this->assertStringContainsString('PARENT001', $output);
        $this->assertStringContainsString('Parent Product', $output);
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
