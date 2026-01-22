<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Handles UI-related operations for Product Attributes
 */
class ProductAttributesUI
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
     * Hook: Add Product Attributes tab to item display
     *
     * @param array $tabs Current tabs array
     * @param string $stock_id The item stock ID
     * @return array Modified tabs array
     */
    public function add_product_attributes_tab($tabs, $stock_id)
    {
        // Add Product Attributes tab
        $tabs['product_attributes'] = [
            'title' => _('Product Attributes'),
            'content' => $this->service->renderProductAttributesTab($stock_id)
        ];

        return $tabs;
    }
}