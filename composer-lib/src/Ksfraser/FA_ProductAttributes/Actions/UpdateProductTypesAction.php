<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Action to update product types (simple/variable/variation)
 */
class UpdateProductTypesAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): ?string
    {
        $productTypes = $postData['product_types'] ?? [];
        $parentProducts = $postData['parent_products'] ?? [];
        $updatedCount = 0;

        foreach ($productTypes as $stockId => $newType) {
            if (empty($newType)) {
                continue; // No change requested
            }

            $currentType = $this->getCurrentProductType($stockId);

            if ($newType !== $currentType) {
                $this->updateProductType($stockId, $newType, $parentProducts[$stockId] ?? null);
                $updatedCount++;
            }
        }

        return "Updated product types for {$updatedCount} products";
    }

    /**
     * Get the current type of a product
     */
    private function getCurrentProductType(string $stockId): string
    {
        // Check if it has category assignments (Variable)
        $categories = $this->dao->listCategoryAssignments($stockId);
        if (!empty($categories)) {
            return 'variable';
        }

        // Check if it has a parent (Variation)
        $parent = $this->dao->getProductParent($stockId);
        if ($parent) {
            return 'variation';
        }

        // Default to Simple
        return 'simple';
    }

    /**
     * Update a product's type
     */
    private function updateProductType(string $stockId, string $newType, ?string $parentStockId): void
    {
        switch ($newType) {
            case 'simple':
                // Remove all category assignments and parent relationships
                $this->clearProductAssignments($stockId);
                break;

            case 'variable':
                // Remove parent relationship (if any) but keep category assignments
                $this->clearParentRelationship($stockId);
                // Note: Category assignments would be managed separately
                break;

            case 'variation':
                if (empty($parentStockId)) {
                    throw new \InvalidArgumentException("Parent product is required for variation type");
                }
                // Set parent relationship
                $this->setParentRelationship($stockId, $parentStockId);
                // Remove category assignments (variations don't have their own categories)
                $this->clearProductAssignments($stockId);
                break;
        }
    }

    /**
     * Clear all category assignments for a product
     */
    private function clearProductAssignments(string $stockId): void
    {
        $assignments = $this->dao->listCategoryAssignments($stockId);
        foreach ($assignments as $assignment) {
            $this->dao->removeCategoryAssignment($stockId, $assignment['id']);
        }
    }

    /**
     * Clear parent relationship for a product
     */
    private function clearParentRelationship(string $stockId): void
    {
        $this->dao->clearParentRelationship($stockId);
    }

    /**
     * Set parent relationship for a variation
     */
    private function setParentRelationship(string $stockId, string $parentStockId): void
    {
        $this->dao->setParentRelationship($stockId, $parentStockId);
    }
}