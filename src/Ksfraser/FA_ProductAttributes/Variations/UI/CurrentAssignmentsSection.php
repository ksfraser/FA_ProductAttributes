<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the "Current Attribute Assignments" fieldset
 * (the specific attribute values that determine generated variations).
 *
 * Optionally renders a per-row Remove control (shared by the Product
 * Attributes tab). Emits no <form> — displayed content within the host
 * items.php form.
 */
class CurrentAssignmentsSection
{
    /**
     * @param array  $assignments   Assignment rows (category/value/sort).
     * @param bool   $hasCategories Whether any categories are assigned at all.
     * @param bool   $enableRemove  Whether to render per-row Remove buttons.
     */
    public function render(array $assignments, bool $hasCategories, bool $enableRemove = false): void
    {
        if (!empty($assignments)) {
            echo '<h5>' . _('Current Attribute Assignments') . '</h5>';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Value') . '</th><th>' . _('Sort') . '</th>';
            if ($enableRemove) {
                echo '<th>' . _('Action') . '</th>';
            }
            echo '</tr>';
            foreach ($assignments as $a) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)($a['category_label'] ?? '')) . '</td>';
                echo '<td>' . htmlspecialchars((string)($a['value_label'] ?? '')) . '</td>';
                echo '<td>' . (int)($a['sort_order'] ?? 0) . '</td>';
                if ($enableRemove) {
                    echo '<td>';
                    echo '<input type="submit" name="pa_delete_row_' . (int)$a['id'] . '" value="'
                        . htmlspecialchars(_('Remove'), ENT_QUOTES) . '"'
                        . ' onclick="return confirm(\'' . htmlspecialchars(_('Remove this assignment?'), ENT_QUOTES) . '\')">';
                    echo '</td>';
                }
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
            return;
        }

        echo '<p style="color:#666;font-size:11px">' . _('No product attributes assigned.') . '</p>';
    }
}