<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Renders the Attribute Categories admin tab.
 */
class CategoriesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Output the categories table and add/edit form.
     */
    public function render(): void
    {
        if (function_exists('display_notification')) {
            display_notification('CategoriesTab render() called');
        }

        $categories  = $this->dao->listCategories();
        $editCatId   = (int)($_GET['edit_category_id'] ?? 0);
        $editCat     = null;

        if (function_exists('display_notification')) {
            display_notification('Categories found: ' . count($categories));
        }

        if ($editCatId > 0) {
            foreach ($categories as $c) {
                if ((int)$c['id'] === $editCatId) {
                    $editCat = $c;
                    break;
                }
            }
        }

        echo '<h3>' . _('Attribute Categories') . '</h3>';

        if (!empty($categories)) {
            echo '<table class="tablestyle2">';
            echo '<tr>';
            echo '<th>' . _('Code') . '</th>';
            echo '<th>' . _('Label') . '</th>';
            echo '<th>' . _('Description') . '</th>';
            echo '<th>' . _('Sort (Royal Order)') . '</th>';
            echo '<th>' . _('Active') . '</th>';
            echo '<th>' . _('Actions') . '</th>';
            echo '</tr>';

            foreach ($categories as $cat) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)$cat['code']) . '</td>';
                echo '<td>' . htmlspecialchars((string)$cat['label']) . '</td>';
                echo '<td>' . htmlspecialchars((string)($cat['description'] ?? '')) . '</td>';
                echo '<td>' . (int)($cat['sort_order'] ?? 0) . '</td>';
                echo '<td>' . ($cat['active'] ? _('Yes') : _('No')) . '</td>';
                echo '<td>';
                echo '<a href="?selected_tab=categories&edit_category_id=' . (int)$cat['id'] . '">' . _('Edit') . '</a> | ';
                echo '<a href="?action=delete_category&category_id=' . (int)$cat['id'] . '" onclick="return confirm(\'' . _('Delete?') . '\')">' . _('Delete') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        // Add/Edit form
        $buttonText = $editCat ? _('Update') : _('Add Category');
        echo '<fieldset><legend>' . ($editCat ? _('Edit Category') : _('Add Category')) . '</legend>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action" value="upsert_category">';
        if ($editCat) {
            echo '<input type="hidden" name="category_id" value="' . (int)$editCat['id'] . '">';
        }
        echo '<div><label>' . _('Code') . '</label>';
        echo '<input type="text" name="code" value="' . htmlspecialchars((string)($editCat['code'] ?? '')) . '" required></div>';
        echo '<div><label>' . _('Label') . '</label>';
        echo '<input type="text" name="label" value="' . htmlspecialchars((string)($editCat['label'] ?? '')) . '" required></div>';
        echo '<div><label>' . _('Description') . '</label>';
        echo '<input type="text" name="description" value="' . htmlspecialchars((string)($editCat['description'] ?? '')) . '"></div>';
        echo '<div><label>' . _('Sort Order') . '</label>';
        echo '<input type="number" name="sort_order" value="' . (int)($editCat['sort_order'] ?? 0) . '"></div>';
        echo '<div><label>' . _('Active') . '</label>';
        $checked = (!$editCat || $editCat['active']) ? ' checked' : '';
        echo '<input type="checkbox" name="active"' . $checked . '></div>';
        echo '<button type="submit">' . $buttonText . '</button>';
        echo '</form>';
        echo '</fieldset>';
    }
}
