<?php

namespace Ksfraser\FA_ProductAttributes\Test\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Test ItemsIntegration class
 */
class ItemsIntegrationTest extends TestCase
{
    /** @var ProductAttributesService|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var ItemsIntegration */
    private $integration;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ProductAttributesService::class);
        $this->integration = new ItemsIntegration($this->service);
    }

    public function testAddTabHeadersAddsProductAttributesTab()
    {
        // Create a mock TabCollection class since fa-hooks may not be available in test environment
        $tabCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['createTab'])
            ->getMock();
        $tabCollection->expects($this->once())
            ->method('createTab')
            ->with('product_attributes', 'Product Attributes')
            ->willReturnSelf();

        $stockId = 'TEST001';

        $result = $this->integration->addTabHeaders($tabCollection, $stockId);

        $this->assertSame($tabCollection, $result);
    }

    public function testGetTabContentReturnsContentForProductAttributesTab()
    {
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with('TEST001')
            ->willReturn('<div>Test Content</div>');

        $this->expectOutputString('<div>Test Content</div>');

        $result = $this->integration->getTabContent('TEST001', 'product_attributes');

        $this->assertTrue($result);
    }

    public function testGetTabContentReturnsUnchangedContentForOtherTabs()
    {
        $result = $this->integration->getTabContent('TEST001', 'other_tab');

        $this->assertFalse($result);
    }

    public function testHandlePreSaveReturnsUnchangedData()
    {
        $this->service->expects($this->once())
            ->method('saveProductAttributes')
            ->with('TEST001', $_POST);

        $itemData = ['field1' => 'value1'];

        // Skip the complex FA hooks part for now - focus on core functionality
        // The FA hooks require full FA environment which is not available in unit tests
        $result = $this->integration->handlePreSave($itemData, 'TEST001');

        $this->assertEquals($itemData, $result);
    }

    public function testHandlePreDeleteDoesNotThrowException()
    {
        $this->service->expects($this->once())
            ->method('deleteProductAttributes')
            ->with('TEST001');

        // Skip the complex FA hooks part for now - focus on core functionality
        $this->integration->handlePreDelete('TEST001');

        $this->assertTrue(true);
    }
}