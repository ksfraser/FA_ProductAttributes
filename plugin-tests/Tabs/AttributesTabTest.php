<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_ProductAttributes\Tabs\AttributesTab;
use PHPUnit\Framework\TestCase;

class AttributesTabTest extends TestCase
{
    /** @var ProductAttributesService|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var ProductAttributesHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var AttributesTab */
    private $tab;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ProductAttributesService::class);
        $this->handler = $this->createMock(ProductAttributesHandler::class);
        $this->tab     = new AttributesTab($this->service, $this->handler);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_attributes', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_attributes', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Product Attributes', $this->tab->getTabLabel());
    }

    public function testRenderTabContentDelegatesToService(): void
    {
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with('SKU001')
            ->willReturn('tab content');

        $saved = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();
        if ($saved !== null) {
            $_SERVER['REQUEST_METHOD'] = $saved;
        } else {
            unset($_SERVER['REQUEST_METHOD']);
        }

        $this->assertSame('tab content', $output);
    }

    public function testHandleSaveDelegatesToHandler(): void
    {
        $postData = ['some' => 'data'];
        $this->handler->expects($this->once())
            ->method('handle_product_attributes_save')
            ->with($postData, 'SKU001');

        $this->tab->handleSave('SKU001', $postData);
    }

    public function testHandleDeleteDelegatesToHandler(): void
    {
        $this->handler->expects($this->once())
            ->method('handle_product_attributes_delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
