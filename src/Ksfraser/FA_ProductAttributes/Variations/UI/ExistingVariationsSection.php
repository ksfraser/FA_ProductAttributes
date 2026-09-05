<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the "Existing Variations" fieldset — the
 * generated child products of the current parent.
 *
 * Emits no <form> — displayed content within the host items.php form.
 */
class ExistingVariationsSection
{
    /**
     * @param array $variations  Variation rows (stock_id/description).
     */
    public function render(array $variations): void
    {
        if (empty($variations)) {
            return;
        }

        echo '<fieldset><legend>' . _('Existing Variations') . '</legend>';
        echo '<table class="tablestyle2">';
        echo '<tr><th>' . _('Stock ID') . '</th><th>' . _('Description') . '</th></tr>';
        foreach ($variations as $v) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($v['stock_id']) . '</td>';
            echo '<td>' . htmlspecialchars($v['description'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</fieldset>';
    }
}