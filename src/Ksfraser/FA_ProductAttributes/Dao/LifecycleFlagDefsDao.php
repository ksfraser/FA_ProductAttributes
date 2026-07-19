<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Manages lifecycle flag definitions and per-product assignments.
 *
 * Tables:
 *   0_product_lifecycle_flag_defs         — admin-configurable flag definitions
 *   0_product_lifecycle_flag_assignments  — per-product flag assignments
 */
class LifecycleFlagDefsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    // ── Flag Definitions ─────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFlags(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_lifecycle_flag_defs` ORDER BY sort_order ASC, id ASC'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveFlags(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_lifecycle_flag_defs`'
            . ' WHERE active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFlag(int $id): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_lifecycle_flag_defs` WHERE id = :id',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Insert or update a flag definition.
     *
     * @param array<string, mixed> $data  Must contain: code, label. May contain: sort_order, active.
     */
    public function upsertFlag(array $data): int
    {
        $p    = $this->db->getTablePrefix();
        $code  = trim((string)($data['code'] ?? ''));
        $label = trim((string)($data['label'] ?? ''));
        if ($code === '' || $label === '') {
            return 0;
        }

        $sortOrder = (int)($data['sort_order'] ?? 0);
        $active    = (int)(bool)($data['active'] ?? 1);

        $existing = $this->db->query(
            'SELECT id FROM `' . $p . 'product_lifecycle_flag_defs` WHERE code = :code',
            ['code' => $code]
        );

        if (!empty($existing)) {
            $id = (int)$existing[0]['id'];
            $this->db->execute(
                'UPDATE `' . $p . 'product_lifecycle_flag_defs`'
                . ' SET label = :label, sort_order = :sort_order, active = :active'
                . ' WHERE id = :id',
                ['label' => $label, 'sort_order' => $sortOrder, 'active' => $active, 'id' => $id]
            );
            return $id;
        }

        $this->db->execute(
            'INSERT INTO `' . $p . 'product_lifecycle_flag_defs` (code, label, sort_order, active)'
            . ' VALUES (:code, :label, :sort_order, :active)',
            ['code' => $code, 'label' => $label, 'sort_order' => $sortOrder, 'active' => $active]
        );
        return (int)$this->db->lastInsertId();
    }

    public function deleteFlag(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_lifecycle_flag_assignments` WHERE flag_id = :id',
            ['id' => $id]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_lifecycle_flag_defs` WHERE id = :id',
            ['id' => $id]
        );
    }

    // ── Per-Product Assignments ──────────────────────────────────────────────

    /**
     * Get active flag IDs for a product.
     *
     * @return int[]
     */
    public function getAssignedFlagIds(string $stockId): array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT flag_id FROM `' . $p . 'product_lifecycle_flag_assignments`'
            . ' WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        return array_map('intval', array_column($rows, 'flag_id'));
    }

    /**
     * Replace all flag assignments for a product.
     *
     * @param int[] $flagIds
     */
    public function setAssignedFlags(string $stockId, array $flagIds): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_lifecycle_flag_assignments` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        foreach ($flagIds as $flagId) {
            $flagId = (int)$flagId;
            if ($flagId > 0) {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_lifecycle_flag_assignments`'
                    . ' (stock_id, flag_id) VALUES (:stock_id, :flag_id)',
                    ['stock_id' => $stockId, 'flag_id' => $flagId]
                );
            }
        }
    }

    /**
     * Delete all flag assignments for a product.
     */
    public function deleteAssignments(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_lifecycle_flag_assignments` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }
}
