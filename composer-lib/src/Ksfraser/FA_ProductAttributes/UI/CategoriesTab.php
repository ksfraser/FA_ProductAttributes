<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class CategoriesTab
{
    private ProductAttributesDao $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        try {
            $cats = $this->dao->listCategories();
            display_notification("Categories found: " . count($cats));

            // Always show the table
            start_table(TABLESTYLE2);
            $th = array(_("Code"), _("Label"), _("Description"), _("Sort"), _("Active"), "");
            table_header($th);

            foreach ($cats as $c) {
                start_row();
                label_cell($c['code'] ?? '');
                label_cell($c['label'] ?? '');
                label_cell($c['description'] ?? '');
                label_cell($c['sort_order'] ?? 0);
                label_cell(($c['active'] ?? 0) ? _("Yes") : _("No"));
                edit_button_cell("Edit" . $c['id'], _("Edit"));
                end_row();
            }

            end_table();

            echo '<br />';

            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title(_("Add / Update Category"));
            hidden('action', 'upsert_category');
            text_row(_("Code"), 'code', '', 20, 32);
            text_row(_("Label"), 'label', '', 20, 64);
            textarea_row(_("Description"), 'description', '', 30, 3);
            small_amount_row(_("Sort order"), 'sort_order', 0);
            check_row(_("Active"), 'active', true);
            end_table(1);
            submit_center('save', _("Save"));
            end_form();
        } catch (Exception $e) {
            display_error("Error: " . $e->getMessage());
        }
    }
}