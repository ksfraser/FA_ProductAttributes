<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the "Saved Combinations" fieldset — the
 * persisted combination pool (product_variation_combos) for the current parent.
 *
 * "Generate Combinations" writes this pool; "Create Child Product" instantiates
 * rows into stock_master children. Rendering the pool here makes the generated
 * set visible immediately, even before any children exist.
 *
 * Emits no <form> — displayed content within the host items.php form.
 */
class CombinationPoolSection
{
    /**
     * @param array $combos Combo rows from CombosDao::listCombos.
     */
    public function render(array $combos): void
    {
        if (empty($combos)) {
            return;
        }

        echo '<fieldset><legend>' . _('Saved Combinations') . '</legend>';
        echo '<table class="tablestyle2">';
        echo '<tr><th>' . _('Variation') . '</th><th>' . _('Status') . '</th></tr>';
        foreach ($combos as $combo) {
            $child = (string)($combo['child_stock_id'] ?? '');
            $status = $child !== ''
                ? htmlspecialchars(sprintf(_('Created as %s'), $child), ENT_QUOTES)
                : _('Pending');
            echo '<tr>';
            echo '<td>' . htmlspecialchars((string)($combo['slug_key'] ?? '')) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</fieldset>';
    }
}