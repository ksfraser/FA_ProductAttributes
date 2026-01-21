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
        $categoryId = (int)($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
        $cats = $this->dao->listCategories();
        if ($categoryId === 0 && count($cats) > 0) {
            $categoryId = (int)$cats[0]['id'];
        }

        // Check for edit mode
        $editValueId = (int)($_GET['edit_value_id'] ?? $_POST['edit_value_id'] ?? 0);
        $editValue = null;
        if ($editValueId > 0) {
            $values = $this->dao->listValues($categoryId);
            foreach ($values as $v) {
                if ((int)$v['id'] === $editValueId) {
                    $editValue = $v;
                    break;
                }
            }
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
        $th = array(_("Value"), _("Slug"), _("Sort"), _("Active"), _("Actions"));
        table_header($th);

        if (count($values) > 0) {
            foreach ($values as $v) {
                start_row();
                label_cell($v['value'] ?? '');
                label_cell($v['slug'] ?? '');
                label_cell($v['sort_order'] ?? 0);
                label_cell($v['active'] ?? 0 ? _("Yes") : _("No"));
                
                // Actions column
                echo '<td>';
                echo '<a href="?tab=values&category_id=' . $categoryId . '&edit_value_id=' . $v['id'] . '">' . _("Edit") . '</a> | ';
                echo '<a href="javascript:void(0)" onclick="if(confirm(\'' . sprintf(_("Delete value '%s'?"), addslashes($v['value'])) . '\')) { ';
                echo 'document.getElementById(\'delete_value_form_' . $v['id'] . '\').submit(); }">' . _("Delete") . '</a>';
                echo '<form id="delete_value_form_' . $v['id'] . '" method="post" style="display:none">';
                echo '<input type="hidden" name="action" value="delete_value">';
                echo '<input type="hidden" name="tab" value="values">';
                echo '<input type="hidden" name="category_id" value="' . $categoryId . '">';
                echo '<input type="hidden" name="value_id" value="' . $v['id'] . '">';
                echo '</form>';
                echo '</td>';
                
                end_row();
            }
        } else {
            start_row();
            label_cell(_("No values found"), '', 'colspan=5');
            end_row();
        }
        end_table();

        echo '<br />';

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title($editValue ? _("Edit Value") : _("Add / Update Value"));
        hidden('action', 'upsert_value');
        hidden('category_id', (string)$categoryId);
        hidden('tab', 'values');
        if ($editValue) {
            hidden('value_id', (string)$editValue['id']);
        }
        text_row(_("Value"), 'value', $editValue['value'] ?? '', 20, 64);
        text_row(_("Slug"), 'slug', $editValue['slug'] ?? '', 20, 32);
        small_amount_row(_("Sort order"), 'sort_order', $editValue['sort_order'] ?? 0);
        check_row(_("Active"), 'active', $editValue ? (bool)$editValue['active'] : true);
        end_table(1);
        submit_center('save', $editValue ? _("Update") : _("Save"));
        if ($editValue) {
            echo '<br /><center><a href="?tab=values&category_id=' . $categoryId . '">' . _("Cancel Edit") . '</a></center>';
        }
        end_form();
    }
}