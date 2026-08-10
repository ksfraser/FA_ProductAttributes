<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for Square-style modifier lists,
 * their modifiers, and per-product list assignments.
 *
 * Tables:
 *   0_product_modifier_lists               — list definitions (selection type, min/max, …)
 *   0_product_modifiers                    — options inside a list (name, price, defaults, …)
 *   0_product_modifier_list_assignments    — stock_id ↔ modifier_list_id many-to-many
 *
 * @since 1.1.0
 */
class ProductModifiersDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var string[] Columns writable on a modifier list row. */
    private static $listColumns = [
        'name', 'selection_type', 'modifier_type',
        'min_selected_modifiers', 'max_selected_modifiers',
        'allow_quantities', 'hidden_from_customer', 'ordinal', 'active',
    ];

    /** @var string[] Columns writable on a modifier row. */
    private static $modifierColumns = [
        'modifier_list_id', 'name', 'price', 'on_by_default',
        'ordinal', 'kitchen_name', 'hidden_online', 'active',
    ];

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    // ── Modifier lists ────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLists(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_modifier_lists` ORDER BY ordinal, name'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getList(int $id): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_modifier_lists` WHERE id = :id',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Create or update a modifier list. Pass $id > 0 to update, 0 to create.
     *
     * @param array<string, mixed> $data
     */
    public function upsertList(array $data, int $id = 0): int
    {
        $p    = $this->db->getTablePrefix();
        $safe = $this->filterData($data, self::$listColumns);

        if ($id > 0) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_modifier_lists` SET ' . implode(', ', $sets)
                . ' WHERE id = :id',
                array_merge($safe, ['id' => $id])
            );
            return $id;
        }

        $cols  = array_keys($safe);
        $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
        $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_modifier_lists` (' . $names . ') VALUES (' . $phs . ')',
            $safe
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Remove a list and all of its modifiers and product assignments.
     */
    public function deleteList(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifier_list_assignments` WHERE modifier_list_id = :modifier_list_id',
            ['modifier_list_id' => $id]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifiers` WHERE modifier_list_id = :modifier_list_id',
            ['modifier_list_id' => $id]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifier_lists` WHERE id = :id',
            ['id' => $id]
        );
    }

    // ── Modifiers ─────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listModifiers(int $listId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_modifiers` WHERE modifier_list_id = :modifier_list_id'
            . ' ORDER BY ordinal, name',
            ['modifier_list_id' => $listId]
        );
    }

    /**
     * Create or update a modifier. Pass $id > 0 to update, 0 to create.
     *
     * @param array<string, mixed> $data
     */
    public function upsertModifier(array $data, int $id = 0): int
    {
        $p    = $this->db->getTablePrefix();
        $safe = $this->filterData($data, self::$modifierColumns);

        if ($id > 0) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_modifiers` SET ' . implode(', ', $sets)
                . ' WHERE id = :id',
                array_merge($safe, ['id' => $id])
            );
            return $id;
        }

        $cols  = array_keys($safe);
        $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
        $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_modifiers` (' . $names . ') VALUES (' . $phs . ')',
            $safe
        );
        return (int)$this->db->lastInsertId();
    }

    public function deleteModifier(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifiers` WHERE id = :id',
            ['id' => $id]
        );
    }

    // ── Per-product list assignment ───────────────────────────────────────────

    /**
     * Modifier lists assigned to a product, in assignment order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getListsForStock(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT l.*, a.sort_order'
            . ' FROM `' . $p . 'product_modifier_lists` l'
            . ' INNER JOIN `' . $p . 'product_modifier_list_assignments` a ON a.modifier_list_id = l.id'
            . ' WHERE a.stock_id = :stock_id'
            . ' ORDER BY a.sort_order, l.ordinal, l.name',
            ['stock_id' => $stockId]
        );
    }

    /**
     * Assign a modifier list to a product (idempotent — duplicates update order).
     */
    public function assignToList(string $stockId, int $listId, int $sortOrder = 0): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_modifier_list_assignments`'
            . ' (stock_id, modifier_list_id, sort_order) VALUES (:stock_id, :modifier_list_id, :sort_order)'
            . ' ON DUPLICATE KEY UPDATE sort_order = :sort_order',
            ['stock_id' => $stockId, 'modifier_list_id' => $listId, 'sort_order' => $sortOrder]
        );
    }

    /**
     * Remove a modifier list assignment from a product.
     */
    public function unassignFromList(string $stockId, int $listId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifier_list_assignments`'
            . ' WHERE stock_id = :stock_id AND modifier_list_id = :modifier_list_id',
            ['stock_id' => $stockId, 'modifier_list_id' => $listId]
        );
    }

    /**
     * Replace all modifier list assignments for a product in one operation.
     *
     * @param int[] $listIds
     */
    public function syncStockAssignments(string $stockId, array $listIds): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_modifier_list_assignments` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        foreach ($listIds as $index => $listId) {
            $listId = (int)$listId;
            if ($listId > 0) {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_modifier_list_assignments`'
                    . ' (stock_id, modifier_list_id, sort_order) VALUES (:stock_id, :modifier_list_id, :sort_order)',
                    ['stock_id' => $stockId, 'modifier_list_id' => $listId, 'sort_order' => (int)$index]
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param string[]             $columns
     * @return array<string, mixed>
     */
    private function filterData(array $data, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $out[$col] = $data[$col];
            }
        }
        return $out;
    }
}
