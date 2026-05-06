<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Adds a category assignment to a product.
 */
class AddCategoryAssignmentAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId    = trim((string)($postData['stock_id'] ?? ''));
        $categoryId = (int)($postData['category_id'] ?? 0);

        if ($stockId === '' || $categoryId <= 0) {
            return "Invalid category assignment data";
        }

        $this->dao->addCategoryAssignment($stockId, $categoryId);

        return "Added category assignment";
    }
}
