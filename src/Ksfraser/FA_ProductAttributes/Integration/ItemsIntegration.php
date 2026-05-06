<?php

namespace Ksfraser\FA_ProductAttributes\Integration;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Single Responsibility: Bridges the FA items screen with the product-attributes module via tab hooks.
 */
class ItemsIntegration
{
    /** @var ProductAttributesService */
    private $service;

    public function __construct(ProductAttributesService $service)
    {
        $this->service = $service;
    }

    /**
     * Add the "Product Attributes" tab header to the FA items tab collection.
     *
     * @param  mixed  $tabCollection  FA tab collection object (must support createTab)
     * @param  string $stockId
     * @return mixed  Returns $tabCollection for fluent use
     */
    public function addTabHeaders($tabCollection, string $stockId)
    {
        $tabCollection->createTab('product_attributes', _('Product Attributes'));
        return $tabCollection;
    }

    /**
     * Echo the content for a given tab and stock item.
     *
     * @param  string $stockId
     * @param  string $tab
     * @return bool   true when content was handled, false otherwise
     */
    public function getTabContent(string $stockId, string $tab): bool
    {
        if ($tab !== 'product_attributes') {
            return false;
        }

        $html = $this->service->renderProductAttributesTab($stockId);
        echo $html;
        return true;
    }

    /**
     * Hook called before an item is saved.  Persists any product-attribute POST data.
     *
     * @param  array  $itemData
     * @param  string $stockId
     * @return array  Returns $itemData unchanged
     */
    public function handlePreSave(array $itemData, string $stockId): array
    {
        $this->service->saveProductAttributes($stockId, $_POST);
        return $itemData;
    }

    /**
     * Hook called before an item is deleted.  Removes all attribute assignments.
     *
     * @param  string $stockId
     */
    public function handlePreDelete(string $stockId): void
    {
        $this->service->deleteProductAttributes($stockId);
    }
}
