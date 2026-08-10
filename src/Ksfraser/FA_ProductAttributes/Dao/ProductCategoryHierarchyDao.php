<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for FA stock-category hierarchy.
 * Maps to Square CatalogCategory `parent_category` / `is_top_level`.
 *
 * One row per child category_id (INSERT or DELETE on save).
 *
 * @since 1.1.0
 */
class ProductCategoryHierarchyDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Parent category id for a category, or null when it is a top-level category.
     */
    public function getParent(int $categoryId): ?int
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT parent_category_id FROM `' . $p . 'product_category_hierarchy`'
            . ' WHERE category_id = :category_id',
            ['category_id' => $categoryId]
        );
        if (empty($rows) || $rows[0]['parent_category_id'] === null) {
            return null;
        }
        return (int)$rows[0]['parent_category_id'];
    }

    /**
     * Set or clear the parent of a category. Passing null removes the mapping.
     */
    public function setParent(int $categoryId, ?int $parentCategoryId): void
    {
        $p = $this->db->getTablePrefix();
        if ($parentCategoryId === null) {
            $this->db->execute(
                'DELETE FROM `' . $p . 'product_category_hierarchy` WHERE category_id = :category_id',
                ['category_id' => $categoryId]
            );
            return;
        }

        $this->db->execute(
            'INSERT INTO `' . $p . 'product_category_hierarchy`'
            . ' (category_id, parent_category_id) VALUES (:category_id, :parent_category_id)'
            . ' ON DUPLICATE KEY UPDATE parent_category_id = :parent_category_id2',
            ['category_id' => $categoryId, 'parent_category_id' => $parentCategoryId, 'parent_category_id2' => $parentCategoryId]
        );
    }

    /**
     * Direct child category ids of a parent category.
     *
     * @return int[]
     */
    public function listChildren(int $parentCategoryId): array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT category_id FROM `' . $p . 'product_category_hierarchy`'
            . ' WHERE parent_category_id = :parent_category_id ORDER BY category_id',
            ['parent_category_id' => $parentCategoryId]
        );
        return array_map(function ($row) {
            return (int)$row['category_id'];
        }, $rows);
    }
}
