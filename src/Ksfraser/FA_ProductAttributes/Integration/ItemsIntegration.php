<?php

namespace Ksfraser\FA_ProductAttributes\Integration;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_ProductAttributes\UI\ShippingAttributesTab;
use Ksfraser\FA_ProductAttributes\UI\ProductIdentifiersTab;

/**
 * Single Responsibility: Bridges the FA items screen with the product-attributes module via tab hooks.
 */
class ItemsIntegration
{
    /** @var ProductAttributesService */
    private $service;

    /** @var ShippingAttributesDao|null */
    private $shippingDao;

    /** @var ProductIdentifiersDao|null */
    private $identifiersDao;

    public function __construct(
        ProductAttributesService $service,
        ?ShippingAttributesDao $shippingDao = null,
        ?ProductIdentifiersDao $identifiersDao = null
    ) {
        $this->service       = $service;
        $this->shippingDao   = $shippingDao;
        $this->identifiersDao = $identifiersDao;
    }

    /**
     * Add tab headers to the FA items tab collection.
     *
     * @param  mixed  $tabCollection  FA tab collection object (must support createTab)
     * @param  string $stockId
     * @return mixed  Returns $tabCollection for fluent use
     */
    public function addTabHeaders($tabCollection, string $stockId)
    {
        $tabCollection->createTab('product_attributes', _('Product Attributes'));
        $tabCollection->createTab('shipping_attributes', _('Shipping'));
        $tabCollection->createTab('product_identifiers', _('Identifiers'));
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
        if ($tab === 'product_attributes') {
            $html = $this->service->renderProductAttributesTab($stockId);
            echo $html;
            return true;
        }

        if ($tab === 'shipping_attributes' && $this->shippingDao !== null) {
            $tabUi = new ShippingAttributesTab($this->shippingDao);
            $tabUi->render($stockId);
            return true;
        }

        if ($tab === 'product_identifiers' && $this->identifiersDao !== null) {
            $tabUi = new ProductIdentifiersTab($this->identifiersDao);
            $tabUi->render($stockId);
            return true;
        }

        return false;
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
