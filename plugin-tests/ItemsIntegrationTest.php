<?php

namespace Ksfraser\FA_ProductAttributes\Test\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;

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
        $tabCollection->expects($this->exactly(3))
            ->method('createTab')
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

    public function testGetTabContentReturnsFalseForShippingWhenNoDao()
    {
        $result = $this->integration->getTabContent('TEST001', 'shipping_attributes');
        $this->assertFalse($result);
    }

    public function testGetTabContentReturnsFalseForIdentifiersWhenNoDao()
    {
        $result = $this->integration->getTabContent('TEST001', 'product_identifiers');
        $this->assertFalse($result);
    }

    public function testGetTabContentRendersShippingTab()
    {
        $shippingDao = $this->createMock(ShippingAttributesDao::class);
        $shippingDao->method('get')->willReturn(null);

        $integration = new ItemsIntegration($this->service, $shippingDao);

        $this->expectOutputRegex('/shipping_attributes/');
        $result = $integration->getTabContent('TEST001', 'shipping_attributes');

        $this->assertTrue($result);
    }

    public function testGetTabContentRendersIdentifiersTab()
    {
        $identifiersDao = $this->createMock(ProductIdentifiersDao::class);
        $identifiersDao->method('get')->willReturn(null);

        $integration = new ItemsIntegration($this->service, null, $identifiersDao);

        $this->expectOutputRegex('/upsert_identifiers/');
        $result = $integration->getTabContent('TEST001', 'product_identifiers');

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

        $result = $this->integration->handlePreSave($itemData, 'TEST001');

        $this->assertEquals($itemData, $result);
    }

    public function testHandlePreDeleteDoesNotThrowException()
    {
        $this->service->expects($this->once())
            ->method('deleteProductAttributes')
            ->with('TEST001');

        $this->integration->handlePreDelete('TEST001');

        $this->assertTrue(true);
    }
}