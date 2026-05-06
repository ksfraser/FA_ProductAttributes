<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;

/**
 * Single Responsibility: Injects the Product Attributes tab into FA's Items screen.
 */
class ProductAttributesUI
{
    /** @var ProductAttributesService */
    private $service;

    public function __construct(ProductAttributesService $service)
    {
        $this->service = $service;
    }

    /**
     * Returns the updated tabs array with the Product Attributes tab added.
     *
     * @param array<string, array<string, string>> $tabs     Existing tabs
     * @param string                               $stockId  Current item stock ID
     * @return array<string, array<string, string>>
     */
    public function add_product_attributes_tab(array $tabs, string $stockId): array
    {
        $tabs['product_attributes'] = [
            'title'   => _('Product Attributes'),
            'content' => $this->service->renderProductAttributesTab($stockId),
        ];

        return $tabs;
    }
}
