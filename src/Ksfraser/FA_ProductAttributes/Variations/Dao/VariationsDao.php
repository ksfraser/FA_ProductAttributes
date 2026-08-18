<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Data Access Object for Product Variations
 *
 * Handles variation-specific database operations including parent-child relationships.
 * This DAO is responsible for the variations plugin's database schema and operations.
 */
class VariationsDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var ProductAttributesDao */
    private $coreDao;

    public function __construct(DbAdapterInterface $db, ProductAttributesDao $coreDao)
    {
        $this->db = $db;
        $this->coreDao = $coreDao;
    }

    /**
     * Ensure variations schema exists
     * This should be called during plugin installation/activation
     */
    public function ensureVariationsSchema(): void
    {
        $p = $this->db->getTablePrefix();

        // Add parent_stock_id column to product_attribute_assignments if it doesn't exist
        try {
            $this->db->execute("
                ALTER TABLE `{$p}product_attribute_assignments`
                ADD COLUMN `parent_stock_id` VARCHAR(50) NULL DEFAULT NULL
            ");
        } catch (\Exception $e) {
            // Column might already exist, ignore
            if (strpos($e->getMessage(), 'Duplicate column') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }

        // Add index for performance
        try {
            $this->db->execute("
                ALTER TABLE `{$p}product_attribute_assignments`
                ADD INDEX `idx_parent_stock_id` (`parent_stock_id`)
            ");
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * List all variation categories (Color, Size, Material, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function listCategories(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_categories` ORDER BY sort_order, code"
        );
    }

    /**
     * Create or update a variation category
     */
    public function upsertCategory(string $code, string $label, string $description = '', int $sortOrder = 0, bool $active = true, int $categoryId = 0): void
    {
        $p = $this->db->getTablePrefix();

        // If categoryId is provided, this is an update
        if ($categoryId > 0) {
            $this->db->execute(
                "UPDATE `{$p}product_attribute_categories`\n"
                . "SET code = :code, label = :label, description = :description, sort_order = :sort_order, active = :active\n"
                . "WHERE id = :id",
                [
                    'id' => $categoryId,
                    'code' => $code,
                    'label' => $label,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'active' => $active ? 1 : 0,
                ]
            );
            return;
        }

        $existing = $this->db->query(
            "SELECT id FROM `{$p}product_attribute_categories` WHERE code = :code",
            ['code' => $code]
        );

        if (count($existing) > 0) {
            $this->db->execute(
                "UPDATE `{$p}product_attribute_categories`\n"
                . "SET label = :label, description = :description, sort_order = :sort_order, active = :active\n"
                . "WHERE code = :code",
                [
                    'code' => $code,
                    'label' => $label,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'active' => $active ? 1 : 0,
                ]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_categories` (code, label, description, sort_order, active)\n"
            . "VALUES (:code, :label, :description, :sort_order, :active)",
            [
                'code' => $code,
                'label' => $label,
                'description' => $description,
                'sort_order' => $sortOrder,
                'active' => $active ? 1 : 0,
            ]
        );
    }

    /**
     * List all values for a variation category
     * @return array<int, array<string, mixed>>
     */
    public function listValues(int $categoryId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug",
            ['category_id' => $categoryId]
        );
    }

    /**
     * List only active values for a variation category (used by generation).
     * @return array<int, array<string, mixed>>
     */
    public function listActiveValues(int $categoryId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_values` WHERE category_id = :category_id AND active = 1 ORDER BY sort_order, slug",
            ['category_id' => $categoryId]
        );
    }

    /**
     * Create or update a variation value
     */
    public function upsertValue(int $categoryId, string $value, string $slug, int $sortOrder = 0, bool $active = true, int $valueId = 0): void
    {
        $p = $this->db->getTablePrefix();

        // If valueId is provided, this is an update
        if ($valueId > 0) {
            $this->db->execute(
                "UPDATE `{$p}product_attribute_values`\n"
                . "SET value = :value, slug = :slug, sort_order = :sort_order, active = :active\n"
                . "WHERE id = :id",
                [
                    'id' => $valueId,
                    'value' => $value,
                    'slug' => $slug,
                    'sort_order' => $sortOrder,
                    'active' => $active ? 1 : 0,
                ]
            );
            return;
        }

        // Check if value already exists for insert
        $existing = $this->db->query(
            "SELECT id FROM `{$p}product_attribute_values` WHERE category_id = :category_id AND slug = :slug",
            ['category_id' => $categoryId, 'slug' => $slug]
        );

        if (count($existing) > 0) {
            $this->db->execute(
                "UPDATE `{$p}product_attribute_values`\n"
                . "SET value = :value, sort_order = :sort_order, active = :active\n"
                . "WHERE category_id = :category_id AND slug = :slug",
                [
                    'category_id' => $categoryId,
                    'slug' => $slug,
                    'value' => $value,
                    'sort_order' => $sortOrder,
                    'active' => $active ? 1 : 0,
                ]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_values` (category_id, value, slug, sort_order, active)\n"
            . "VALUES (:category_id, :value, :slug, :sort_order, :active)",
            [
                'category_id' => $categoryId,
                'value' => $value,
                'slug' => $slug,
                'sort_order' => $sortOrder,
                'active' => $active ? 1 : 0,
            ]
        );
    }

    /**
     * List category assignments for a product
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Add a category assignment to a product
     */
    public function addCategoryAssignment(string $stockId, int $categoryId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_category_assignments` (stock_id, category_id)
             VALUES (:stock_id, :category_id)",
            ['stock_id' => $stockId, 'category_id' => $categoryId]
        );
    }

    /**
     * Remove a category assignment from a product
     */
    public function removeCategoryAssignment(string $stockId, int $categoryId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_category_assignments`
             WHERE stock_id = :stock_id AND category_id = :category_id",
            ['stock_id' => $stockId, 'category_id' => $categoryId]
        );
    }

    /**
     * Delete a category and all its related data
     */
    public function deleteCategory(int $categoryId): void
    {
        $p = $this->db->getTablePrefix();

        // First delete all assignments for this category
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_assignments` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );

        // Then delete all values for this category
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_values` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );

        // Then delete all category assignments
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_category_assignments` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );

        // Finally delete the category itself
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_categories` WHERE id = :id",
            ['id' => $categoryId]
        );
    }

    /**
     * Get the parent product for a variation
     *
     * @param string $stockId The variation product stock ID
     * @return array|null Parent product data or null if not a variation
     */
    public function getProductParent(string $stockId): ?array
    {
        $p = $this->db->getTablePrefix();

        $sql = "SELECT parent_stock_id FROM `{$p}product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != ''
                LIMIT 1";
        $result = $this->db->query($sql, ['stock_id' => $stockId]);

        if (!empty($result)) {
            $parentStockId = $result[0]['parent_stock_id'];
            // Get parent product details
            $parentSql = "SELECT stock_id, description FROM `{$p}stock_master`
                          WHERE stock_id = :stock_id";
            $parentResult = $this->db->query($parentSql, ['stock_id' => $parentStockId]);

            return $parentResult[0] ?? null;
        }

        return null;
    }

    /**
     * Clear parent relationship for a product
     */
    public function clearParentRelationship(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "UPDATE `{$p}product_attribute_assignments` SET parent_stock_id = NULL WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
    }

    /**
     * Set parent relationship for a variation
     */
    public function setParentRelationship(string $stockId, string $parentStockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "UPDATE `{$p}product_attribute_assignments` SET parent_stock_id = :parent_stock_id WHERE stock_id = :stock_id",
            ['parent_stock_id' => $parentStockId, 'stock_id' => $stockId]
        );
    }

    /**
     * Get parent product data from stock_master
     */
    public function getParentProductData(string $stockId): ?array
    {
        $p = $this->db->getTablePrefix();
        $result = $this->db->query(
            "SELECT * FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return $result[0] ?? null;
    }

    /**
     * Create child product in stock_master
     */
    public function createChildProduct(string $childStockId, array $parentData): void
    {
        // Copy most fields from parent, but modify description and set as service item (variation)
        $childData = $parentData;
        $childData['stock_id'] = $childStockId;
        $childData['description'] = $parentData['description'] . ' (Variation)';
        $childData['long_description'] = ($parentData['long_description'] ?? '') . ' - Variation of ' . $parentData['stock_id'];
        $childData['mb_flag'] = 'D'; // Dimension/service item for variations

        // Remove fields that shouldn't be copied
        unset($childData['inactive']);

        $p = $this->db->getTablePrefix();
        $fields = array_keys($childData);
        $placeholders = array_map(function($field) { return ':' . $field; }, $fields);

        $sql = "INSERT INTO `{$p}stock_master` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->execute($sql, $childData);
    }

    /**
     * Copy parent's category assignments to child
     */
    public function copyParentCategoryAssignments(string $childStockId, string $parentStockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "INSERT INTO `{$p}product_attribute_category_assignments` (stock_id, category_id)
             SELECT :child_stock_id, category_id FROM `{$p}product_attribute_category_assignments`
             WHERE stock_id = :parent_stock_id",
            ['child_stock_id' => $childStockId, 'parent_stock_id' => $parentStockId]
        );
    }

    /**
     * Get all variations for a parent product
     *
     * @param string $parentStockId The parent product stock ID
     * @return array<int, array<string, mixed>> Array of variation data
     */
    public function getProductVariations(string $parentStockId): array
    {
        $p = $this->db->getTablePrefix();
        $sql = "SELECT stock_id, description FROM `{$p}stock_master`
                WHERE stock_id IN (
                    SELECT stock_id FROM `{$p}product_attribute_assignments`
                    WHERE parent_stock_id = :parent_stock_id
                )";
        return $this->db->query($sql, ['parent_stock_id' => $parentStockId]);
    }

    /**
     * Check if a product is a variation (has a parent)
     *
     * @param string $stockId The product stock ID
     * @return bool True if product is a variation
     */
    public function isVariation(string $stockId): bool
    {
        $p = $this->db->getTablePrefix();
        $sql = "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != ''";
        $result = $this->db->query($sql, ['stock_id' => $stockId]);
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * List all assignments for a product with full category and value details
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Add an attribute assignment to a product
     */
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

    /**
     * Delete an attribute assignment
     */
    public function deleteAssignment(int $assignmentId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_assignments` WHERE id = :id",
            ['id' => $assignmentId]
        );
    }

    /**
     * Hard-delete a value and all of its assignments.
     */
    public function deleteValue(int $id): void
    {
        $p = $this->db->getTablePrefix();
        // Remove dependent assignments first
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_assignments` WHERE value_id = :value_id",
            ['value_id' => $id]
        );
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_values` WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Return a distinct list of parent_stock_id values from the assignments table.
     * Used to populate the "Assign Parent" dropdown for simple products.
     *
     * @return array<int, array<string, string>>  e.g. [['parent_stock_id' => 'ABC'], ...]
     */
    public function getParentStockIds(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT DISTINCT parent_stock_id FROM `{$p}product_attribute_assignments`"
            . " WHERE parent_stock_id IS NOT NULL AND parent_stock_id != ''
            ORDER BY parent_stock_id"
        );
    }
}