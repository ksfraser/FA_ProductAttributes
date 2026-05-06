<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Renders the Attribute Values admin tab.
 */
class ValuesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Output the values table and add/edit form for a given category.
     */
    public function render(): void
    {
        $categories = $this->dao->listCategories();

        $categoryId = (int)($_GET['category_id'] ?? 0);
        if ($categoryId === 0 && !empty($categories)) {
            $categoryId = (int)$categories[0]['id'];
        }

        $editValueId = (int)($_GET['edit_value_id'] ?? 0);

        $values    = ($categoryId > 0) ? $this->dao->listValues((string)$categoryId) : [];
        $editValue = null;

        if ($editValueId > 0) {
            // Reload to find the item being edited (still within the same category)
            $allValues = ($categoryId > 0) ? $this->dao->listValues((string)$categoryId) : [];
            foreach ($allValues as $v) {
                if ((int)$v['id'] === $editValueId) {
                    $editValue = $v;
                    break;
                }
            }
        }

        echo '<h3>' . _('Attribute Values') . '</h3>';

        // Category selector
        echo '<form method="get" action="">';
        echo '<label>' . _('Category') . ':</label>';
        echo '<select name="category_id" onchange="this.form.submit()">';
        foreach ($categories as $cat) {
            $sel = ((int)$cat['id'] === $categoryId) ? ' selected' : '';
            echo '<option value="' . (int)$cat['id'] . '"' . $sel . '>' . htmlspecialchars((string)$cat['code']) . ' - ' . htmlspecialchars((string)$cat['label']) . '</option>';
        }
        echo '</select>';
        echo '<input type="hidden" name="selected_tab" value="values">';
        echo '</form>';

        if (!empty($values)) {
            echo '<table class="tablestyle2">';
            echo '<tr>';
            echo '<th>' . _('Value') . '</th>';
            echo '<th>' . _('Slug') . '</th>';
            echo '<th>' . _('Sort Order') . '</th>';
            echo '<th>' . _('Active') . '</th>';
            echo '<th>' . _('Actions') . '</th>';
            echo '</tr>';

            foreach ($values as $val) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)$val['value']) . '</td>';
                echo '<td>' . htmlspecialchars((string)$val['slug']) . '</td>';
                echo '<td>' . (int)($val['sort_order'] ?? 0) . '</td>';
                echo '<td>' . ($val['active'] ? _('Yes') : _('No')) . '</td>';
                echo '<td>';
                echo '<a href="?selected_tab=values&category_id=' . $categoryId . '&edit_value_id=' . (int)$val['id'] . '">' . _('Edit') . '</a> | ';
                echo '<a href="?action=delete_value&value_id=' . (int)$val['id'] . '" onclick="return confirm(\'' . _('Delete?') . '\')">' . _('Delete') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        // Add/Edit form
        echo '<fieldset><legend>' . ($editValue ? _('Edit Value') : _('Add Value')) . '</legend>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action" value="upsert_value">';
        echo '<input type="hidden" name="category_id" value="' . $categoryId . '">';
        if ($editValue) {
            echo '<input type="hidden" name="value_id" value="' . (int)$editValue['id'] . '">';
        }
        echo '<div><label>' . _('Value') . '</label>';
        echo '<input type="text" name="value" value="' . htmlspecialchars((string)($editValue['value'] ?? '')) . '" required></div>';
        echo '<div><label>' . _('Slug') . '</label>';
        echo '<input type="text" name="slug" value="' . htmlspecialchars((string)($editValue['slug'] ?? '')) . '" required></div>';
        echo '<div><label>' . _('Sort Order') . '</label>';
        echo '<input type="number" name="sort_order" value="' . (int)($editValue['sort_order'] ?? 0) . '"></div>';
        echo '<div><label>' . _('Active') . '</label>';
        $checked = (!$editValue || $editValue['active']) ? ' checked' : '';
        echo '<input type="checkbox" name="active"' . $checked . '></div>';
        echo '<button type="submit">' . ($editValue ? _('Update') : _('Add Value')) . '</button>';
        echo '</form>';
        echo '</fieldset>';
    }
}
