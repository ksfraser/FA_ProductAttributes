<?php

namespace Ksfraser\FA_ProductAttributes\Handler;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Handles business logic operations for Product Attributes
 */
class ProductAttributesHandler
{
    /**
     * @var ProductAttributesService
     */
    private $service;

    /**
     * Constructor
     *
     * @param ProductAttributesService $service
     */
    public function __construct(ProductAttributesService $service)
    {
        $this->service = $service;
    }

    /**
     * Hook: Handle product attributes save
     *
     * @param array $item_data The item data being saved
     * @param string $stock_id The item stock ID
     * @return array Modified item data
     */
    public function handle_product_attributes_save($item_data, $stock_id)
    {
        // Handle saving product attributes data
        // This will be called before the item is saved
        $this->service->saveProductAttributes($stock_id, $_POST);

        return $item_data;
    }

    /**
     * Hook: Handle product attributes delete
     *
     * @param string $stock_id The item stock ID being deleted
     */
    public function handle_product_attributes_delete($stock_id)
    {
        // Handle cleanup when item is deleted
        $this->service->deleteProductAttributes($stock_id);
    }
}