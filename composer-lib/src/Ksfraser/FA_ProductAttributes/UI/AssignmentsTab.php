<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class AssignmentsTab
{
    private ProductAttributesDao $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        $stockId = trim((string)($_GET['stock_id'] ?? ''));
        $categoryId = (int)($_GET['category_id'] ?? 0);
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
            start_table(TABLESTYLE2);
            $th = array(_("Category"), _("Value"), _("Sort"), "");
            table_header($th);
            foreach ($assignments as $a) {
                start_row();
                label_cell($a['category_code'] ?? '');
                label_cell($a['value'] ?? '');
                label_cell($a['sort_order'] ?? 0);
                delete_button_cell("Delete" . $a['id'], _("Delete"));
                end_row();
            }
            end_table();
        }

        echo '<br />';

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title(_("Add Assignment"));
        hidden('action', 'add_assignment');
        hidden('stock_id', $stockId);
        echo '<tr><td>' . _("Category") . ':</td><td><select name="category_id">';
        echo '<option value="0">' . _("Select category") . '</option>';
        foreach ($cats as $c) {
            $id = $c['id'] ?? 0;
            $sel = $id == $categoryId ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string)$id) . '"' . $sel . '>'
                . htmlspecialchars((string)$c['code'])
                . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><td>' . _("Value") . ':</td><td><select name="value_id">';
        echo '<option value="0">' . _("Select value") . '</option>';
        foreach ($values as $v) {
            $id = $v['id'] ?? 0;
            echo '<option value="' . htmlspecialchars((string)$id) . '">'
                . htmlspecialchars((string)$v['value'])
                . '</option>';
        }
        echo '</select></td></tr>';
        small_amount_row(_("Sort order"), 'sort_order', 0);
        end_table(1);
        submit_center('save', _("Add"));
        end_form();
    }
}