<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single responsibility: render the "Assigned Categories" fieldset for the
 * current stock item.
 *
 * Shows the assigned categories with their active-value counts and per-row
 * actions (Manage Values / Remove), plus an assign-category dropdown when the
 * item is a parent (not itself a variation) and unassigned categories remain.
 *
 * Emits no <form> — the action inputs participate in the host items.php form.
 */
class AssignedCategoriesSection
{
    /** @var ProductAttributesDao */
    private $coreDao;

    public function __construct(ProductAttributesDao $coreDao)
    {
        $this->coreDao = $coreDao;
    }

    /**
     * @param string $stockId            Current item stock id.
     * @param array  $assignedCategories Category rows filtered to assigned ones.
     * @param array  $allCategories       All category rows.
     * @param bool   $isChild            Whether the item is a variation (read-only).
     */
    public function render(string $stockId, array $assignedCategories, array $allCategories, bool $isChild): void
    {
        echo '<fieldset><legend>' . _('Assigned Categories') . '</legend>';

        if (empty($assignedCategories)) {
            if ($isChild) {
                echo '<p>' . _('No categories assigned. Categories are managed on the parent product.') . '</p>';
            } else {
                echo '<p>' . _('No categories assigned. Use the dropdown below to assign one.') . '</p>';
            }
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Active Values') . '</th><th>' . _('Actions') . '</th></tr>';
            foreach ($assignedCategories as $category) {
                $activeValues = $this->coreDao->listActiveValues((int)$category['id']);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($category['label']) . '</td>';
                echo '<td>' . count($activeValues) . ' ' . _('values') . '</td>';
                echo '<td>';
                if (!$isChild) {
                    echo '<a href="' . $GLOBALS['path_to_root']
                        . '/modules/FA_ProductAttributes/public/index.php?tab=values&category_id='
                        . (int)$category['id'] . '">' . _('Manage Values') . '</a> ';
                    if ($stockId !== '') {
                        echo '<input type="hidden" name="unassign_category_id" value="' . (int)$category['id'] . '">';
                        if (function_exists('submit_js_confirm')) {
                            submit_js_confirm('unassign_category_submit', _('Remove this category from the product?'));
                        }
                        echo '<button class="ajaxsubmit" type="submit" formnovalidate'
                            . ' name="unassign_category_submit" value="' . (int)$category['id'] . '">'
                            . '<span>' . htmlspecialchars(_('Remove'), ENT_QUOTES) . '</span></button>';
                    }
                } else {
                    echo '<span style="color:#666">' . _('inherited from parent') . '</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        $unassigned = $this->unassigned($assignedCategories, $allCategories);
        if ($stockId !== '' && !$isChild && !empty($allCategories) && !empty($unassigned)) {
            echo '<div style="margin-top:8px">';
            echo '<select name="assign_category_id">';
            echo '<option value="0">' . _('-- Select category --') . '</option>';
            foreach ($unassigned as $cat) {
                echo '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars($cat['label']) . '</option>';
            }
            echo '</select> ';
            echo '<button class="ajaxsubmit" type="submit" formnovalidate name="assign_category_submit"'
                . ' value="1">' . '<span>' . _('Assign Category') . '</span></button>';
            echo '</div>';
        }

        echo '</fieldset>';
    }

    /**
     * Categories not already assigned to the item.
     *
     * @param array $assignedCategories Assigned category rows.
     * @param array $allCategories       All category rows.
     * @return array<int, array<string, mixed>>
     */
    private function unassigned(array $assignedCategories, array $allCategories): array
    {
        $assignedIds = array_column($assignedCategories, 'id');

        return array_values(array_filter($allCategories, function ($cat) use ($assignedIds) {
            return !in_array($cat['id'], $assignedIds, true);
        }));
    }
}