<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\UI\ProductAttributesTabUI;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class ProductAttributesTabUITest extends TestCase
{
    private $dao;
    private $ui;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->ui = new ProductAttributesTabUI($this->dao);
    }

    public function testRenderMainTab()
    {
        $stock_id = 'TEST001';

        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with($stock_id)
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with($stock_id)
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->with($stock_id)
            ->willReturn(null);

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'TEST001', 'description' => 'Test Product'],
                ['stock_id' => 'PARENT001', 'description' => 'Parent Product']
            ]);

        $html = $this->ui->renderMainTab($stock_id);

        $this->assertStringContains('<h4>Product Hierarchy:</h4>', $html);
        $this->assertStringContains('name="parent_stock_id"', $html);
        $this->assertStringContains('fa_pa_updateParent', $html);
        $this->assertStringContains('<h4>Current Assignments:</h4>', $html);
    }
}