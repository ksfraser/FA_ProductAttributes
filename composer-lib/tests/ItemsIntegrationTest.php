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
        $existingTabs = [
            'general' => 'General',
            'settings' => 'Settings'
        ];

        $stockId = 'TEST001';

        $result = $this->integration->addTabHeaders($existingTabs, $stockId);

        $this->assertArrayHasKey('product_attributes', $result);
        $this->assertEquals('Product Attributes', $result['product_attributes']);
        $this->assertArrayHasKey('general', $result);
        $this->assertArrayHasKey('settings', $result);
    }

    public function testGetTabContentReturnsContentForProductAttributesTab()
    {
        $stockId = 'TEST001';
        $selectedTab = 'product_attributes';
        $expectedContent = '<div>Product Attributes Content</div>';

        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with($stockId)
            ->willReturn($expectedContent);

        $result = $this->integration->getTabContent('', $stockId, $selectedTab);

        $this->assertEquals($expectedContent, $result);
    }

    public function testGetTabContentReturnsUnchangedContentForOtherTabs()
    {
        $existingContent = '<div>Existing Content</div>';
        $stockId = 'TEST001';
        $selectedTab = 'general';

        $result = $this->integration->getTabContent($existingContent, $stockId, $selectedTab);

        $this->assertEquals($existingContent, $result);
    }

    public function testHandlePreSaveReturnsUnchangedData()
    {
        $itemData = ['name' => 'Test Item', 'price' => 10.00];
        $stockId = 'TEST001';

        $result = $this->integration->handlePreSave($itemData, $stockId);

        $this->assertEquals($itemData, $result);
    }

    public function testHandlePreDeleteDoesNotThrowException()
    {
        $stockId = 'TEST001';

        // Should not throw any exceptions
        $this->integration->handlePreDelete($stockId);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}