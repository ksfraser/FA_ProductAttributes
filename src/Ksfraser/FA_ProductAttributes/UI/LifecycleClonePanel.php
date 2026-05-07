<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Single Responsibility: Renders the "Copy Lifecycle to Variations" panel.
 *
 * Shown on the Lifecycle tab when the current product is a parent.
 * Allows bulk-copying status flags and dates to selected variations.
 *
 * POSTs action=clone_lifecycle_to_variations with:
 *   stock_id              string     Parent stock ID
 *   variation_stock_ids[] string[]   Selected variation IDs
 */
class LifecycleClonePanel
{
    /** @var ProductLifecycleDao */
    private $lifecycleDao;

    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(ProductLifecycleDao $lifecycleDao, VariationsDao $variationsDao)
    {
        $this->lifecycleDao  = $lifecycleDao;
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
        echo '<legend>' . _('Copy Lifecycle to Variations') . '</legend>';
        echo '<p>' . _('Select the variations that should inherit this product\'s lifecycle status. '
            . 'Existing variation lifecycle data will be overwritten.') . '</p>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="clone_lifecycle_to_variations">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<table class="tablestyle">';
        echo '<thead><tr>';
        echo '<th><input type="checkbox" onchange="' . htmlspecialchars($js) . '" '
            . 'title="' . _('Select all') . '"></th>';
        echo '<th>' . _('Variation') . '</th>';
        echo '<th>' . _('Status') . '</th>';
        echo '<th>' . _('Flags') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($variations as $var) {
            $varId   = htmlspecialchars((string)($var['stock_id'] ?? ''));
            $varDesc = htmlspecialchars((string)($var['description'] ?? $varId));

            $existing = $this->lifecycleDao->get((string)($var['stock_id'] ?? ''));
            $status   = htmlspecialchars((string)($existing['status'] ?? ''));

            $activeFlags = [];
            if (!empty($existing['is_special_order']))       { $activeFlags[] = _('Special Order'); }
            if (!empty($existing['is_clearance']))           { $activeFlags[] = _('Clearance'); }
            if (!empty($existing['is_featured']))            { $activeFlags[] = _('Featured'); }
            if (!empty($existing['is_new_arrival']))         { $activeFlags[] = _('New Arrival'); }
            $flagSummary = !empty($activeFlags)
                ? htmlspecialchars(implode(', ', $activeFlags))
                : '<em>' . _('none') . '</em>';

            echo '<tr>';
            echo '<td><input type="checkbox" name="variation_stock_ids[]" value="' . $varId . '"></td>';
            echo '<td>' . $varDesc . '</td>';
            echo '<td>' . ($status !== '' ? $status : '<em>' . _('—') . '</em>') . '</td>';
            echo '<td>' . $flagSummary . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p><input type="submit" value="' . _('Copy to Selected Variations') . '"></p>';
        echo '</form>';
        echo '</fieldset>';
    }
}
