<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the "Current Attribute Assignments" fieldset
 * (the specific attribute values that determine generated variations).
 *
 * Emits no <form> — displayed content within the host items.php form.
 */
class CurrentAssignmentsSection
{
    /**
     * @param array $assignments  Assignment rows (category/value/sort).
     * @param bool  $hasCategories Whether any categories are assigned at all.
     */
    public function render(array $assignments, bool $hasCategories): void
    {
        if (!empty($assignments)) {
            echo '<h5>' . _('Current Attribute Assignments') . '</h5>';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Value') . '</th><th>' . _('Sort') . '</th></tr>';
            foreach ($assignments as $a) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)($a['category_label'] ?? '')) . '</td>';
                echo '<td>' . htmlspecialchars((string)($a['value_label'] ?? '')) . '</td>';
                echo '<td>' . (int)($a['sort_order'] ?? 0) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<p style="color:#666;font-size:11px">'
                . _('These attribute values determine the variations that will be generated.')
                . '</p>';
            return;
        }

        if ($hasCategories) {
            echo '<p style="color:#666;font-size:11px">'
                . _('Categories are assigned but no specific values are set. ')
                . _('Use the <strong>Product Attributes</strong> tab to assign values, or the')
                . ' <a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php">'
                . _('admin page') . '</a> '
                . _('to manage them.')
                . '</p>';
        }
    }
}