<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Test ProductAttributesUI class
 */
class ProductAttributesUITest extends TestCase
{
    /**
     * @var ProductAttributesService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $service;

    /**
     * @var ProductAttributesUI
     */
    private $ui;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ProductAttributesService::class);
        $this->ui = new ProductAttributesUI($this->service);
    }

    /**
     * Test add_product_attributes_tab adds the tab correctly
     */
    public function testAddProductAttributesTab()
    {
        $stock_id = 'TEST001';
        $existing_tabs = [
            'general' => ['title' => 'General', 'content' => 'General content']
        ];

        $expected_content = '<div>Product Attributes Content</div>';
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with($stock_id)
            ->willReturn($expected_content);

        $result = $this->ui->add_product_attributes_tab($existing_tabs, $stock_id);

        $this->assertArrayHasKey('product_attributes', $result);
        $this->assertEquals(_('Product Attributes'), $result['product_attributes']['title']);
        $this->assertEquals($expected_content, $result['product_attributes']['content']);
        $this->assertArrayHasKey('general', $result); // Existing tabs preserved
    }
}