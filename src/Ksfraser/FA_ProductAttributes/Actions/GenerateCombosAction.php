<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * "Generate Combinations" (renamed from "Generate Variations", FR-9.12, #60).
 *
 * Computes the cartesian product of a parent product's assigned category values
 * and PERSISTS the combination set into the combo pool (product_variation_combos).
 * It does NOT create stock_master children - that is GenerateChildAction's job.
 *
 * Idempotent: combos already in the pool are left untouched. Re-running after a
 * category/value change adds only the newly-produced combos; orphan reconciliation
 * is handled by GenerateChildAction.
 */
class GenerateCombosAction
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var CombosDao */
    private $combosDao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(
        ProductAttributesDao $dao,
        CombosDao $combosDao,
        DbAdapterInterface $dbAdapter
    ) {
        $this->dao       = $dao;
        $this->combosDao = $combosDao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            return _("Invalid stock ID");
        }

        if ($this->isChildProduct($stockId)) {
            return sprintf(
                _("Cannot generate combinations for '%s': it is a variation of another product"),
                $stockId
            );
        }

        $assignedCategories = $this->dao->listCategoryAssignments($stockId);
        if (empty($assignedCategories)) {
            return _("No categories assigned to this product");
        }

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

        $combinations = $this->generateCombinations($categoryValues);
        if (empty($combinations)) {
            return _("No valid combinations to generate");
        }

        $comboRecords = [];
        foreach ($combinations as $combination) {
            $sorted = $this->sortCombinationByRoyalOrder($combination);
            $valueSetKey = $this->buildValueSetKey($sorted);
            $slugKey = $this->buildSlugKey($sorted);
            $comboRecords[] = [
                'value_set_key' => $valueSetKey,
                'slug_key' => $slugKey,
            ];
        }

        $added = $this->combosDao->syncCombos($stockId, $comboRecords);

        if ($added === 0) {
            return sprintf(_("Combination set is already up to date (%d combinations persist)"), count($comboRecords));
        }

        return sprintf(_("Combination set saved: %d new / %d total"), $added, count($comboRecords));
    }

    /**
     * Compute the cartesian product of category values.
     *
     * @param array<int, array<int, array<string,mixed>>> $categoryValues
     * @return array<int, array<int, array<string,mixed>>>
     */
    private function generateCombinations(array $categoryValues): array
    {
        $combinations = [[]];

        foreach ($categoryValues as $categoryId => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge($combination, [[
                        'category_id' => $categoryId,
                        'value_id' => (int)$value['id'],
                        'value_slug' => $value['slug'],
                        'value_label' => $value['value'],
                    ]]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * Order-independent key: comma-joined sorted value_ids (for dedupe).
     */
    private function buildValueSetKey(array $combination): string
    {
        $valueIds = array_column($combination, 'value_id');
        sort($valueIds, SORT_NUMERIC);
        return implode(',', $valueIds);
    }

    /**
     * Royal-Order slug chain (for child stock_id suffix).
     */
    private function buildSlugKey(array $combination): string
    {
        $slugs = array_column($combination, 'value_slug');
        return implode('-', $slugs);
    }

    private function sortCombinationByRoyalOrder(array $combination): array
    {
        $categories = $this->dao->listCategories();
        $categoryOrders = [];
        foreach ($categories as $cat) {
            $categoryOrders[$cat['id']] = $cat['sort_order'];
        }

        usort($combination, function ($a, $b) use ($categoryOrders) {
            $orderA = $categoryOrders[$a['category_id']] ?? 999;
            $orderB = $categoryOrders[$b['category_id']] ?? 999;
            return $orderA <=> $orderB;
        });

        return $combination;
    }

    /**
     * Determine whether a product is itself a variation of another product.
     */
    private function isChildProduct(string $stockId): bool
    {
        $parentId = $this->dao->getProductParent($stockId);
        if (!empty($parentId)) {
            return true;
        }

        $p = $this->dbAdapter->getTablePrefix();
        $rows = $this->dbAdapter->query(
            "SELECT 1 FROM `{$p}product_attribute_assignments`
             WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != ''
             LIMIT 1",
            ['stock_id' => $stockId]
        );

        return !empty($rows);
    }
}
