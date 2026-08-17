<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Action to create child products for all attribute combinations.
 *
 * Generates the cartesian product of all assigned category values and
 * creates one child product per combination.
 */
class CreateChildAction
{
    /** @var VariationsDao */
    private $variationsDao;

    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(VariationsDao $variationsDao, ProductAttributesDao $coreDao, DbAdapterInterface $db)
    {
        $this->variationsDao = $variationsDao;
        $this->coreDao       = $coreDao;
        $this->db            = $db;
    }

    public function handle(array $postData): ?string
    {
        $stockId = trim($postData['stock_id'] ?? '');

        if (empty($stockId)) {
            throw new \InvalidArgumentException("Stock ID is required");
        }

        $parentData = $this->variationsDao->getParentProductData($stockId);
        if (!$parentData) {
            throw new \InvalidArgumentException("Parent product '$stockId' not found");
        }

        $assignedCategories = $this->variationsDao->listCategoryAssignments($stockId);
        if (empty($assignedCategories)) {
            return _("No categories assigned to this product");
        }

        $categoryValues = [];
        foreach ($assignedCategories as $category) {
            $categoryId = (int)$category['id'];
            $values = $this->variationsDao->listValues($categoryId);
            if (!empty($values)) {
                $categoryValues[$categoryId] = $values;
            }
        }

        if (empty($categoryValues)) {
            return _("No values found for assigned categories");
        }

        $combinations = $this->buildCombinations($categoryValues);

        if (empty($combinations)) {
            return _("No valid combinations to generate");
        }

        $p       = $this->db->getTablePrefix();
        $created = 0;
        $errors  = [];

        foreach ($combinations as $combo) {
            $childId = $this->buildStockId($stockId, $combo);

            $exists = $this->db->query(
                "SELECT 1 FROM `{$p}stock_master` WHERE stock_id = :stock_id",
                ['stock_id' => $childId]
            );
            if (!empty($exists)) {
                continue;
            }

            try {
                $this->variationsDao->createChildProduct($childId, $parentData);
                $this->variationsDao->copyParentCategoryAssignments($childId, $stockId);
                $this->variationsDao->setParentRelationship($childId, $stockId);
                $this->recordAssignments($childId, $combo, $stockId);
                $created++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $message = sprintf(_("Created %d child product(s)"), $created);
        if (!empty($errors)) {
            $message .= ". " . sprintf(_("Errors: %s"), implode("; ", $errors));
        }

        return $message;
    }

    private function buildCombinations(array $categoryValues): array
    {
        $combinations = [[]];

        foreach ($categoryValues as $categoryId => $values) {
            $next = [];
            foreach ($combinations as $existing) {
                foreach ($values as $value) {
                    $next[] = array_merge($existing, [
                        ['category_id' => $categoryId, 'slug' => $value['slug'], 'value_id' => $value['id']]
                    ]);
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    private function buildStockId(string $parentId, array $combo): string
    {
        $slugs = array_column($combo, 'slug');
        return $parentId . '-' . implode('-', $slugs);
    }

    private function recordAssignments(string $childId, array $combo, string $parentStockId): void
    {
        $sortOrder = 1;
        foreach ($combo as $item) {
            $this->coreDao->addAssignment(
                $childId,
                (int)$item['category_id'],
                (int)$item['value_id'],
                $sortOrder++,
                $parentStockId
            );
        }
    }
}
