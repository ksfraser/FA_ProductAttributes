<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class ProductTypesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        // Get all products
        $products = $this->dao->getAllProducts();

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title(_("Product Type Management"));
        table_header([
            _("Stock ID"),
            _("Description"),
            _("Current Type"),
            _("New Type"),
            _("Parent Product (for Variations)")
        ]);

        foreach ($products as $product) {
            $stockId = $product['stock_id'];
            $currentType = $this->getProductType($stockId);

            start_row();

            // Stock ID
            label_cell($stockId);

            // Description
            label_cell($product['description'] ?? '');

            // Current Type
            label_cell($this->formatProductType($currentType));

            // New Type Selection
            echo '<td>';
            echo '<select name="product_types[' . htmlspecialchars($stockId) . ']">';
            echo '<option value="">' . _("No change") . '</option>';
            echo '<option value="simple">' . _("Simple") . '</option>';
            echo '<option value="variable">' . _("Variable") . '</option>';
            echo '<option value="variation">' . _("Variation") . '</option>';
            echo '</select>';
            echo '</td>';

            // Parent Product Selection (only shown for variations)
            echo '<td>';
            echo '<select name="parent_products[' . htmlspecialchars($stockId) . ']" style="display: none;" class="parent-select">';
            echo '<option value="">' . _("Select parent") . '</option>';
            foreach ($products as $parentProduct) {
                if ($parentProduct['stock_id'] !== $stockId) {
                    echo '<option value="' . htmlspecialchars($parentProduct['stock_id']) . '">'
                        . htmlspecialchars($parentProduct['stock_id'] . ' - ' . $parentProduct['description'])
                        . '</option>';
                }
            }
            echo '</select>';
            echo '</td>';

            end_row();
        }

        end_table(1);

        // JavaScript to show/hide parent select based on type selection
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const typeSelects = document.querySelectorAll("select[name*=\'product_types\']");
            typeSelects.forEach(function(select) {
                select.addEventListener("change", function() {
                    const stockId = this.name.match(/\[([^\]]+)\]/)[1];
                    const parentSelect = document.querySelector("select[name=\'parent_products[" + stockId + "]\']");
                    if (this.value === "variation") {
                        parentSelect.style.display = "block";
                    } else {
                        parentSelect.style.display = "none";
                        parentSelect.value = "";
                    }
                });
            });
        });
        </script>';

        hidden('action', 'update_product_types');
        hidden('tab', 'product_types');

        submit_center('update', _("Update Product Types"));
        end_form();
    }

    /**
     * Determine the current type of a product
     */
    private function getProductType(string $stockId): string
    {
        // Check if it has category assignments (Variable)
        $categories = $this->dao->listCategoryAssignments($stockId);
        if (!empty($categories)) {
            return 'variable';
        }

        // Check if it has a parent (Variation)
        $parent = $this->dao->getProductParent($stockId);
        if ($parent) {
            return 'variation';
        }

        // Default to Simple
        return 'simple';
    }

    /**
     * Format product type for display
     */
    private function formatProductType(string $type): string
    {
        switch ($type) {
            case 'simple':
                return _("Simple");
            case 'variable':
                return _("Variable");
            case 'variation':
                return _("Variation");
            default:
                return _("Unknown");
        }
    }
}