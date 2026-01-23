<?php

namespace Ksfraser\FA_ProductAttributes\Integration;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Handles FrontAccounting items.php integration with SRP
 *
 * This class is responsible for integrating Product Attributes
 * into FA's items.php file by providing clean hook callbacks
 * that separate tab headers from tab content.
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
     * Hook callback for adding tab headers to the tab collection
     *
     * @param \Ksfraser\FA_Hooks\TabCollection $collection Current tab collection
     * @param string $stock_id The item stock ID
     * @return \Ksfraser\FA_Hooks\TabCollection Modified tab collection
     */
    public function addTabHeaders($collection, $stock_id)
    {
        // Add Product Attributes tab using the object-based API
        $collection->createTab('product_attributes', _('Product Attributes'));

        return $collection;
    }

    /**
     * Hook callback for providing tab content in the switch statement
     *
     * @param string $content Current content (usually empty)
     * @param string $stock_id The item stock ID
     * @param string $selected_tab The currently selected tab
     * @return string HTML content for the product_attributes tab
     */
    public function getTabContent($content, $stock_id, $selected_tab)
    {
        // Only provide content for our tab
        if ($selected_tab === 'product_attributes') {
            return $this->service->renderProductAttributesTab($stock_id);
        }

        // Return unchanged content for other tabs
        return $content;
    }

    /**
     * Hook callback for pre-save operations
     *
     * @param array $item_data The item data being saved
     * @param string $stock_id The item stock ID
     * @return array Modified item data
     */
    public function handlePreSave($item_data, $stock_id)
    {
        // Handle any product attributes data that needs to be saved
        // This could include processing POST data for attributes
        return $item_data;
    }

    /**
     * Hook callback for pre-delete operations
     *
     * @param string $stock_id The item stock ID being deleted
     * @return void
     */
    public function handlePreDelete($stock_id)
    {
        // Handle cleanup of product attributes data
        // This could include removing attribute assignments
    }

    // Static methods for hook registration

    /**
     * Get service instance (static helper for hooks)
     *
     * @return ProductAttributesService
     */
    private static function getService()
    {
        // This would need to be implemented to get the service instance
        // Could use a service locator or dependency injection container
        throw new \Exception('Service instantiation not implemented in static context');
    }

    /**
     * Static hook callback for tab headers
     */
    public static function staticAddTabHeaders($tabs, $stock_id)
    {
        $service = self::getService();
        $integration = new self($service);
        return $integration->addTabHeaders($tabs, $stock_id);
    }

    /**
     * Static hook callback for tab content
     */
    public static function staticGetTabContent($content, $stock_id, $selected_tab)
    {
        $service = self::getService();
        $integration = new self($service);
        return $integration->getTabContent($content, $stock_id, $selected_tab);
    }

    /**
     * Static hook callback for pre-save
     */
    public static function staticHandlePreSave($item_data, $stock_id)
    {
        $service = self::getService();
        $integration = new self($service);
        return $integration->handlePreSave($item_data, $stock_id);
    }

    /**
     * Static hook callback for pre-delete
     */
    public static function staticHandlePreDelete($stock_id)
    {
        $service = self::getService();
        $integration = new self($service);
        $integration->handlePreDelete($stock_id);
    }
}