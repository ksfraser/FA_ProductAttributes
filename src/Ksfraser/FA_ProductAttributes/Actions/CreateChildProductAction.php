<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * "Create Child Product" (FR-9.13..9.16, #60).
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
 * Each freshly raised child is created through the FA-native child-creation
 * chokepoint (VariationsDao::createChildProduct → add_item, so it also gains its
 * item_codes row and is invoice-selectable) and then receives the FULL PA-attribute
 * clone: category assignments, the combo's concrete value assignments
 * (product_attribute_assignments), and the "other PA attributes"
 * (identifiers / shipping / warranty / lifecycle flags / tags) via
 * cloneProductAttributes().
 *
 * Reconciliation is scoped strictly to the given parent; nothing is ever
 * auto-rewritten when the parent's categories/values change.
 *
 * The destructive delete branch is the reason a confirmation summary is surfaced:
 * it never touches a child that has transaction history.
 */
class CreateChildProductAction
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

            // Guard: FA core accepts stock_ids up to varchar(32). Skip any
            // combo whose derived id is too long instead of letting add_item()
            // die (db_query with an error message exits the whole request).
            if (strlen($childStockId) > 32) {
                $errors[] = sprintf(
                    _("Child stock id '%s' exceeds the 32-character limit — skipped"),
                    $childStockId
                );
                continue;
            }

            if ($this->stockMasterExists($childStockId)) {
                // Already a product under this id: adopt it and stamp the combo.
                // If it was never linked to this parent (e.g. a half-instantiated
                // pool), repair the parent link + concrete value assignments so
                // combos and children cannot drift apart. Attribute clones are
                // left untouched to avoid duplicate rows.
                try {
                    if (!$this->hasParentLinkedAssignments($childStockId)) {
                        $this->variationsDao->copyParentCategoryAssignments($childStockId, $stockId);
                        $this->variationsDao->setParentRelationship($childStockId, $stockId);
                        $this->coreDao->setProductParent($childStockId, $stockId);
                        $this->recordAssignments($childStockId, (array)($combo['value_set'] ?? []), $stockId);
                    }
                    $this->combosDao->markInstantiated((int)$combo['id'], $childStockId);
                } catch (\Exception $e) {
                    $errors[] = sprintf(
                        _("Adopted '%s' but repair failed: %s"),
                        $childStockId,
                        $e->getMessage()
                    );
                }
                continue;
            }

            try {
                $this->variationsDao->createChildProduct($childStockId, $parentData);
                $this->variationsDao->copyParentCategoryAssignments($childStockId, $stockId);
                $this->variationsDao->setParentRelationship($childStockId, $stockId);
                $this->coreDao->setProductParent($childStockId, $stockId);
                $this->recordAssignments($childStockId, (array)($combo['value_set'] ?? []), $stockId);
                $this->cloneProductAttributes($childStockId, $stockId);
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
     * Record the combo's concrete value assignments on the child, linked back to
     * the parent via parent_stock_id.
     *
     * @param string $childId       New child stock id.
     * @param array  $comboValues   Per-value rows from the combo pool's value_set.
     * @param string $parentStockId Parent stock id.
     */
    private function recordAssignments(string $childId, array $comboValues, string $parentStockId): void
    {
        $sortOrder = 1;
        foreach ($comboValues as $item) {
            $this->coreDao->addAssignment(
                $childId,
                (int)$item['category_id'],
                (int)$item['value_id'],
                $sortOrder++,
                $parentStockId
            );
        }
    }

    /**
     * Clone the parent's "other PA attributes" onto the child: identifiers,
     * shipping attributes, warranty, lifecycle flags and tag assignments.
     * Only tables where the parent actually has a row are cloned; each copied
     * row rebinds stock_id to the child (the surrogate `id` is not copied).
     *
     * @param string $childId       New child stock id.
     * @param string $parentStockId Parent stock id.
     */
    private function cloneProductAttributes(string $childId, string $parentStockId): void
    {
        $p = $this->db->getTablePrefix();

        $clonableTables = [
            'product_identifiers',
            'product_shipping_attributes',
            'product_warranty',
            'product_lifecycle_flag_assignments',
            'product_tag_assignments',
        ];

        foreach ($clonableTables as $table) {
            $tbl = $p . $table;

            $exists = $this->db->query(
                "SELECT 1 FROM `" . $tbl . "` WHERE stock_id = :parent LIMIT 1",
                ['parent' => $parentStockId]
            );
            if (empty($exists)) {
                continue;
            }

            $cols = $this->db->query("SHOW COLUMNS FROM `" . $tbl . "`");
            $existingCols = array_column($cols, 'Field');

            $rows = $this->db->query(
                "SELECT * FROM `" . $tbl . "` WHERE stock_id = :parent",
                ['parent' => $parentStockId]
            );

            foreach ($rows as $row) {
                $copied = [];
                foreach ($row as $k => $v) {
                    if (!in_array($k, $existingCols, true)) {
                        continue;
                    }
                    if ($k === 'id') {
                        continue;
                    }
                    $copied[$k] = ($k === 'stock_id') ? $childId : $v;
                }
                if (empty($copied)) {
                    continue;
                }
                $fields = array_keys($copied);
                $placeholders = array_map(function ($f) { return ':' . $f; }, $fields);
                $this->db->execute(
                    "INSERT INTO `" . $tbl . "` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")",
                    $copied
                );
            }
        }
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

    /**
     * Whether the child already carries parent-linked value assignments.
     */
    private function hasParentLinkedAssignments(string $stockId): bool
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT 1 FROM `{$p}product_attribute_assignments`
             WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != ''
             LIMIT 1",
            ['stock_id' => $stockId]
        );
        return !empty($rows);
    }
}