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
        $cats = $this->dao->listCategories();

        start_form(false);
        start_table(TABLESTYLE2);
        table_section_title(_("Product Category Assignments"));
        text_row(_("Stock ID (Parent Product)"), 'stock_id', $stockId, 20, 32);
        hidden('tab', 'assignments');
        end_table(1);
        submit_center('load', _("Load Product"));
        end_form();

        if ($stockId !== '') {
            $assignments = $this->dao->listCategoryAssignments($stockId);

            // Display current category assignments
            start_table(TABLESTYLE2);
            $th = array(_("Category"), _("Code"), _("Description"), _("Sort Order"), _("Actions"));
            table_header($th);

            if (count($assignments) > 0) {
                foreach ($assignments as $a) {
                    start_row();
                    label_cell($a['label'] ?? '');
                    label_cell($a['code'] ?? '');
                    label_cell($a['description'] ?? '');
                    $sortOrder = (int)($a['sort_order'] ?? 0);
                    $sortLabel = $sortOrder > 0 ? $sortOrder . ' - ' . RoyalOrderHelper::getRoyalOrderLabel($sortOrder) : '0';
                    label_cell($sortLabel);

                    // Actions column
                    echo '<td>';
                    echo '<a href="?tab=assignments&action=remove_category_assignment&category_id=' . $a['id'] . '&stock_id=' . urlencode($stockId) . '" onclick="return confirm(\'' . sprintf(_("Remove category '%s' from product?"), addslashes($a['label'])) . '\')">' . _("Remove") . '</a>';
                    echo '</td>';

                    end_row();
                }
            } else {
                start_row();
                label_cell(_("No category assignments found"), '', 'colspan=5');
                end_row();
            }
            end_table();

            // Generate Variations button
            echo '<br />';
            start_form(true);
            hidden('action', 'generate_variations');
            hidden('tab', 'assignments');
            hidden('stock_id', $stockId);
            submit_center('generate', _("Generate Variations"));
            end_form();

            echo '<br />';

            // Add Category Assignment form
            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title(_("Add Category Assignment"));
            hidden('action', 'add_category_assignment');
            hidden('tab', 'assignments');
            hidden('stock_id', $stockId);

            echo '<tr><td>' . _("Category") . '</td><td><select name="category_id">';
            echo '<option value="">' . _("Select category to assign") . '</option>';
            foreach ($cats as $c) {
                $cid = (int)$c['id'];
                // Check if category is already assigned
                $alreadyAssigned = false;
                foreach ($assignments as $a) {
                    if ((int)$a['id'] === $cid) {
                        $alreadyAssigned = true;
                        break;
                    }
                }
                if (!$alreadyAssigned) {
                    echo '<option value="' . htmlspecialchars((string)$cid) . '">'
                        . htmlspecialchars((string)$c['label'])
                        . ' (' . htmlspecialchars((string)$c['code']) . ')'
                        . '</option>';
                }
            }
            echo '</select></td></tr>';

            end_table(1);
            submit_center('add', _("Add Category"));
            end_form();
        }
    }
}