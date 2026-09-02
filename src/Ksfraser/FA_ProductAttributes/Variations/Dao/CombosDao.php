<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Data Access Object for the persisted combination pool (FR-9.12..9.16).
 *
 * The combo pool (`product_variation_combos`) records, per parent product, the
 * cartesian combination set produced by "Generate Combinations" (renamed from
 * "Generate Variations"). It is written ONLY on the explicit action and is never
 * auto-rewritten when a parent's categories or values change.
 *
 * Gen Child reads the pool to instantiate combos into stock_master children and to
 * reconcile this parent's children against it.
 */
class CombosDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Persist the cartesian combination set for a parent.
     *
     * Idempotent: combos already present (same value_set_key) are left untouched,
     * new ones are inserted. Combos no longer produced by the new selection are
     * NOT deleted here (orphan reconciliation is a Gen Child concern).
     *
     * @param string $parentStockId
     * @param array<int, array{value_set_key:string, slug_key:string}> $combos
     */
    public function syncCombos(string $parentStockId, array $combos): int
    {
        $p = $this->db->getTablePrefix();
        $added = 0;

        foreach ($combos as $combo) {
            $valueSetKey = (string)($combo['value_set_key'] ?? '');
            $slugKey     = (string)($combo['slug_key'] ?? '');
            if ($valueSetKey === '') {
                continue;
            }

            $existing = $this->db->query(
                "SELECT id FROM `{$p}product_variation_combos`
                 WHERE parent_stock_id = :parent AND value_set_key = :vsk LIMIT 1",
                ['parent' => $parentStockId, 'vsk' => $valueSetKey]
            );

            if (!empty($existing)) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO `{$p}product_variation_combos`
                 (parent_stock_id, value_set_key, slug_key) VALUES (:parent, :vsk, :slug)",
                ['parent' => $parentStockId, 'vsk' => $valueSetKey, 'slug' => $slugKey]
            );
            $added++;
        }

        return $added;
    }

    /**
     * List the persisted combos for a parent, ordered by slug_key.
     *
     * @return array<int, array<string, string|null>>
     */
    public function listCombos(string $parentStockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT id, parent_stock_id, value_set_key, slug_key, child_stock_id
             FROM `{$p}product_variation_combos`
             WHERE parent_stock_id = :parent
             ORDER BY slug_key",
            ['parent' => $parentStockId]
        );
    }

    /**
     * Mark a combo as instantiated (child stock_id stamped).
     */
    public function markInstantiated(int $comboId, string $childStockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "UPDATE `{$p}product_variation_combos` SET child_stock_id = :child WHERE id = :id",
            ['child' => $childStockId, 'id' => $comboId]
        );
    }

    /**
     * List the child stock_ids recorded in this parent's combo pool.
     *
     * @return array<int, string>
     */
    public function listPoolChildStockIds(string $parentStockId): array
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT child_stock_id FROM `{$p}product_variation_combos`
             WHERE parent_stock_id = :parent AND child_stock_id IS NOT NULL AND child_stock_id != ''",
            ['parent' => $parentStockId]
        );
        return array_values(array_filter(array_map(function ($r) {
            return (string)($r['child_stock_id'] ?? '');
        }, $rows), function ($v) {
            return $v !== '';
        }));
    }

    /**
     * List all current children of a parent from the canonical product_hierarchy.
     *
     * @param string $parentStockId
     * @return array<int, string>
     */
    public function listChildrenByParent(string $parentStockId): array
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT child_stock_id FROM `{$p}product_hierarchy` WHERE parent_stock_id = :parent",
            ['parent' => $parentStockId]
        );
        return array_map(function ($r) { return (string)$r['child_stock_id']; }, $rows);
    }

    /**
     * Whether a product has any stock_moves history (transactions occurred).
     */
    public function childHasHistory(string $stockId): bool
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT 1 FROM `{$p}stock_moves` WHERE stock_id = :stock_id LIMIT 1",
            ['stock_id' => $stockId]
        );
        return !empty($rows);
    }

    /**
     * Current on-hand quantity for a product, derived from stock_moves.
     */
    public function childQtyOnHand(string $stockId): float
    {
        $p = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT COALESCE(SUM(qty_on_hand), 0) AS qty FROM `{$p}stock_moves` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return (float)($rows[0]['qty'] ?? 0);
    }

    /**
     * Mark a child product inactive in stock_master.
     */
    public function setChildInactive(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "UPDATE `{$p}stock_master` SET inactive = 1 WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
    }

    /**
     * Remove a child product entirely: stock_master, parent link, and any
     * recorded attribute assignment rows. Intended ONLY for children with no
     * transaction history (otherwise the history would be orphaned).
     */
    public function removeChild(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute("DELETE FROM `{$p}product_hierarchy` WHERE child_stock_id = :child", ['child' => $stockId]);
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_assignments` WHERE stock_id = :child",
            ['child' => $stockId]
        );
        // Drop the combo pool stamp so the combo can be re-instantiated later.
        $this->db->execute(
            "UPDATE `{$p}product_variation_combos` SET child_stock_id = NULL WHERE child_stock_id = :child",
            ['child' => $stockId]
        );
        $this->db->execute("DELETE FROM `{$p}stock_master` WHERE stock_id = :child", ['child' => $stockId]);
    }
}
