<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use Ksfraser\FA_ProductAttributes\Schema\SchemaManager;

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
    public function listCategories(): array
    {
        $p = $this->db->getTablePrefix();
        display_notification("DAO listCategories: table_prefix='$p'");
        $result = $this->db->query(
            "SELECT * FROM `{$p}product_attribute_categories` ORDER BY sort_order, code"
        );
        display_notification("Query result count: " . count($result));
        return $result;
    }

    public function upsertCategory(string $code, string $label, string $description = '', int $sortOrder = 0, bool $active = true, int $categoryId = 0): void
    {
        $p = $this->db->getTablePrefix();

        display_notification("DAO upsertCategory: table_prefix='$p', code='$code'");

        // If categoryId is provided, this is an update
        if ($categoryId > 0) {
            display_notification("Updating category by ID: $categoryId");
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

        display_notification("Existing categories with code '$code': " . count($existing));

        if (count($existing) > 0) {
            display_notification("Updating existing category");
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

        display_notification("Inserting new category");
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

    /** @return array<int, array<string, mixed>> */
    public function listValues(int $categoryId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug",
            ['category_id' => $categoryId]
        );
    }

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

    public function deleteCategory(int $categoryId): void
    {
        $p = $this->db->getTablePrefix();
        
        // First delete all values for this category
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_values` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );
        
        // Then delete the category itself
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_categories` WHERE id = :id",
            ['id' => $categoryId]
        );
    }

    public function deleteValue(int $valueId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_attribute_values` WHERE id = :id",
            ['id' => $valueId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getAssignedCategoriesForProduct(string $stockId): array
    {
        return $this->listCategoryAssignments($stockId);
    }

    /** @return array<int, array<string, mixed>> */
    public function getValuesForCategory(int $categoryId): array
    {
        return $this->listValues($categoryId);
    }

    public function getVariationCountForProductCategory(string $stockId, int $categoryId): int
    {
        $values = $this->getValuesForCategory($categoryId);
        return count($values);
    }

    /**
     * Get products by their type (simple, variable, variation)
     *
     * @param array $types Array of types to filter by
     * @return array List of products with stock_id, description, and type
     */
    public function getProductsByType(array $types): array
    {
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $sql = "SELECT stock_id, description, '' as type FROM " . $this->db->escapeTableName('stock_master') . "
                WHERE stock_id NOT IN (
                    SELECT DISTINCT parent_stock_id FROM " . $this->db->escapeTableName('product_attribute_assignments') . "
                    WHERE parent_stock_id IS NOT NULL AND parent_stock_id != ''
                )";

        // For now, we'll return all products that are not variations (don't have parent_stock_id)
        // In a real implementation, you'd need to determine the type from some field or logic
        $stmt = $this->db->prepare($sql);
        $stmt->execute([]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all products from the stock_master table
     *
     * @return array List of all products with stock_id and description
     */
    public function getAllProducts(): array
    {
        $sql = "SELECT stock_id, description FROM " . $this->db->escapeTableName('stock_master') . "
                ORDER BY stock_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get the parent product for a variation
     *
     * @param string $stockId The variation product stock ID
     * @return array|null Parent product data or null if not a variation
     */
    public function getProductParent(string $stockId): ?array
    {
        $sql = "SELECT parent_stock_id FROM " . $this->db->escapeTableName('product_attribute_assignments') . "
                WHERE stock_id = ? AND parent_stock_id IS NOT NULL AND parent_stock_id != ''
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result && !empty($result['parent_stock_id'])) {
            // Get parent product details
            $parentSql = "SELECT stock_id, description FROM " . $this->db->escapeTableName('stock_master') . "
                          WHERE stock_id = ?";
            $parentStmt = $this->db->prepare($parentSql);
            $parentStmt->execute([$result['parent_stock_id']]);
            return $parentStmt->fetch(\PDO::FETCH_ASSOC);
        }

        return null;
    }

    /**
     * Get the database adapter
     */
    public function getDbAdapter(): DbAdapterInterface
    {
        return $this->db;
    }

    /**
     * Clear parent relationship for a product
     */
    public function clearParentRelationship(string $stockId): void
    {
        $sql = "UPDATE " . $this->db->escapeTableName('product_attribute_assignments') . "
                SET parent_stock_id = NULL WHERE stock_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
    }

    /**
     * Set parent relationship for a variation
     */
    public function setParentRelationship(string $stockId, string $parentStockId): void
    {
        $sql = "UPDATE " . $this->db->escapeTableName('product_attribute_assignments') . "
                SET parent_stock_id = ? WHERE stock_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentStockId, $stockId]);
    }

    /**
     * Get parent product data from stock_master
     */
    public function getParentProductData(string $stockId): ?array
    {
        $sql = "SELECT * FROM " . $this->db->escapeTableName('stock_master') . " WHERE stock_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
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

        // Build insert query
        $fields = array_keys($childData);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO " . $this->db->escapeTableName('stock_master') . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($childData));
    }

    /**
     * Copy parent's category assignments to child
     */
    public function copyParentCategoryAssignments(string $childStockId, string $parentStockId): void
    {
        $sql = "INSERT INTO " . $this->db->escapeTableName('product_attribute_category_assignments') . "
                (stock_id, category_id)
                SELECT ?, category_id FROM " . $this->db->escapeTableName('product_attribute_category_assignments') . "
                WHERE stock_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$childStockId, $parentStockId]);
    }
}
