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
     * @param string $stock_id The item stock ID
     * @param string $selected_tab The currently selected tab
     * @return bool True if this integration handled the tab, false otherwise
     */
    public function getTabContent($stock_id, $selected_tab)
    {
        // Handle any tab that starts with 'product_attributes'
        if (preg_match('/^product_attributes/', $selected_tab)) {
            // For the base product_attributes tab, show the main interface
            if ($selected_tab === 'product_attributes') {
                $attributesContent = $this->service->renderProductAttributesTab($stock_id);
            } else {
                // For plugin tabs (e.g., product_attributes_dimensions), start with empty content
                // Plugins will populate this via hooks
                $attributesContent = '';
            }

            // Allow plugins to extend/modify the tab content based on the selected tab
            global $path_to_root;
            $hooksFile = $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';
            if (file_exists($hooksFile)) {
                require_once $hooksFile;
                $hooks = fa_hooks();

                // Apply extensions to the attributes tab content
                $extendedContent = $hooks->apply_filters('attributes_tab_content', $attributesContent, $stock_id, $selected_tab);
            } else {
                // No hooks available, use content as-is
                $extendedContent = $attributesContent;
            }

            echo $extendedContent;
            return true;
        }

        // Return false for other tabs (not handled)
        return false;
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
        // Handle core product attributes data that needs to be saved
        $this->service->saveProductAttributes($stock_id, $_POST);

        // Allow plugins to extend the save functionality
        global $path_to_root;
        require_once $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';
        $hooks = fa_hooks();

        // Apply extensions to the save process
        $extendedItemData = $hooks->apply_filters('attributes_save', $item_data, $stock_id);

        return $extendedItemData;
    }

    /**
     * Hook callback for pre-delete operations
     *
     * @param string $stock_id The item stock ID being deleted
     * @return void
     */
    public function handlePreDelete($stock_id)
    {
        // Handle cleanup of core product attributes data
        $this->service->deleteProductAttributes($stock_id);

        // Allow plugins to extend the delete functionality
        global $path_to_root;
        require_once $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';
        $hooks = fa_hooks();

        // Apply extensions to the delete process
        $hooks->do_action('attributes_delete', $stock_id);
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