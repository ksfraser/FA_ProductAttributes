<?php

namespace Ksfraser\FA_ProductAttributes\Handler;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Test ProductAttributesHandler class
 */
class ProductAttributesHandlerTest extends TestCase
{
    /**
     * @var ProductAttributesService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $service;

    /**
     * @var ProductAttributesHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ProductAttributesService::class);
        $this->handler = new ProductAttributesHandler($this->service);
    }

    /**
     * Test handle_product_attributes_save calls service and returns item data unchanged
     */
    public function testHandleProductAttributesSave()
    {
        $stock_id = 'TEST001';
        $item_data = ['name' => 'Test Item', 'description' => 'Test Description'];
        $post_data = ['attribute_1' => 'value1'];

        // Set $_POST for the test
        $_POST = $post_data;

        $this->service->expects($this->once())
            ->method('saveProductAttributes')
            ->with($stock_id, $post_data);

        $result = $this->handler->handle_product_attributes_save($item_data, $stock_id);

        $this->assertEquals($item_data, $result); // Item data should be returned unchanged
    }

    /**
     * Test handle_product_attributes_delete calls service
     */
    public function testHandleProductAttributesDelete()
    {
        $stock_id = 'TEST001';

        $this->service->expects($this->once())
            ->method('deleteProductAttributes')
            ->with($stock_id);

        $this->handler->handle_product_attributes_delete($stock_id);
    }
}