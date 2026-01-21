<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class CategoriesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render(): void
    {
        display_notification("CategoriesTab render() called");
        try {
            $cats = $this->dao->listCategories();
            display_notification("Categories found: " . count($cats));

            // Check for edit mode
            $editCategoryId = (int)($_GET['edit_category_id'] ?? $_POST['edit_category_id'] ?? 0);
            $editCategory = null;
            if ($editCategoryId > 0) {
                foreach ($cats as $c) {
                    if ((int)$c['id'] === $editCategoryId) {
                        $editCategory = $c;
                        break;
                    }
                }
            }

            // Always show the table
            start_table(TABLESTYLE2);
            $th = array(_("Code"), _("Label"), _("Sort"), _("Active"), _("Actions"));
            table_header($th);

            if (count($cats) > 0) {
                foreach ($cats as $c) {
                    start_row();
                    label_cell($c['code'] ?? '');
                    label_cell($c['label'] ?? '');
                    label_cell($c['sort_order'] ?? 0);
                    label_cell($c['active'] ?? 0 ? _("Yes") : _("No"));
                    
                    // Actions column
                    echo '<td>';
                    echo '<a href="?tab=categories&edit_category_id=' . $c['id'] . '">' . _("Edit") . '</a> | ';
                    echo '<a href="?tab=categories&action=delete_category&category_id=' . $c['id'] . '" onclick="return confirm(\'' . sprintf(_("Delete category '%s'?"), addslashes($c['label'])) . '\')">' . _("Delete") . '</a>';
                    echo '</td>';
                    
                    end_row();
                }
            } else {
                start_row();
                label_cell(_("No categories found"), '', 'colspan=5');
                end_row();
            }
            end_table();

            echo '<br />';

            start_form(true);
            start_table(TABLESTYLE2);
            table_section_title($editCategory ? _("Edit Category") : _("Add / Update Category"));
            text_row(_("Code"), 'code', $editCategory['code'] ?? '', 20, 64);
            text_row(_("Label"), 'label', $editCategory['label'] ?? '', 20, 64);
            text_row(_("Description"), 'description', $editCategory['description'] ?? '', 40, 255);
            small_amount_row(_("Sort order"), 'sort_order', $editCategory['sort_order'] ?? 0);
            check_row(_("Active"), 'active', $editCategory ? (bool)$editCategory['active'] : true);
            hidden('action', 'upsert_category');
            hidden('tab', 'categories');
            if ($editCategory) {
                hidden('category_id', (string)$editCategory['id']);
            }
            end_table(1);
            submit_center('save', $editCategory ? _("Update") : _("Save"));
            if ($editCategory) {
                echo '<br /><center><a href="?tab=categories">' . _("Cancel Edit") . '</a></center>';
            }
            end_form();
        } catch (Exception $e) {
            display_error("Error: " . $e->getMessage());
        }
    }
}