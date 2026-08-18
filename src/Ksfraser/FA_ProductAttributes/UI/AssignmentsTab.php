<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Renders the Product Category Assignments admin tab.
 */
class AssignmentsTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Output the product/category assignment management UI.
     */
    public function render(): void
    {
        $products = $this->dao->getProductsByType(['simple', 'variable']);

        // Determine the selected stock_id (GET takes priority over POST)
        $stockId = '';
        if (!empty($_GET['stock_id'])) {
            $stockId = (string)$_GET['stock_id'];
        } elseif (!empty($_POST['stock_id'])) {
            $stockId = (string)$_POST['stock_id'];
        }

        echo '<h3>' . _('Product Category Assignments') . '</h3>';

        // Product selector
        echo '<form method="get" action="">';
        echo '<label>' . _('Select product') . ':</label>';
        echo '<select name="stock_id" onchange="this.form.submit()">';
        echo '<option value="">' . _('-- Select product --') . '</option>';
        foreach ($products as $prod) {
            $sel = ($prod['stock_id'] === $stockId) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string)$prod['stock_id']) . '"' . $sel . '>'
                . htmlspecialchars((string)$prod['stock_id']) . ' - '
                . htmlspecialchars((string)$prod['description'])
                . '</option>';
        }
        echo '</select>';
        echo '<input type="hidden" name="selected_tab" value="assignments">';
        echo '</form>';

        if ($stockId !== '') {
            $assignments = $this->dao->listCategoryAssignments($stockId);
            $categories  = $this->dao->listCategories();

            $assignedCatIds = array_column($assignments, 'category_id');

            echo '<h4>' . sprintf(_('Category assignments for %s'), htmlspecialchars($stockId)) . '</h4>';

            echo '<form method="post" action="">';
            echo '<input type="hidden" name="action" value="update_category_assignments">';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Assigned') . '</th><th>' . _('Code') . '</th><th>' . _('Label') . '</th></tr>';
            foreach ($categories as $cat) {
                $checked = in_array((int)$cat['id'], array_map('intval', $assignedCatIds)) ? ' checked' : '';
                echo '<tr>';
                echo '<td><input type="checkbox" name="category_ids[]" value="' . (int)$cat['id'] . '"' . $checked . '></td>';
                echo '<td>' . htmlspecialchars((string)$cat['code']) . '</td>';
                echo '<td>' . htmlspecialchars((string)$cat['label']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<button type="submit">' . _('Save Assignments') . '</button>';
            echo '</form>';
        }
    }
}
