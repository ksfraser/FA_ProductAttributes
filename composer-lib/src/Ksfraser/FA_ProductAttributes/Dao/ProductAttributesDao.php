<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\SchemaManager\SchemaManager;

class ProductAttributesDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var SchemaManager */
    private $schema;

    public function __construct(DbAdapterInterface $db, ?SchemaManager $schema = null)
    {
        $this->db = $db;
        $this->schema = $schema ?? new SchemaManager();
    }

    public function ensureSchema(): void
    {
        $this->schema->ensureSchema($this->db);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAssignments(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\n"
            . "FROM `{$p}product_attribute_assignments` a\n"
            . "JOIN `{$p}product_attribute_categories` c ON c.id = a.category_id\n"
            . "JOIN `{$p}product_attribute_values` v ON v.id = a.value_id\n"
            . "WHERE a.stock_id = :stock_id\n"
            . "ORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
            ['stock_id' => $stockId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listCategoryAssignments(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT c.* FROM `{$p}product_attribute_categories` c
             INNER JOIN `{$p}product_attribute_category_assignments` pca ON c.id = pca.category_id
             WHERE pca.stock_id = :stock_id
             ORDER BY c.sort_order, c.code",
            ['stock_id' => $stockId]
        );
    }

    public function addCategoryAssignment(string $stockId, int $categoryId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_category_assignments` (stock_id, category_id)
             VALUES (:stock_id, :category_id)",
            ['stock_id' => $stockId, 'category_id' => $categoryId]
        );
    }

    public function removeCategoryAssignment(string $stockId, int $categoryId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_category_assignments`
             WHERE stock_id = :stock_id AND category_id = :category_id",
            ['stock_id' => $stockId, 'category_id' => $categoryId]
        );
    }

    public function addAssignment(string $stockId, int $categoryId, int $valueId, int $sortOrder = 0): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_assignments` (stock_id, category_id, value_id, sort_order)\n"
            . "VALUES (:stock_id, :category_id, :value_id, :sort_order)",
            [
                'stock_id' => $stockId,
                'category_id' => $categoryId,
                'value_id' => $valueId,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_assignments` WHERE id = :id",
            ['id' => $assignmentId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getAssignedCategoriesForProduct(string $stockId): array
    {
        return $this->listCategoryAssignments($stockId);
    }

    public function getVariationCountForProductCategory(string $stockId, int $categoryId): int
    {
        // This method is deprecated - variations are now handled by VariationsDao
        // For backward compatibility, return 0
        return 0;
    }

    /**
     * Get products by their type (simple, variable, variation)
     *
     * @param array $types Array of types to filter by
     * @return array List of products with stock_id, description, and type
     */
    public function getProductsByType(array $types): array
    {
        $p = $this->db->getTablePrefix();
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $sql = "SELECT stock_id, description FROM `{$p}stock_master`
                WHERE mb_flag IN ({$placeholders})";

        // For now, we'll return all products that are not variations (don't have parent_stock_id)
        // In a real implementation, you'd need to determine the type from some field or logic
        return $this->db->query($sql, $types);
    }

    /**
     * Get all products from the stock_master table
     *
     * @return array List of all products with stock_id and description
     */
    public function getAllProducts(): array
    {
        $p = $this->db->getTablePrefix();
        $sql = "SELECT stock_id, description FROM `{$p}stock_master`
                ORDER BY stock_id";
        return $this->db->query($sql);
    }

    /**
     * Get the parent stock_id for a product
     */
    public function getProductParent(string $stockId): ?string
    {
        $p = $this->db->getTablePrefix();
        $result = $this->db->query("SELECT parent_stock_id FROM `{$p}product_hierarchy` WHERE child_stock_id = :child", ['child' => $stockId]);
        return $result[0]['parent_stock_id'] ?? null;
    }

    /**
     * Set the parent for a product
     */
    public function setProductParent(string $stockId, ?string $parentStockId): void
    {
        $p = $this->db->getTablePrefix();
        if ($parentStockId === null) {
            // Remove parent
            $this->db->execute("DELETE FROM `{$p}product_hierarchy` WHERE child_stock_id = :child", ['child' => $stockId]);
        } else {
            // Insert or update
            $this->db->execute("INSERT INTO `{$p}product_hierarchy` (child_stock_id, parent_stock_id) VALUES (:child, :parent) ON DUPLICATE KEY UPDATE parent_stock_id = :parent", ['child' => $stockId, 'parent' => $parentStockId]);
        }
    }

    /**
     * Get the database adapter
     */
    public function getDbAdapter(): DbAdapterInterface
    {
        return $this->db;
    }
}
