<?php

namespace Ksfraser\FA_ProductAttributes\Handler;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Single Responsibility: FA hook handler for product attribute save/delete events.
 */
class ProductAttributesHandler
{
    /** @var ProductAttributesService */
    private $service;

    public function __construct(ProductAttributesService $service)
    {
        $this->service = $service;
    }

    /**
     * Hook: handle saving product attributes when an item is saved.
     *
     * @param array<string, mixed> $itemData  Data being saved for the item
     * @param string               $stockId   The product stock ID
     * @return array<string, mixed>           Unchanged item data (pass-through)
     */
    public function handle_product_attributes_save(array $itemData, string $stockId): array
    {
        $this->service->saveProductAttributes($stockId, $_POST);

        return $itemData;
    }

    /**
     * Hook: handle deleting product attributes when an item is deleted.
     *
     * @param string $stockId The product stock ID
     */
    public function handle_product_attributes_delete(string $stockId): void
    {
        $this->service->deleteProductAttributes($stockId);
    }
}
