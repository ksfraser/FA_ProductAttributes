<?php
display_notification("DEBUG: CategoriesTab.php file loaded");

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class CategoriesTab
{
    private ProductAttributesDao $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        display_notification("CategoriesTab constructor called");
        $this->dao = $dao;
    }

    public function render(): void
    {
        display_notification("CategoriesTab render() called");
        try {
            $cats = $this->dao->listCategories();
            display_notification("Categories found: " . count($cats));

            // Always show the table
            start_table(TABLESTYLE2);
            $th = array(_("Code"), _("Label"), _("Sort"), _("Active"));
            table_header($th);

            if (count($cats) > 0) {
                foreach ($cats as $c) {
                    start_row();
                    label_cell($c['code'] ?? '');
                    label_cell($c['label'] ?? '');
                    label_cell($c['sort_order'] ?? 0);
                    label_cell($c['active'] ?? 0 ? _("Yes") : _("No"));
                    end_row();
                }
            } else {
                start_row();
                label_cell(_("No categories found"), '', 'colspan=4');
                end_row();
            }
            end_table();

            echo '<br />';

            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title(_("Add / Update Category"));
            text_row(_("Code"), 'code', '', 20, 64);
            text_row(_("Label"), 'label', '', 20, 64);
            text_row(_("Description"), 'description', '', 40, 255);
            small_amount_row(_("Sort order"), 'sort_order', 0);
            check_row(_("Active"), 'active', true);
            hidden('action', 'upsert_category');
            end_table(1);
            submit_center('save', _("Save"));
            end_form();
        } catch (Exception $e) {
            display_error("Error: " . $e->getMessage());
        }
    }
}