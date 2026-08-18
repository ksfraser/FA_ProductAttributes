<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\FA_ProductAttributes\Variations\UI\RoyalOrderHelper;

class GenerateVariationsAction
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var DbAdapterInterface */
    private $dbAdapter;

    /** @var ShippingAttributesDao|null */
    private $shippingDao;

    public function __construct(
        ProductAttributesDao $dao,
        DbAdapterInterface $dbAdapter,
        ShippingAttributesDao $shippingDao = null
    ) {
        $this->dao        = $dao;
        $this->dbAdapter  = $dbAdapter;
        $this->shippingDao = $shippingDao;
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
            $values = $this->dao->listActiveValues($categoryId);
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
                $this->createVariationProduct($parentProduct, $variationStockId, $variationDescription, $combination);

                // Clone parent shipping attributes to the new variation (if available)
                $this->cloneShippingIfAvailable($stockId, $variationStockId);

                // Record the attribute combination for this variation so the
                // Variations tab and variations picker can list it (issue #34).
                $this->recordVariationAssignments(
                    $parentProduct['stock_id'],
                    $variationStockId,
                    $this->sortCombinationByRoyalOrder($combination)
                );

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
                    $newCombinations[] = array_merge($combination, [[
                        'category_id' => $categoryId,
                        'value_id' => $value['id'],
                        'value_slug' => $value['slug'],
                        'value_label' => $value['value']
                    ]]);
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

    /**
     * Copy the parent's shipping attributes to the child, if a ShippingAttributesDao
     * was provided and the parent has a shipping record.
     */
    private function cloneShippingIfAvailable(string $parentId, string $childId): void
    {
        if ($this->shippingDao === null) {
            return;
        }
        $parentShipping = $this->shippingDao->get($parentId);
        if ($parentShipping === null) {
            return;
        }
        $data = $parentShipping;
        unset($data['stock_id']);
        $this->shippingDao->upsert($childId, $data);
    }

    private function createVariationProduct(array $parentProduct, string $variationStockId, string $variationDescription, array $combination): void
    {
        $p = $this->dbAdapter->getTablePrefix();

        $cols = $this->dbAdapter->query("SHOW COLUMNS FROM `{$p}stock_master`");
        $existingCols = array_column($cols, 'Field');

        $copied = [];
        foreach ($parentProduct as $k => $v) {
            if (in_array($k, $existingCols, true)) {
                $copied[$k] = $v;
            }
        }

        $copied['stock_id'] = $variationStockId;
        $copied['description'] = $variationDescription;
        unset($copied['inactive']);
        unset($copied['editable']);

        $fields = array_keys($copied);
        $placeholders = array_map(function ($f) { return ':' . $f; }, $fields);

        $this->dbAdapter->execute(
            "INSERT INTO `{$p}stock_master` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $copied
        );
    }

    /**
     * Insert one product_attribute_assignments row per selected value, linked to
     * the parent product via parent_stock_id (GitHub issue #34).
     *
     * @param string $parentStockId
     * @param string $variationStockId
     * @param array  $combination
     */
    private function recordVariationAssignments(string $parentStockId, string $variationStockId, array $combination): void
    {
        $sortOrder = 1;
        foreach ($combination as $item) {
            $this->dao->addAssignment(
                $variationStockId,
                (int)$item['category_id'],
                (int)$item['value_id'],
                $sortOrder++,
                $parentStockId
            );
        }
    }
}