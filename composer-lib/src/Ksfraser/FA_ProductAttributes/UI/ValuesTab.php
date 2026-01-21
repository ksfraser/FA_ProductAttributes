<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class ValuesTab
{
    private ProductAttributesDao $dao;

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

        $table = \Ksfraser\HTML\Elements\HtmlTable::createFaTable(2); // TABLESTYLE2
        $table->addNested(TableBuilder::createHeaderRow(['Value', 'Slug', 'Sort', 'Active']));
        foreach ($values as $v) {
            $table->addNested(TableBuilder::createDataRow([
                (string)($v['value'] ?? ''),
                (string)($v['slug'] ?? ''),
                (string)($v['sort_order'] ?? 0),
                (string)($v['active'] ?? 0 ? 'Yes' : 'No'),
            ]));
        }
        $table->toHtml();

        echo '<br />';

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title(_("Add / Update Value"));
        hidden('action', 'upsert_value');
        hidden('category_id', (string)$categoryId);
        text_row(_("Value"), 'value', '', 20, 64);
        text_row(_("Slug"), 'slug', '', 20, 32);
        small_amount_row(_("Sort order"), 'sort_order', 0);
        check_row(_("Active"), 'active', true);
        end_table(1);
        submit_center('save', _("Save"));
        end_form();
    }
}