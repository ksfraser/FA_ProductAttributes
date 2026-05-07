<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Manages the global product-tag dictionary and
 * per-product tag assignments.
 *
 * Tables:
 *   0_product_tags              — global tag definitions (id, name, slug)
 *   0_product_tag_assignments   — stock_id ↔ tag_id many-to-many
 */
class ProductTagsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    // ── Global tag management ─────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTags(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT id, name, slug FROM `' . $p . 'product_tags` ORDER BY name'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTag(int $tagId): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT id, name, slug FROM `' . $p . 'product_tags` WHERE id = :id',
            ['id' => $tagId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Create or update a global tag.
     * Pass $tagId > 0 to update, 0 to create.
     */
    public function upsertTag(string $name, string $slug, int $tagId = 0): void
    {
        $p = $this->db->getTablePrefix();
        if ($tagId > 0) {
            $this->db->execute(
                'UPDATE `' . $p . 'product_tags` SET name = :name, slug = :slug WHERE id = :id',
                ['name' => $name, 'slug' => $slug, 'id' => $tagId]
            );
        } else {
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_tags` (name, slug) VALUES (:name, :slug)',
                ['name' => $name, 'slug' => $slug]
            );
        }
    }

    public function deleteTag(int $tagId): void
    {
        $p = $this->db->getTablePrefix();
        // Remove all assignments first
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_tag_assignments` WHERE tag_id = :tag_id',
            ['tag_id' => $tagId]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_tags` WHERE id = :id',
            ['id' => $tagId]
        );
    }

    // ── Per-product tag assignment ────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>  Each row has id, name, slug
     */
    public function getProductTags(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT t.id, t.name, t.slug'
            . ' FROM `' . $p . 'product_tags` t'
            . ' INNER JOIN `' . $p . 'product_tag_assignments` a ON a.tag_id = t.id'
            . ' WHERE a.stock_id = :stock_id'
            . ' ORDER BY t.name',
            ['stock_id' => $stockId]
        );
    }

    /**
     * Assign a tag to a product (idempotent — duplicate silently skipped).
     */
    public function addAssignment(string $stockId, int $tagId): void
    {
        $p = $this->db->getTablePrefix();
        // Check for existing assignment first
        $exists = $this->db->query(
            'SELECT 1 FROM `' . $p . 'product_tag_assignments`'
            . ' WHERE stock_id = :stock_id AND tag_id = :tag_id',
            ['stock_id' => $stockId, 'tag_id' => $tagId]
        );
        if (empty($exists)) {
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_tag_assignments` (stock_id, tag_id)'
                . ' VALUES (:stock_id, :tag_id)',
                ['stock_id' => $stockId, 'tag_id' => $tagId]
            );
        }
    }

    /**
     * Remove a tag assignment from a product.
     */
    public function removeAssignment(string $stockId, int $tagId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_tag_assignments`'
            . ' WHERE stock_id = :stock_id AND tag_id = :tag_id',
            ['stock_id' => $stockId, 'tag_id' => $tagId]
        );
    }

    /**
     * Replace all tag assignments for a product in one operation.
     *
     * @param int[] $tagIds
     */
    public function syncAssignments(string $stockId, array $tagIds): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_tag_assignments` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        foreach ($tagIds as $tagId) {
            $tagId = (int)$tagId;
            if ($tagId > 0) {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_tag_assignments` (stock_id, tag_id)'
                    . ' VALUES (:stock_id, :tag_id)',
                    ['stock_id' => $stockId, 'tag_id' => $tagId]
                );
            }
        }
    }
}
