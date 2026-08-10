<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for the product shipping-class registry.
 * Maps to WooCommerce `/products/shipping_classes` and per-product
 * `shipping_class` (via product_shipping_attributes.shipping_class_id).
 *
 * @since 1.1.0
 */
class ProductShippingClassesDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_shipping_classes` ORDER BY sort_order, name'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_shipping_classes` WHERE id = :id',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBySlug(string $slug): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_shipping_classes` WHERE slug = :slug',
            ['slug' => $slug]
        );
        return $rows[0] ?? null;
    }

    /**
     * Create or update a shipping class. Pass $id > 0 to update, 0 to create.
     */
    public function upsert(string $name, string $slug, string $description, int $sortOrder, bool $active, int $id = 0): int
    {
        $p = $this->db->getTablePrefix();
        if ($id > 0) {
            $this->db->execute(
                'UPDATE `' . $p . 'product_shipping_classes`'
                . ' SET name = :name, slug = :slug, description = :description,'
                . ' sort_order = :sort_order, active = :active WHERE id = :id',
                [
                    'id' => $id,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'active' => (int)$active,
                ]
            );
            return $id;
        }

        $this->db->execute(
            'INSERT INTO `' . $p . 'product_shipping_classes`'
            . ' (name, slug, description, sort_order, active)'
            . ' VALUES (:name, :slug, :description, :sort_order, :active)',
            [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
                'active' => (int)$active,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Remove a shipping class after clearing per-product references.
     */
    public function delete(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'UPDATE `' . $p . 'product_shipping_attributes` SET shipping_class_id = NULL'
            . ' WHERE shipping_class_id = :shipping_class_id',
            ['shipping_class_id' => $id]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_shipping_classes` WHERE id = :id',
            ['id' => $id]
        );
    }
}
