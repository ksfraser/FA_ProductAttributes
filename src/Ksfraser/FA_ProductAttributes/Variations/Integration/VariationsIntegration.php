<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Integration;

use Ksfraser\FA_ProductAttributes\Variations\Service\FrontAccountingVariationService;

/**
 * Handles FrontAccounting integration for Product Variations
 */
class VariationsIntegration
{
    /** @var FrontAccountingVariationService */
    private $service;

    public function __construct(FrontAccountingVariationService $service)
    {
        // Defensive check: Ensure the core FA_ProductAttributes plugin is loaded
        if (!class_exists('Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao')) {
            throw new \RuntimeException('The FA_ProductAttributes core plugin must be loaded before the variations plugin can function.');
        }

        $this->service = $service;
    }

    /**
     * Extend the core attributes tab with variations functionality
     *
     * @param string $content Current tab content from core module
     * @param string $stock_id The item stock ID
     * @return string Extended tab content
     */
    public function extendAttributesTab($content, $stock_id)
    {
        // Add variations UI elements to the existing attributes tab
        $variationsContent = $this->renderVariationsUI($stock_id);

        // Append variations content to the core attributes content
        return $content . $variationsContent;
    }

    /**
     * Render the variations UI for the attributes tab
     *
     * @param string $stock_id The item stock ID
     * @return string HTML content for variations
     */
    private function renderVariationsUI($stock_id)
    {
        // This would render the variations interface
        // For now, return a placeholder
        return '
        <div class="variations-section">
            <h4>Product Variations</h4>
            <p>Variations functionality will be implemented here.</p>
            <!-- Variations UI components will be added here -->
        </div>';
    }

    /**
     * Render the full variations tab content for embedded interface
     *
     * @param string $stock_id The item stock ID
     * @return string HTML content for the variations tab
     */
    public function renderVariationsTab($stock_id)
    {
        // Get categories for this product
        $categories = $this->service->getDao()->listCategories();

        $content = '<h4>Variation Categories</h4>';

        if (empty($categories)) {
            $content .= '<p>No variation categories defined. <a href="' . ($GLOBALS['path_to_root'] ?? '/fa') . '/modules/FA_ProductAttributes/product_attributes_admin.php">Manage Categories</a></p>';
        } else {
            $content .= '<p>Available categories for creating variations:</p>';
            start_table(TABLESTYLE2);
            table_header(array(_("Category"), _("Values"), _("Actions")));

            foreach ($categories as $category) {
                $values = $this->service->getDao()->listValues($category['id']);
                $valuesCount = count($values);

                start_row();
                label_cell($category['label']);
                label_cell($valuesCount . ' values');
                label_cell('<a href="' . ($GLOBALS['path_to_root'] ?? '/fa') . '/modules/FA_ProductAttributes/product_attributes_admin.php?tab=values&category_id=' . $category['id'] . '">Manage Values</a>');
                end_row();
            }

            end_table();
        }

        // Add variations management section
        $content .= '<br><h4>Product Variations</h4>';
        $content .= '<form method="post" action="">';
        $content .= '<input type="submit" name="generate_variations" value="Generate Variations" class="btn btn-default">';
        $content .= '</form>';

        return $content;
    }
}