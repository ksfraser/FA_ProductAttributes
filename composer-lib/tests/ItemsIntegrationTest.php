<?php

namespace Ksfraser\FA_ProductAttributes\Test\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_Hooks\TabCollection;

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
        $tabCollection = $this->createMock(TabCollection::class);
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
        $this->markTestSkipped('Requires FA environment with fa_hooks.php');
    }

    public function testHandlePreDeleteDoesNotThrowException()
    {
        $this->markTestSkipped('Requires FA environment with fa_hooks.php');
    }
}