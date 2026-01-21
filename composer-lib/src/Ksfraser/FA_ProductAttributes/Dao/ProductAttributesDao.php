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
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_categories` ORDER BY sort_order, code"
        );
    }

    public function upsertCategory(string $code, string $label, string $description = '', int $sortOrder = 0, bool $active = true): void
    {
        $p = $this->db->getTablePrefix();

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

    /** @return array<int, array<string, mixed>> */
    public function listValues(int $categoryId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT * FROM `{$p}product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug",
            ['category_id' => $categoryId]
        );
    }

    public function upsertValue(int $categoryId, string $value, string $slug, int $sortOrder = 0, bool $active = true): void
    {
        $p = $this->db->getTablePrefix();

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
}
