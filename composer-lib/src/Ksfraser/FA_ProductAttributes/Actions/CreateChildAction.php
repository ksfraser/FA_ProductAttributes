<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Action to create a child product (variation)
 */
class CreateChildAction
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

        if (empty($stockId)) {
            throw new \InvalidArgumentException("Stock ID is required");
        }

        // Generate child stock ID (parent + timestamp for uniqueness)
        $childStockId = $stockId . '-VAR-' . time();

        // Get parent product data
        $parentData = $this->dao->getParentProductData($stockId);
        if (!$parentData) {
            throw new \InvalidArgumentException("Parent product '$stockId' not found");
        }

        // Create child product
        $this->dao->createChildProduct($childStockId, $parentData);

        // Copy parent's category assignments to child
        $this->dao->copyParentCategoryAssignments($childStockId, $stockId);

        // Set parent relationship
        $this->dao->setParentRelationship($childStockId, $stockId);

        return "Child product '$childStockId' created successfully from parent '$stockId'";
    }
}