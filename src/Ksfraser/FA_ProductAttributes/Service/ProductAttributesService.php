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
     * @param string $stockId
     * @return string HTML
     */
    public function renderProductAttributesTab(string $stockId): string
    {
        $assignments         = $this->dao->listAssignments($stockId);
        $assignedCategories  = $this->dao->getAssignedCategoriesForProduct($stockId);

        $html  = '<h4>' . _('Product Attributes') . '</h4>';

        if (empty($assignments)) {
            $html .= '<p>' . _('No product attributes assigned') . '</p>';
        } else {
            $html .= '<table class="tablestyle2">';
            $html .= '<tr><th>' . _('Category') . '</th><th>' . _('Value') . '</th></tr>';
            foreach ($assignments as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string)($row['category_label'] ?? '')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($row['value_label'] ?? '')) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        // Category assignment multi-select (first call to listCategories)
        $categories = $this->dao->listCategories();
        if (!empty($categories)) {
            $html .= '<h5>' . _('Assign Category') . '</h5>';
            $html .= '<select name="assigned_categories[]" multiple>';
            foreach ($categories as $cat) {
                $sel   = '';
                foreach ($assignedCategories as $ac) {
                    if ((int)$ac['id'] === (int)$cat['id']) {
                        $sel = ' selected';
                        break;
                    }
                }
                $html .= '<option value="' . (int)$cat['id'] . '"' . $sel . '>';
                $html .= htmlspecialchars((string)$cat['label']);
                $html .= '</option>';
            }
            $html .= '</select>';
        }

        // Per-assigned-category value selects
        foreach ($assignedCategories as $ac) {
            $catId  = (int)$ac['id'];
            $values = $this->dao->getValuesForCategory($catId);
            if (empty($values)) {
                continue;
            }
            $html .= '<div><label>' . htmlspecialchars((string)$ac['label']) . '</label><select name="attribute_values[' . $catId . '][]" multiple>';
            foreach ($values as $v) {
                $vidUsed = '';
                foreach ($assignments as $a) {
                    if ((int)$a['category_id'] === $catId && (int)$a['value_id'] === (int)$v['id']) {
                        $vidUsed = ' selected';
                        break;
                    }
                }
                $html .= '<option value="' . (int)$v['id'] . '"' . $vidUsed . '>' . htmlspecialchars((string)$v['value']) . '</option>';
            }
            $html .= '</select></div>';
        }

        // Add new assignment section (second call to listCategories)
        $allCategories = $this->dao->listCategories();
        $html .= '<h5>' . _('Add Assignment') . '</h5>';
        $html .= '<select name="add_category_id">';
        $html .= '<option value="">' . _('-- Select Category --') . '</option>';
        foreach ($allCategories as $cat) {
            $html .= '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars((string)$cat['label']) . '</option>';
        }
        $html .= '</select>';

        return $html;
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
