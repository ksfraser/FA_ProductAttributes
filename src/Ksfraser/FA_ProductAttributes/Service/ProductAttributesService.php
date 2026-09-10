<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\AddAssignmentForm;
use Ksfraser\FA_ProductAttributes\Variations\UI\AssignedCategoriesSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\CurrentAssignmentsSection;
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
     * 2. Add Assignment section with Category + Value checkboxes + Sort Order + Add button
     *
     * @param string $stockId
     * @return string HTML
     */
    public function renderProductAttributesTab(string $stockId): string
    {
        $assignments        = $stockId !== '' ? $this->dao->listAssignments($stockId) : [];
        $assignedCategories = $stockId !== '' ? $this->dao->listCategoryAssignments($stockId) : [];
        $categories         = $this->dao->listCategories();

        $html = '<h4>' . _('Product Attributes') . '</h4>';

        $html .= '<p style="color:#666;font-size:11px">'
            . _('Assign attribute values to this product. Manage categories and values on the')
            . ' <a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php">'
            . _('Product Attributes Admin page') . '</a>.</p>';

        // Assigned Categories + Current Attribute Assignments use the same SRP
        // section classes as the Variations tab (display-only here).
        ob_start();
        (new AssignedCategoriesSection($this->dao))->render($stockId, $assignedCategories, false);
        $html .= ob_get_clean();

        ob_start();
        (new CurrentAssignmentsSection())->render($assignments, !empty($assignedCategories), true);
        $html .= ob_get_clean();

        if (!empty($categories)) {
            $html .= (new AddAssignmentForm($categories, $stockId))->render();
        }

        return $html;
    }

    /**
     * Handle adding assignments from the tab.
     *
     * Accepts the same multi-value payload as the admin page: `value_ids[]`
     * checkboxes plus the "Add All" flag. The category-level assignment row is
     * upserted so the category shows as assigned on the Variations tab.
     *
     * @param string               $stockId
     * @param array<string, mixed> $postData
     * @return string Success/error message
     */
    public function handleAddAssignment(string $stockId, array $postData): string
    {
        $categoryId = (int)($postData['category_id'] ?? 0);
        $sortOrder  = (int)($postData['sort_order'] ?? 0);

        if ($stockId === '' || $categoryId <= 0) {
            return _('Please select a category.');
        }

        $addAll = isset($postData['add_all']) && (int)$postData['add_all'] === 1;

        $valueIds = array_values(array_unique(array_filter(
            array_map('intval', (array)($postData['value_ids'] ?? [])),
            function ($id) {
                return $id > 0;
            }
        )));

        if ($addAll) {
            $valueIds = array_values(array_unique(array_merge(
                $valueIds,
                array_map('intval', array_column($this->dao->listActiveValues($categoryId), 'id'))
            )));
        }

        if (empty($valueIds)) {
            return _('Please select at least one value, or check "Add All".');
        }

        $this->dao->addCategoryAssignment($stockId, $categoryId);

        $rows = [];
        foreach ($valueIds as $vid) {
            $rows[] = ['category_id' => $categoryId, 'value_id' => $vid, 'sort_order' => $sortOrder];
        }

        $added = $this->dao->assignValues($stockId, $rows);

        if (empty($added)) {
            return _('Those category-value pairs are already assigned.');
        }
        return sprintf(_('%d assignment(s) added.'), count($added));
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

        // Single-select condition (radio box). Persisted whenever the field is
        // present in the item form; a cleared selection removes the row.
        if (array_key_exists('pa_condition', $postData)) {
            $conditionId = (int)$postData['pa_condition'];
            $this->dao->setProductCondition($stockId, $conditionId > 0 ? $conditionId : null);
        }

        // Condition dropdown rendered on the Lifecycle tab. Persisted from the
        // main item form save as well so both save paths stay consistent.
        if (array_key_exists('condition_id', $postData)) {
            $conditionId = (int)$postData['condition_id'];
            $this->dao->setProductCondition($stockId, $conditionId > 0 ? $conditionId : null);
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
        $this->dao->setProductCondition($stockId, null);
    }
}
