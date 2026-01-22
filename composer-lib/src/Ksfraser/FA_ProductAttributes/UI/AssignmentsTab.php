<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

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
        $categoryId = (int)($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
        $cats = $this->dao->listCategories();
        if ($categoryId === 0 && count($cats) > 0) {
            $categoryId = (int)$cats[0]['id'];
        }
        $values = $categoryId ? $this->dao->listValues($categoryId) : [];

        start_form(false);
        start_table(TABLESTYLE2);
        table_section_title(_("Assignments"));
        text_row(_("Stock ID"), 'stock_id', $stockId, 20, 32);
        echo '<tr><td>' . _("Category") . ':</td><td><select name="category_id" onchange="this.form.submit()">';
        echo '<option value="0">' . _("Select category") . '</option>';
        foreach ($cats as $c) {
            $id = $c['id'] ?? 0;
            $sel = $id == $categoryId ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string)$id) . '"' . $sel . '>'
                . htmlspecialchars((string)$c['code'])
                . '</option>';
        }
        echo '</select></td></tr>';
        hidden('tab', 'assignments');
        end_table(1);
        end_form();

        if ($stockId !== '') {
            $assignments = $this->dao->listAssignments($stockId);

            // Display assignments table
            start_table(TABLESTYLE2);
            $th = array(_("Category"), _("Value"), _("Slug"), _("Sort"), _("Actions"));
            table_header($th);

            if (count($assignments) > 0) {
                foreach ($assignments as $a) {
                    start_row();
                    label_cell($a['category_code'] ?? '');
                    label_cell($a['value_label'] ?? '');
                    label_cell($a['value_slug'] ?? '');
                    label_cell($a['sort_order'] ?? 0);
                    
                    // Actions column
                    echo '<td>';
                    echo '<a href="?tab=assignments&action=delete_assignment&assignment_id=' . $a['id'] . '&stock_id=' . urlencode($stockId) . '" onclick="return confirm(\'' . sprintf(_("Remove assignment '%s - %s' from product?"), addslashes($a['category_code']), addslashes($a['value_label'])) . '\')">' . _("Delete") . '</a>';
                    echo '</td>';
                    
                    end_row();
                }
            } else {
                start_row();
                label_cell(_("No assignments found"), '', 'colspan=5');
                end_row();
            }
            end_table();

            echo '<br />';

            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title(_("Add Assignment"));
            hidden('action', 'add_assignment');
            hidden('tab', 'assignments');
            hidden('stock_id', $stockId);
            hidden('category_id', (string)$categoryId);

            echo '<tr><td>' . _("Value") . '</td><td><select name="value_id">';
            foreach ($values as $v) {
                $vid = (int)$v['id'];
                echo '<option value="' . htmlspecialchars((string)$vid) . '">'
                    . htmlspecialchars((string)$v['value'])
                    . ' (' . htmlspecialchars((string)$v['slug']) . ')'
                    . '</option>';
            }
            echo '</select></td></tr>';

            small_amount_row(_("Sort order"), 'sort_order', 0);
            end_table(1);
            submit_center('save', _("Save"));
            end_form();
        }
    }
}