<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class ValuesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $cats = $this->dao->listCategories();
        if ($categoryId === 0 && count($cats) > 0) {
            $categoryId = (int)$cats[0]['id'];
        }

        start_form(false);
        start_table(TABLESTYLE2);
        table_section_title(_("Category"));
        echo '<tr><td>' . _("Category") . '</td><td><select name="category_id" onchange="this.form.submit()">';
        foreach ($cats as $c) {
            $id = (int)$c['id'];
            $sel = $id === $categoryId ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string)$id) . '"' . $sel . '>'
                . htmlspecialchars((string)$c['code'])
                . '</option>';
        }
        echo '</select></td></tr>';
        hidden('tab', 'values');
        end_table(1);
        end_form();

        $values = $categoryId ? $this->dao->listValues($categoryId) : [];

        // Display values table
        start_table(TABLESTYLE2);
        $th = array(_("Value"), _("Slug"), _("Sort"), _("Active"));
        table_header($th);

        if (count($values) > 0) {
            foreach ($values as $v) {
                start_row();
                label_cell($v['value'] ?? '');
                label_cell($v['slug'] ?? '');
                label_cell($v['sort_order'] ?? 0);
                label_cell($v['active'] ?? 0 ? _("Yes") : _("No"));
                end_row();
            }
        } else {
            start_row();
            label_cell(_("No values found"), '', 'colspan=4');
            end_row();
        }
        end_table();

        echo '<br />';

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title(_("Add / Update Value"));
        hidden('action', 'upsert_value');
        hidden('category_id', (string)$categoryId);
        hidden('tab', 'values');
        text_row(_("Value"), 'value', '', 20, 64);
        text_row(_("Slug"), 'slug', '', 20, 32);
        small_amount_row(_("Sort order"), 'sort_order', 0);
        check_row(_("Active"), 'active', true);
        end_table(1);
        submit_center('save', _("Save"));
        end_form();
    }
}