<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\Integration;

use Ksfraser\FA_ProductAttributes\Variations\Integration\VariationsIntegration;
use Ksfraser\FA_ProductAttributes\Variations\Service\FrontAccountingVariationService;
use PHPUnit\Framework\TestCase;

class VariationsIntegrationTest extends TestCase
{
    /** @var FrontAccountingVariationService|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var VariationsIntegration */
    private $integration;

    protected function setUp(): void
    {
        $this->service = $this->createMock(FrontAccountingVariationService::class);
        $this->integration = new VariationsIntegration($this->service);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(VariationsIntegration::class, $this->integration);
    }

    public function testExtendAttributesTabAppendsVariationsContent(): void
    {
        $originalContent = '<div>Original attributes content</div>';
        $stockId = 'TEST001';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        $this->assertStringStartsWith($originalContent, $result);
        $this->assertStringContainsString('variations-section', $result);
        $this->assertStringContainsString('Product Variations', $result);
    }

    public function testExtendAttributesTabWithEmptyContent(): void
    {
        $originalContent = '';
        $stockId = 'TEST001';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        $this->assertStringContainsString('variations-section', $result);
        $this->assertStringContainsString('Product Variations', $result);
    }

    public function testExtendAttributesTabWithNullContent(): void
    {
        $originalContent = null;
        $stockId = 'TEST001';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        $this->assertStringContainsString('variations-section', $result);
    }

    public function testExtendAttributesTabWithEmptyStockId(): void
    {
        $originalContent = '<div>Content</div>';
        $stockId = '';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        $this->assertStringStartsWith($originalContent, $result);
        $this->assertStringContainsString('variations-section', $result);
    }

    public function testExtendAttributesTabContentStructure(): void
    {
        $originalContent = '<div>Original</div>';
        $stockId = 'TEST001';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        // Verify the structure contains expected elements
        $this->assertStringContainsString('<div class="variations-section">', $result);
        $this->assertStringContainsString('<h4>Product Variations</h4>', $result);
        $this->assertStringContainsString('Variations functionality will be implemented here', $result);
    }

    public function testExtendAttributesTabPreservesOriginalContent(): void
    {
        $originalContent = '<div class="attributes">Original content with <strong>HTML</strong></div>';
        $stockId = 'TEST001';

        $result = $this->integration->extendAttributesTab($originalContent, $stockId);

        $this->assertStringStartsWith($originalContent, $result);
        $this->assertGreaterThan(strlen($originalContent), strlen($result));
    }
}