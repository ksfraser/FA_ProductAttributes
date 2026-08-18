<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Renders the variation action buttons on the Product Attributes TAB (BRD FR1).
 *
 * For parent products (has variations):
 *   - "Create Variations" — generate new variation combinations
 *   - "Make Inactive"     — deactivate parent and zero-stock children
 *   - "Create Missing Variations" — generate only absent combination rows
 *
 * For inactive parent products (in addition to the above):
 *   - "Reactivate Variations" — re-activate existing child variations
 *
 * For non-parent / non-variation products ("simple"):
 *   - "Assign Parent" dropdown — designate this product as a child of a parent
 */
class VariationsButtonsPanel
{
    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(VariationsDao $variationsDao)
    {
        $this->variationsDao = $variationsDao;
    }

    /**
     * Render the buttons panel for $stockId.
     *
     * @param string $stockId  The current item's stock ID
     */
    public function render(string $stockId): void
    {
        $variations = $this->variationsDao->getProductVariations($stockId);
        $isParent   = !empty($variations);

        $parent     = $isParent ? null : $this->variationsDao->getProductParent($stockId);
        $isVariation = ($parent !== null);

        // Get product data to check inactive status
        $productData = $this->variationsDao->getParentProductData($stockId);
        $isInactive  = !empty($productData['inactive']);

        echo '<div class="variations-buttons-panel">';
        echo '<h4>' . _('Variation Actions') . '</h4>';
        echo '<form method="post" action="">';

        if ($isParent) {
            $this->renderParentButtons($stockId, $isInactive);
        } elseif (!$isVariation) {
            $this->renderAssignParentDropdown($stockId);
        }

        echo '</form>';
        echo '</div>';
    }

    /**
     * Render buttons shown for parent products.
     *
     * @param string $stockId
     * @param bool   $isInactive  Whether the parent product is currently inactive
     */
    private function renderParentButtons(string $stockId, bool $isInactive): void
    {
        echo '<button type="submit" name="variation_action" value="create_variations" class="btn btn-primary">'
            . _('Create Variations') . '</button> ';

        echo '<button type="submit" name="variation_action" value="create_missing_variations" class="btn btn-default">'
            . _('Create Missing Variations') . '</button> ';

        if ($isInactive) {
            echo '<button type="submit" name="variation_action" value="reactivate_variations" class="btn btn-success">'
                . _('Reactivate Variations') . '</button> ';
        } else {
            echo '<button type="submit" name="variation_action" value="make_inactive"'
                . ' class="btn btn-warning"'
                . ' onclick="return confirm(\'' . addslashes(_('This will deactivate the parent and all zero-stock variations. Variations with stock > 0 will be listed. Continue?')) . '\')">'
                . _('Make Inactive') . '</button> ';
        }
    }

    /**
     * Render the "Assign Parent" dropdown for simple (non-parent, non-variation) products.
     *
     * @param string $stockId
     */
    private function renderAssignParentDropdown(string $stockId): void
    {
        // Get all parent products to populate the dropdown
        $allProducts    = [];
        $parentProducts = [];

        // Fetch products that themselves have at least one variation (i.e., are parents)
        // For the dropdown we query via the core products list
        // We use getProductVariations to detect parents among all products —
        // but that requires iterating, which is expensive. A targeted DAO query via the
        // assignments table is used instead to find distinct parent_stock_id values.
        $parentStockIds = $this->variationsDao->getParentStockIds();

        echo '<label for="assign_parent_stock_id">' . _('Assign Parent:') . '</label> ';
        echo '<select name="assign_parent_stock_id" id="assign_parent_stock_id">';
        echo '<option value="">' . _('— Select parent —') . '</option>';

        foreach ($parentStockIds as $row) {
            $parentId = htmlspecialchars($row['parent_stock_id']);
            echo '<option value="' . $parentId . '">' . $parentId . '</option>';
        }

        echo '</select> ';
        echo '<button type="submit" name="variation_action" value="assign_parent" class="btn btn-primary">'
            . _('Assign Parent') . '</button>';
    }
}
