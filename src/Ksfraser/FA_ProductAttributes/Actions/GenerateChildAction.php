<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * "Generate Child" (FR-9.13..9.16, #60).
 *
 * Instantiates the persisted combo pool (product_variation_combos) into real
 * stock_master children and reconciles THIS parent's children against the pool:
 *
 *  - combos not yet instantiated → create child (stamp child_stock_id).
 *  - current children no longer represented in the pool:
 *      - no stock_moves history → delete (fully removed; no orphaned history).
 *      - history but zero on-hand → deactivate (inactive = 1), history preserved.
 *      - history with on-hand   → report as "with stock" (blocked list); left active.
 *
 * Reconciliation is scoped strictly to the given parent; nothing is ever
 * auto-rewritten when the parent's categories/values change.
 *
 * The destructive delete branch is the reason a confirmation summary is surfaced:
 * it never touches a child that has transaction history.
 */
class GenerateChildAction
{
    /** @var VariationsDao */
    private $variationsDao;

    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var CombosDao */
    private $combosDao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(
        VariationsDao $variationsDao,
        ProductAttributesDao $coreDao,
        CombosDao $combosDao,
        DbAdapterInterface $db
    ) {
        $this->variationsDao = $variationsDao;
        $this->coreDao       = $coreDao;
        $this->combosDao     = $combosDao;
        $this->db            = $db;
    }

    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));
        if ($stockId === '') {
            return _('Invalid stock ID');
        }

        if ($this->isChildProduct($stockId)) {
            return sprintf(
                _("Cannot generate children for '%s': it is a variation of another product"),
                $stockId
            );
        }

        $parentData = $this->variationsDao->getParentProductData($stockId);
        if ($parentData === null) {
            return sprintf(_('Parent product %s not found'), $stockId);
        }

        $combos      = $this->combosDao->listCombos($stockId);
        $poolChildIds = $this->combosDao->listPoolChildStockIds($stockId);
        $currentChildren = $this->combosDao->listChildrenByParent($stockId);

        if (empty($combos)) {
            return sprintf(
                _("No combination set saved for '%s'. Run <em>Generate Combinations</em> first."),
                $stockId
            );
        }

        // 1) Create children for combos not yet instantiated.
        $created = 0;
        $errors  = [];
        foreach ($combos as $combo) {
            $childStockId = $combo['child_stock_id'];
            if (empty($childStockId)) {
                $childStockId = $stockId . '-' . ($combo['slug_key'] ?? '');
            }
            if (!empty($combo['child_stock_id'])) {
                continue; // already instantiated
            }

            // Skip the empty-slug degenerate case (all values blank) — nothing to name.
            if (trim((string)($combo['slug_key'] ?? '')) === '') {
                continue;
            }

            if ($this->stockMasterExists($childStockId)) {
                // Already a product under this id: adopt it and stamp the combo.
                $this->combosDao->markInstantiated((int)$combo['id'], $childStockId);
                continue;
            }

            try {
                $this->variationsDao->createChildProduct($childStockId, $parentData);
                $this->variationsDao->copyParentCategoryAssignments($childStockId, $stockId);
                $this->variationsDao->setParentRelationship($childStockId, $stockId);
                $this->coreDao->setProductParent($childStockId, $stockId);
                $this->combosDao->markInstantiated((int)$combo['id'], $childStockId);
                $created++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 2) Reconcile: children of this parent not represented in the pool.
        $poolSet        = array_flip($poolChildIds);
        $deleted        = [];
        $inactivated    = [];
        $withStockBlock = [];

        foreach ($currentChildren as $childId) {
            if (isset($poolSet[$childId])) {
                continue; // represented — keep
            }
            if (!$this->stockMasterExists($childId)) {
                continue; // already gone
            }

            if (!$this->combosDao->childHasHistory($childId)) {
                $this->combosDao->removeChild($childId);
                $deleted[] = $childId;
            } elseif ($this->combosDao->childQtyOnHand($childId) <= 0) {
                $this->combosDao->setChildInactive($childId);
                $inactivated[] = $childId;
            } else {
                $withStockBlock[] = $childId;
            }
        }

        $message = sprintf(
            _('Generate Child complete. %d created, %d removed, %d inactivated.'),
            $created,
            count($deleted),
            count($inactivated)
        );

        if (!empty($deleted)) {
            $message .= ' ' . _('Removed') . ': ' . implode(', ', $deleted);
        }
        if (!empty($inactivated)) {
            $message .= ' ' . _('Inactivated') . ': ' . implode(', ', $inactivated);
        }
        if (!empty($withStockBlock)) {
            $message .= ' ' . sprintf(
                _('With stock (left active, block further orders): %s'),
                implode(', ', $withStockBlock)
            );
        }
        if (!empty($errors)) {
            $message .= '. ' . sprintf(_('Errors: %s'), implode('; ', $errors));
        }

        return $message;
    }

    /**
     * Determine whether a product is itself a variation of another product.
     */
    private function isChildProduct(string $stockId): bool
    {
        $parentId = $this->coreDao->getProductParent($stockId);
        if (!empty($parentId)) {
            return true;
        }
        return $this->variationsDao->getProductParent($stockId) !== null;
    }

    private function stockMasterExists(string $stockId): bool
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT 1 FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return !empty($rows);
    }
}