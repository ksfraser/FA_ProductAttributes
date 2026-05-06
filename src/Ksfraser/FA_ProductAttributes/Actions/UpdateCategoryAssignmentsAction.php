<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Syncs a product's category assignments to a desired set.
 *
 * Adds newly required categories and removes deselected ones.
 */
class UpdateCategoryAssignmentsAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData  Expects 'stock_id' and 'category_ids' (array of int/string)
     * @return string Result message
     * @throws \InvalidArgumentException if stock_id is missing or blank
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            throw new \InvalidArgumentException("Stock ID is required");
        }

        $desiredIds = array_map('intval', (array)($postData['category_ids'] ?? []));

        // Current assignments
        $current    = $this->dao->listCategoryAssignments($stockId);
        $currentIds = array_map(function ($row) { return (int)$row['category_id']; }, $current);

        // Remove categories no longer desired
        foreach ($currentIds as $cid) {
            if (!in_array($cid, $desiredIds, true)) {
                $this->dao->removeCategoryAssignment($stockId, $cid);
            }
        }

        // Add newly desired categories
        foreach ($desiredIds as $cid) {
            if (!in_array($cid, $currentIds, true)) {
                $this->dao->addCategoryAssignment($stockId, $cid);
            }
        }

        return "Category assignments updated for product '{$stockId}'";
    }
}
