<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Action to update category assignments for a product
 */
class UpdateCategoryAssignmentsAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): ?string
    {
        $stockId = trim($postData['stock_id'] ?? '');
        $categoryIds = $postData['category_ids'] ?? [];

        if (empty($stockId)) {
            throw new \InvalidArgumentException("Stock ID is required");
        }

        // Get current assignments
        $currentAssignments = $this->dao->listCategoryAssignments($stockId);
        $currentCategoryIds = array_column($currentAssignments, 'id');

        // Convert category IDs to integers
        $categoryIds = array_map('intval', $categoryIds);

        // Remove assignments that are no longer selected
        $toRemove = array_diff($currentCategoryIds, $categoryIds);
        foreach ($toRemove as $categoryId) {
            $this->dao->removeCategoryAssignment($stockId, $categoryId);
        }

        // Add new assignments
        $toAdd = array_diff($categoryIds, $currentCategoryIds);
        foreach ($toAdd as $categoryId) {
            $this->dao->addCategoryAssignment($stockId, $categoryId);
        }

        return "Category assignments updated for product '$stockId'";
    }
}