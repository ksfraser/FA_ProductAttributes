<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single responsibility: render the "Assigned Categories" fieldset for the
 * current stock item, display-only.
 *
 * Category assignment is managed on the Product Attributes admin page
 * (public/index.php assignments sub-tab) and via the Add Assignment box; the
 * item tabs (Variations / Product Attributes) only show the result. Emits no
 * <form>, no action buttons and no assign/unassign controls.
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
     * @param bool   $isChild            Whether the item is a variation (read-only).
     * @return void
     */
    public function render(string $stockId, array $assignedCategories, bool $isChild = false): void
    {
        echo '<fieldset><legend>' . _('Assigned Categories') . '</legend>';

        if (empty($assignedCategories)) {
            if ($isChild) {
                echo '<p>' . _('No categories assigned. Categories are managed on the parent product.') . '</p>';
            } else {
                echo '<p>' . _('No categories assigned.') . '</p>';
            }
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Active Values') . '</th></tr>';
            foreach ($assignedCategories as $category) {
                $activeValues = $this->coreDao->listActiveValues((int)$category['id']);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($category['label']) . '</td>';
                echo '<td>' . count($activeValues) . ' ' . _('values') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            if ($isChild) {
                echo '<p style="color:#666">' . _('Category assignments are inherited from parent product.') . '</p>';
            }
        }

        echo '</fieldset>';
    }
}