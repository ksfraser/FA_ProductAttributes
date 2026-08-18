<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Action: Generate any missing variation products for a parent (BR1.6).
 *
 * Relies on the same combination-generation logic used by GenerateVariationsAction but
 * only creates variations whose stock_id does not yet exist in stock_master.
 */
class CreateMissingVariationsAction
{
    /** @var VariationsDao */
    private $variationsDao;

    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var DbAdapterInterface */
    private $db;

    /** @var ShippingAttributesDao|null */
    private $shippingDao;

    public function __construct(
        VariationsDao $variationsDao,
        ProductAttributesDao $coreDao,
        DbAdapterInterface $db,
        ShippingAttributesDao $shippingDao = null
    ) {
        $this->variationsDao = $variationsDao;
        $this->coreDao       = $coreDao;
        $this->db            = $db;
        $this->shippingDao   = $shippingDao;
    }

    /**
     * @param array<string, mixed> $postData  Must contain 'stock_id'
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            return _('Invalid stock ID');
        }

        $parentData = $this->variationsDao->getParentProductData($stockId);
        if ($parentData === null) {
            return sprintf(_('Parent product %s not found'), $stockId);
        }

        // Gather all category→values combos via category assignments
        $assignments = $this->variationsDao->listCategoryAssignments($stockId);
        if (empty($assignments)) {
            return _('No categories assigned to this product');
        }

        $categoryValues = [];
        foreach ($assignments as $assignment) {
            $categoryId = (int)$assignment['category_id'];
            $values     = $this->variationsDao->listActiveValues($categoryId);
            if (!empty($values)) {
                $categoryValues[$categoryId] = $values;
            }
        }

        if (empty($categoryValues)) {
            return _('No values found for assigned categories');
        }

        $combinations = $this->buildCombinations($categoryValues);

        $p       = $this->db->getTablePrefix();
        $created = 0;
        $errors  = [];

        foreach ($combinations as $combo) {
            $childId = $this->buildStockId($stockId, $combo);

            // Skip if already exists
            $exists = $this->db->query(
                "SELECT 1 FROM `{$p}stock_master` WHERE stock_id = :stock_id",
                ['stock_id' => $childId]
            );
            if (!empty($exists)) {
                continue;
            }

            try {
                $this->variationsDao->createChildProduct($childId, $parentData);
                $this->variationsDao->setParentRelationship($childId, $stockId);
                $this->cloneShippingIfAvailable($stockId, $childId);
                $created++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $message = sprintf(_('%d missing variation(s) created'), $created);
        if (!empty($errors)) {
            $message .= '. ' . sprintf(_('Errors: %s'), implode('; ', $errors));
        }

        return $message;
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

    /** Build all cartesian-product combinations from $categoryValues */
    private function buildCombinations(array $categoryValues): array
    {
        $combinations = [[]];

        foreach ($categoryValues as $categoryId => $values) {
            $next = [];
            foreach ($combinations as $existing) {
                foreach ($values as $value) {
                    $next[] = array_merge($existing, [
                        ['category_id' => $categoryId, 'slug' => $value['slug']]
                    ]);
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /** Build a child stock_id from the parent and the value slugs of a single combination */
    private function buildStockId(string $parentId, array $combo): string
    {
        $slugs = array_column($combo, 'slug');
        return $parentId . '-' . implode('-', $slugs);
    }
}
