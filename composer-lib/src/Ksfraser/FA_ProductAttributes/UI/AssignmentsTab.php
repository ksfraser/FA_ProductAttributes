<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\RoyalOrderHelper;

class AssignmentsTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        $stockId = trim((string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? ''));

        // Get all products that are Simple or Variable (not Variation)
        $products = $this->dao->getProductsByType(['simple', 'variable']);

        start_form(false);
        start_table(TABLESTYLE2);
        table_section_title(_("Product Category Assignments"));

        // Product selection dropdown
        echo '<tr><td>' . _("Product") . '</td><td>';
        echo '<select name="stock_id" onchange="this.form.submit()">';
        echo '<option value="">' . _("Select product") . '</option>';
        foreach ($products as $product) {
            $selected = ($product['stock_id'] === $stockId) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($product['stock_id']) . '" ' . $selected . '>'
                . htmlspecialchars($product['stock_id'] . ' - ' . $product['description'])
                . '</option>';
        }
        echo '</select></td></tr>';

        hidden('tab', 'assignments');
        end_table(1);
        end_form();

        if ($stockId !== '') {
            $assignments = $this->dao->listCategoryAssignments($stockId);
            $assignedCategoryIds = array_column($assignments, 'id');
            $allCategories = $this->dao->listCategories();

            // Category assignment checkboxes
            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title(_("Category Assignments for: ") . htmlspecialchars($stockId));

            hidden('action', 'update_category_assignments');
            hidden('tab', 'assignments');
            hidden('stock_id', $stockId);

            echo '<tr><td colspan="2">';
            echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';

            foreach ($allCategories as $category) {
                $checked = in_array($category['id'], $assignedCategoryIds) ? 'checked' : '';
                echo '<label style="display: block; margin-bottom: 5px;">';
                echo '<input type="checkbox" name="category_ids[]" value="' . $category['id'] . '" ' . $checked . '> ';
                echo htmlspecialchars($category['label'] . ' (' . $category['code'] . ')');
                echo '</label>';
            }

            echo '</div></td></tr>';

            end_table(1);

            // Buttons - allow plugins to extend
            echo '<div style="text-align: center; margin: 10px;">';
            submit('update', _("Update Assignments"), true, '', 'default');

            // Hook: fa_product_attributes_assignments_buttons
            // Allows plugins to add additional buttons after the Update Assignments button
            if (function_exists('fa_hooks')) {
                $hooks = fa_hooks();
                $additionalButtons = $hooks->call_hook('fa_product_attributes_assignments_buttons', [
                    'stock_id' => $stockId,
                    'assignments' => $assignments
                ]);
                if (!empty($additionalButtons)) {
                    echo ' ';
                    echo implode(' ', $additionalButtons);
                }
            }

            echo '</div>';

            end_form();

            // Display current assignments
            if (!empty($assignments)) {
                echo '<br />';
                start_table(TABLESTYLE2);
                table_section_title(_("Current Category Assignments"));
                $th = array(_("Category"), _("Code"), _("Description"), _("Sort Order"));
                table_header($th);

                foreach ($assignments as $a) {
                    start_row();
                    label_cell($a['label'] ?? '');
                    label_cell($a['code'] ?? '');
                    label_cell($a['description'] ?? '');
                    $sortOrder = (int)($a['sort_order'] ?? 0);
                    $sortLabel = $sortOrder > 0 ? $sortOrder . ' - ' . RoyalOrderHelper::getRoyalOrderLabel($sortOrder) : '0';
                    label_cell($sortLabel);
                    end_row();
                }
                end_table();
            }

            // Hook: fa_product_attributes_assignments_after_table
            // Allows plugins to add content after the assignments table
            if (function_exists('fa_hooks')) {
                $hooks = fa_hooks();
                $additionalContent = $hooks->call_hook('fa_product_attributes_assignments_after_table', [
                    'stock_id' => $stockId,
                    'assignments' => $assignments
                ]);
                if (!empty($additionalContent)) {
                    echo implode('', $additionalContent);
                }
            }
        }
    }
}