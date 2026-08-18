<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Single Responsibility: Renders the "Copy Identifiers to Variations" panel.
 *
 * Shown on the Identifiers tab when the current product is a parent.
 * Allows bulk-copying brand/barcode/sourcing data to selected variations.
 *
 * POSTs action=clone_identifiers_to_variations with:
 *   stock_id              string     Parent stock ID
 *   variation_stock_ids[] string[]   Selected variation IDs
 */
class IdentifiersClonePanel
{
    /** @var ProductIdentifiersDao */
    private $identifiersDao;

    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(ProductIdentifiersDao $identifiersDao, VariationsDao $variationsDao)
    {
        $this->identifiersDao = $identifiersDao;
        $this->variationsDao  = $variationsDao;
    }

    /**
     * Render the panel.
     * Outputs nothing when $stockId is empty or the product has no variations.
     */
    public function render(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }

        $variations = $this->variationsDao->getProductVariations($stockId);
        if (empty($variations)) {
            return;
        }

        $js = 'var chk=document.querySelectorAll(\'input[name="variation_stock_ids[]"]\');'
            . 'for(var i=0;i<chk.length;i++){chk[i].checked=this.checked;}';

        echo '<fieldset>';
        echo '<legend>' . _('Copy Identifiers to Variations') . '</legend>';
        echo '<p>' . _('Select the variations that should inherit this product\'s identifiers. '
            . 'Existing variation identifier data will be overwritten.') . '</p>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="clone_identifiers_to_variations">';

        echo '<table class="tablestyle">';
        echo '<thead><tr>';
        echo '<th><input type="checkbox" onchange="' . htmlspecialchars($js) . '" '
            . 'title="' . _('Select all') . '"></th>';
        echo '<th>' . _('Variation') . '</th>';
        echo '<th>' . _('Brand') . '</th>';
        echo '<th>' . _('MPN') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($variations as $var) {
            $varId   = htmlspecialchars((string)($var['stock_id'] ?? ''));
            $varDesc = htmlspecialchars((string)($var['description'] ?? $varId));

            $existing  = $this->identifiersDao->get((string)($var['stock_id'] ?? ''));
            $brand     = htmlspecialchars((string)($existing['brand'] ?? ''));
            $mpn       = htmlspecialchars((string)($existing['mpn'] ?? ''));

            echo '<tr>';
            echo '<td><input type="checkbox" name="variation_stock_ids[]" value="' . $varId . '"></td>';
            echo '<td>' . $varDesc . '</td>';
            echo '<td>' . ($brand !== '' ? $brand : '<em>' . _('—') . '</em>') . '</td>';
            echo '<td>' . ($mpn !== '' ? $mpn : '<em>' . _('—') . '</em>') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p><input type="submit" value="' . _('Copy to Selected Variations') . '"></p>';
        echo '</form>';
        echo '</fieldset>';
    }
}
