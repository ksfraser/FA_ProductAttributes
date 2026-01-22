<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\UI\RoyalOrderHelper;

class GenerateVariationsAction
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var FrontAccountingDbAdapter */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, FrontAccountingDbAdapter $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            return _("Invalid stock ID");
        }

        // Get assigned categories for this product
        $assignedCategories = $this->dao->listCategoryAssignments($stockId);

        if (empty($assignedCategories)) {
            return _("No categories assigned to this product");
        }

        // Get all values for each assigned category
        $categoryValues = [];
        foreach ($assignedCategories as $category) {
            $categoryId = (int)$category['id'];
            $values = $this->dao->listValues($categoryId);
            if (!empty($values)) {
                $categoryValues[$categoryId] = $values;
            }
        }

        if (empty($categoryValues)) {
            return _("No values found for assigned categories");
        }

        // Generate all combinations
        $combinations = $this->generateCombinations($categoryValues);

        if (empty($combinations)) {
            return _("No valid combinations to generate");
        }

        // Get parent product details
        $parentProduct = $this->getParentProduct($stockId);
        if (!$parentProduct) {
            return _("Parent product not found");
        }

        $createdCount = 0;
        $errors = [];

        foreach ($combinations as $combination) {
            try {
                $variationStockId = $this->generateVariationStockId($stockId, $combination);
                $variationDescription = $this->generateVariationDescription($parentProduct['description'], $combination);

                // Check if variation already exists
                if ($this->variationExists($variationStockId)) {
                    continue; // Skip existing variations
                }

                // Create the variation product
                $this->createVariationProduct($parentProduct, $variationStockId, $variationDescription);

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $message = sprintf(_("Created %d variations"), $createdCount);
        if (!empty($errors)) {
            $message .= ". " . sprintf(_("Errors: %s"), implode(", ", $errors));
        }

        return $message;
    }

    private function generateCombinations(array $categoryValues): array
    {
        $combinations = [[]];

        foreach ($categoryValues as $categoryId => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge($combination, [
                        'category_id' => $categoryId,
                        'value_id' => $value['id'],
                        'value_slug' => $value['slug'],
                        'value_label' => $value['value']
                    ]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    private function generateVariationStockId(string $parentStockId, array $combination): string
    {
        // Sort combination by Royal Order
        $sortedCombination = $this->sortCombinationByRoyalOrder($combination);

        $slugs = [];
        foreach ($sortedCombination as $item) {
            $slugs[] = $item['value_slug'];
        }

        return $parentStockId . '-' . implode('-', $slugs);
    }

    private function sortCombinationByRoyalOrder(array $combination): array
    {
        // Group by category and get category sort orders
        $categories = $this->dao->listCategories();
        $categoryOrders = [];
        foreach ($categories as $cat) {
            $categoryOrders[$cat['id']] = $cat['sort_order'];
        }

        // Sort combination by category sort order
        usort($combination, function($a, $b) use ($categoryOrders) {
            $orderA = $categoryOrders[$a['category_id']] ?? 999;
            $orderB = $categoryOrders[$b['category_id']] ?? 999;
            return $orderA <=> $orderB;
        });

        return $combination;
    }

    private function generateVariationDescription(string $parentDescription, array $combination): string
    {
        $sortedCombination = $this->sortCombinationByRoyalOrder($combination);

        $placeholders = [];
        foreach ($sortedCombination as $item) {
            // This is a simplified version - in practice you'd need to map categories to placeholder names
            $placeholders[] = $item['value_label'];
        }

        // For now, just append the attributes to the description
        // In a full implementation, you'd replace ${ATTRIB_CLASS} placeholders
        return $parentDescription . ' (' . implode(', ', $placeholders) . ')';
    }

    private function getParentProduct(string $stockId): ?array
    {
        $p = $this->dbAdapter->getTablePrefix();
        $result = $this->dbAdapter->query(
            "SELECT * FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return $result[0] ?? null;
    }

    private function variationExists(string $stockId): bool
    {
        $p = $this->dbAdapter->getTablePrefix();
        $result = $this->dbAdapter->query(
            "SELECT COUNT(*) as count FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }

    private function createVariationProduct(array $parentProduct, string $variationStockId, string $variationDescription): void
    {
        $p = $this->dbAdapter->getTablePrefix();

        // Insert new product based on parent
        $this->dbAdapter->execute(
            "INSERT INTO `{$p}stock_master` (
                stock_id, category_id, tax_type_id, description, long_description,
                units, mb_flag, sales_account, inventory_account, cogs_account,
                adjustment_account, wip_account, dimension_id, dimension2_id,
                base_sales, last_cost, actual_cost, material_cost, labour_cost, overhead_cost,
                inactive, no_sale, editable
            ) SELECT
                :stock_id, category_id, tax_type_id, :description, long_description,
                units, mb_flag, sales_account, inventory_account, cogs_account,
                adjustment_account, wip_account, dimension_id, dimension2_id,
                base_sales, last_cost, actual_cost, material_cost, labour_cost, overhead_cost,
                0, no_sale, editable
            FROM `{$p}stock_master` WHERE stock_id = :parent_stock_id",
            [
                'stock_id' => $variationStockId,
                'description' => $variationDescription,
                'parent_stock_id' => $parentProduct['stock_id']
            ]
        );

        // Set parent relationship (assuming there's a parent_stock_id field)
        // Note: This might need to be added to FA schema or handled differently
        if ($this->dbAdapter->query("SHOW COLUMNS FROM `{$p}stock_master` LIKE 'parent_stock_id'")) {
            $this->dbAdapter->execute(
                "UPDATE `{$p}stock_master` SET parent_stock_id = :parent_stock_id WHERE stock_id = :stock_id",
                ['parent_stock_id' => $parentProduct['stock_id'], 'stock_id' => $variationStockId]
            );
        }
    }
}