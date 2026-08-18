<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Single Responsibility: Renders the "Copy Shipping Attributes to Variations"
 * selection panel.
 *
 * Displayed on the Shipping tab whenever the current product is a parent (has
 * at least one variation).  Each variation is shown with a checkbox and a
 * brief summary of its current shipping data so the user can decide which ones
 * should inherit the parent's settings (useful when, e.g., clothing sizes share
 * the same hazmat classification but differ in weight/dimensions).
 *
 * The form POSTs to action=clone_shipping_to_variations with:
 *   stock_id              string     The parent product stock ID
 *   variation_stock_ids[] string[]   IDs of the selected variations
 */
class ShippingClonePanel
{
    /** @var ShippingAttributesDao */
    private $shippingDao;

    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(ShippingAttributesDao $shippingDao, VariationsDao $variationsDao)
    {
        $this->shippingDao   = $shippingDao;
        $this->variationsDao = $variationsDao;
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
        echo '<legend>' . _('Copy Shipping Attributes to Variations') . '</legend>';
        echo '<p>' . _('Select the variations that should inherit this product\'s shipping '
            . 'attributes. Existing variation-specific shipping data will be overwritten.') . '</p>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action" value="clone_shipping_to_variations">';

        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<th><input type="checkbox" id="clone-select-all" onclick="' . htmlspecialchars($js) . '"> '
            . _('All') . '</th>';
        echo '<th>' . _('Variation') . '</th>';
        echo '<th>' . _('Current Shipping') . '</th>';
        echo '</tr>';

        foreach ($variations as $varRow) {
            $varId       = (string)($varRow['stock_id'] ?? '');
            $varShipping = ($varId !== '') ? $this->shippingDao->get($varId) : null;
            $summary     = ($varShipping !== null)
                ? htmlspecialchars($this->shippingSummary($varShipping))
                : _('None');

            echo '<tr>';
            echo '<td><input type="checkbox" name="variation_stock_ids[]" value="'
                . htmlspecialchars($varId) . '"></td>';
            echo '<td>' . htmlspecialchars($varId) . '</td>';
            echo '<td>' . $summary . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '<p><button type="submit" class="btn btn-default">'
            . _('Copy to Selected Variations') . '</button></p>';
        echo '</form>';
        echo '</fieldset>';
    }

    /**
     * Build a short human-readable summary of shipping attributes for display.
     *
     * @param array<string, mixed> $data
     */
    private function shippingSummary(array $data): string
    {
        $parts = [];

        $weight     = $data['weight'] ?? null;
        $weightUnit = (string)($data['weight_unit'] ?? 'kg');
        if ($weight !== null && $weight !== '') {
            $parts[] = ((string)(float)$weight) . ' ' . $weightUnit;
        }

        $l  = $data['length'] ?? null;
        $w  = $data['width'] ?? null;
        $h  = $data['height'] ?? null;
        $du = (string)($data['dim_unit'] ?? 'cm');
        if ($l !== null && $l !== '' && $w !== null && $w !== '' && $h !== null && $h !== '') {
            $parts[] = ((string)(float)$l) . 'x' . ((string)(float)$w) . 'x' . ((string)(float)$h)
                . ' ' . $du;
        }

        if ((bool)($data['is_hazardous'] ?? false)) {
            $parts[] = _('Hazmat');
        }

        return !empty($parts) ? implode(', ', $parts) : _('Saved');
    }
}
