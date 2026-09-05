<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the read-only "Parent Product" fieldset shown
 * when the current stock item is itself a child (variation) of another product.
 *
 * Emits no <form> — this section lives inside the host items.php form.
 */
class ParentProductSection
{
    /**
     * @param array{stock_id: string, description: string}|null $parentData
     *   Parent product details, or null when the current item is not a child.
     */
    public function render(?array $parentData): void
    {
        if (!$parentData) {
            return;
        }

        echo '<fieldset><legend>' . _('Parent Product') . '</legend>';
        echo '<p>' . _('This product is a variation of') . ' <strong>'
            . htmlspecialchars($parentData['stock_id']) . '</strong> — '
            . htmlspecialchars($parentData['description'] ?? '') . '</p>';
        echo '</fieldset>';
    }
}