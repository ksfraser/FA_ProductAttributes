<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for product relationship / recommendation
 * links. Maps to WooCommerce `upsell_ids` and `cross_sell_ids`.
 *
 * Tables:
 *   0_product_related_products — stock_id ↔ related_stock_id with a relation type.
 *
 * @since 1.1.0
 */
class ProductRelatedProductsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Related products for a product, optionally filtered by relation type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(string $stockId, ?string $relationType = null): array
    {
        $p = $this->db->getTablePrefix();
        if ($relationType === null) {
            return $this->db->query(
                'SELECT * FROM `' . $p . 'product_related_products` WHERE stock_id = :stock_id'
                . ' ORDER BY relation_type, sort_order, related_stock_id',
                ['stock_id' => $stockId]
            );
        }

        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_related_products`'
            . ' WHERE stock_id = :stock_id AND relation_type = :relation_type'
            . ' ORDER BY sort_order, related_stock_id',
            ['stock_id' => $stockId, 'relation_type' => $relationType]
        );
    }

    /**
     * All related products for a product regardless of type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(string $stockId): array
    {
        return $this->list($stockId, null);
    }

    /**
     * Add a relationship (idempotent — duplicates update order).
     */
    public function add(string $stockId, string $relatedStockId, string $relationType, int $sortOrder = 0): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_related_products`'
            . ' (stock_id, related_stock_id, relation_type, sort_order)'
            . ' VALUES (:stock_id, :related_stock_id, :relation_type, :sort_order)'
            . ' ON DUPLICATE KEY UPDATE sort_order = :sort_order',
            [
                'stock_id' => $stockId,
                'related_stock_id' => $relatedStockId,
                'relation_type' => $relationType,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function remove(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_related_products` WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Remove a specific stock↔stock relationship.
     */
    public function removeByPair(string $stockId, string $relatedStockId, string $relationType): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_related_products`'
            . ' WHERE stock_id = :stock_id AND related_stock_id = :related_stock_id'
            . ' AND relation_type = :relation_type',
            ['stock_id' => $stockId, 'related_stock_id' => $relatedStockId, 'relation_type' => $relationType]
        );
    }

    /**
     * Replace all relationships of a type for a product in one operation.
     *
     * @param string[] $relatedStockIds
     */
    public function sync(string $stockId, string $relationType, array $relatedStockIds): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_related_products`'
            . ' WHERE stock_id = :stock_id AND relation_type = :relation_type',
            ['stock_id' => $stockId, 'relation_type' => $relationType]
        );

        foreach ($relatedStockIds as $index => $relatedStockId) {
            $relatedStockId = trim((string)$relatedStockId);
            if ($relatedStockId !== '') {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_related_products`'
                    . ' (stock_id, related_stock_id, relation_type, sort_order)'
                    . ' VALUES (:stock_id, :related_stock_id, :relation_type, :sort_order)',
                    [
                        'stock_id' => $stockId,
                        'related_stock_id' => $relatedStockId,
                        'relation_type' => $relationType,
                        'sort_order' => (int)$index,
                    ]
                );
            }
        }
    }
}
