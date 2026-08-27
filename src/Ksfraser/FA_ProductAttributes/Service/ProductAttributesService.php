<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Orchestrates reading and writing product attributes
 * and renders the FA Items tab HTML.
 */
class ProductAttributesService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db)
    {
        $this->dao = $dao;
        $this->db  = $db;
    }

    /**
     * Render the Product Attributes tab HTML for a given product.
     *
     * Shows:
     * 1. Summary table of assigned attributes (Category, Value) with delete buttons
     * 2. Add Assignment section with Category + Value dropdowns + Sort Order + Add button
     *
     * @param string $stockId
     * @return string HTML
     */
    public function renderProductAttributesTab(string $stockId): string
    {
        $assignments = $stockId !== '' ? $this->dao->listAssignments($stockId) : [];
        $categories  = $this->dao->listCategories();

        $html = '<h4>' . _('Product Attributes') . '</h4>';

        $html .= '<p style="color:#666;font-size:11px">'
            . _('Assign attribute values to this product. Manage categories and values on the')
            . ' <a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php">'
            . _('Product Attributes Admin page') . '</a>.</p>';

        if (empty($assignments)) {
            $html .= '<p>' . _('No product attributes assigned.') . '</p>';
        } else {
            $html .= '<table class="tablestyle2">';
            $html .= '<tr>';
            $html .= '<th>' . _('Category') . '</th>';
            $html .= '<th>' . _('Value') . '</th>';
            $html .= '<th>' . _('Sort') . '</th>';
            $html .= '<th>' . _('Action') . '</th>';
            $html .= '</tr>';
            foreach ($assignments as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string)($row['category_label'] ?? '')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($row['value_label'] ?? '')) . '</td>';
                $html .= '<td>' . (int)($row['sort_order'] ?? 0) . '</td>';
                $html .= '<td>';
                $html .= '<input type="hidden" name="pa_delete_row_id" value="' . (int)$row['id'] . '">';
                $html .= '<input type="submit" name="pa_delete_row_submit" value="' . htmlspecialchars(_('Remove'), ENT_QUOTES) . '"'
                    . ' onclick="return confirm(\'' . htmlspecialchars(_('Remove this assignment?'), ENT_QUOTES) . '\')">';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        if (!empty($categories)) {
            $ajaxUrl = $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/ajax_get_values.php';
            $escapedAjaxUrl = htmlspecialchars($ajaxUrl, ENT_QUOTES);

            $html .= '<fieldset><legend>' . _('Add Assignment') . '</legend>';

            $html .= '<input type="hidden" name="action" value="add_pa_assignment" />';
            $html .= '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId, ENT_QUOTES, 'UTF-8') . '" />';

            $html .= '<div><label>' . _('Category') . '</label>';
            $html .= '<select name="category_id" id="pa_category_select"'
                . ' onchange="'
                . 'var v=document.getElementById(\'pa_value_select\');'
                . 'v.innerHTML=\'<option value="">Loading...</option>\';'
                . 'fetch(\'' . $escapedAjaxUrl . '?category_id=\'+this.value)'
                . '.then(function(r){return r.json()})'
                . '.then(function(d){'
                . 'var h=\'<option value="">-- Select Value --</option>\';'
                . 'for(var i=0;i<d.length;i++){h+=\'<option value="\'+d[i].id+\'">\'+d[i].value+\' (\'+d[i].slug+\')</option>\'}'
                . 'v.innerHTML=h;'
                . '})'
                . '">';
            $html .= '<option value="">' . _('-- Select Category --') . '</option>';
            foreach ($categories as $cat) {
                $html .= '<option value="' . (int)$cat['id'] . '">'
                    . htmlspecialchars((string)$cat['label']) . '</option>';
            }
            $html .= '</select></div>';

            $html .= '<div><label>' . _('Value') . '</label>';
            $html .= '<select name="value_id" id="pa_value_select">';
            $html .= '<option value="">' . _('-- Select Category First --') . '</option>';
            $html .= '</select></div>';

            $html .= '<div><label>' . _('Sort Order') . '</label>';
            $html .= '<input type="number" name="sort_order" value="0" min="0" /></div>';

            $html .= '<div style="margin-top:8px"><button type="submit">' . _('Add') . '</button></div>';
            $html .= '</fieldset>';
        }

        return $html;
    }

    /**
     * Handle adding a single assignment from the tab.
     *
     * @param string $stockId
     * @param array<string, mixed> $postData
     * @return string Success/error message
     */
    public function handleAddAssignment(string $stockId, array $postData): string
    {
        $categoryId = (int)($postData['category_id'] ?? 0);
        $valueId    = (int)($postData['value_id'] ?? 0);
        $sortOrder  = (int)($postData['sort_order'] ?? 0);

        if ($categoryId <= 0 || $valueId <= 0) {
            return _('Please select both a category and a value.');
        }

        $assignments = $this->dao->listAssignments($stockId);
        foreach ($assignments as $a) {
            if ((int)$a['category_id'] === $categoryId && (int)$a['value_id'] === $valueId) {
                return _('This category-value pair is already assigned.');
            }
        }

        $this->dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
        return _('Assignment added.');
    }

    /**
     * Handle deleting a single assignment row from the tab.
     *
     * @param int $rowId Assignment row id
     * @return string Success/error message
     */
    public function handleDeleteRow(int $rowId): string
    {
        if ($rowId <= 0) {
            return _('Invalid assignment.');
        }
        $this->dao->deleteAssignment($rowId);
        return _('Assignment removed.');
    }

    /**
     * Save product attribute assignments from POST data.
     *
     * @param string               $stockId
     * @param array<string, mixed> $postData
     */
    public function saveProductAttributes(string $stockId, array $postData): void
    {
        if (empty($postData)) {
            return;
        }

        // Sync category assignments
        if (array_key_exists('assigned_categories', $postData)) {
            $desired  = array_map('intval', (array)$postData['assigned_categories']);
            $current  = $this->dao->getAssignedCategoriesForProduct($stockId);
            $currentIds = array_map(function ($r) { return (int)$r['id']; }, $current);

            foreach ($currentIds as $cid) {
                if (!in_array($cid, $desired, true)) {
                    $this->dao->removeCategoryAssignment($stockId, $cid);
                }
            }
            foreach ($desired as $cid) {
                if (!in_array($cid, $currentIds, true)) {
                    $this->dao->addCategoryAssignment($stockId, $cid);
                }
            }
        }

        // Sync individual value assignments
        if (array_key_exists('attribute_values', $postData)) {
            // Clear existing individual assignments
            $existing = $this->dao->listAssignments($stockId);
            foreach ($existing as $row) {
                $this->dao->deleteAssignment((int)$row['id']);
            }

            // Add new assignments
            foreach ((array)$postData['attribute_values'] as $catId => $valueIds) {
                foreach ((array)$valueIds as $vid) {
                    $this->dao->addAssignment($stockId, (int)$catId, (int)$vid);
                }
            }
        }
    }

    /**
     * Delete all attribute assignments for a product.
     *
     * @param string $stockId
     */
    public function deleteProductAttributes(string $stockId): void
    {
        $existing = $this->dao->listAssignments($stockId);
        foreach ($existing as $row) {
            $this->dao->deleteAssignment((int)$row['id']);
        }
    }
}
