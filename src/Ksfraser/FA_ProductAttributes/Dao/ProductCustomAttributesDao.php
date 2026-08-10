<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for free-form product custom attributes
 * (key/value pairs). Maps to Square `custom_attribute_values` and WooCommerce
 * `meta_data`.
 *
 * One row per (stock_id, attr_key).
 *
 * @since 1.1.0
 */
class ProductCustomAttributesDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Return all custom attributes for a product.
     *
     * @return array<int, array<string, mixed>> Rows with attr_key / attr_value
     */
    public function get(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT attr_key, attr_value FROM `' . $p . 'product_custom_attributes`'
            . ' WHERE stock_id = :stock_id ORDER BY attr_key',
            ['stock_id' => $stockId]
        );
    }

    /**
     * Fetch a single attribute value, or null when unset.
     */
    public function getValue(string $stockId, string $key): ?string
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT attr_value FROM `' . $p . 'product_custom_attributes`'
            . ' WHERE stock_id = :stock_id AND attr_key = :attr_key',
            ['stock_id' => $stockId, 'attr_key' => $key]
        );
        return !empty($rows) ? (string)$rows[0]['attr_value'] : null;
    }

    /**
     * Set a single attribute value (insert or update).
     */
    public function set(string $stockId, string $key, string $value): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_custom_attributes` (stock_id, attr_key, attr_value)'
            . ' VALUES (:stock_id, :attr_key, :attr_value)'
            . ' ON DUPLICATE KEY UPDATE attr_value = :attr_value',
            ['stock_id' => $stockId, 'attr_key' => $key, 'attr_value' => $value]
        );
    }

    /**
     * Remove a single attribute key.
     */
    public function remove(string $stockId, string $key): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_custom_attributes`'
            . ' WHERE stock_id = :stock_id AND attr_key = :attr_key',
            ['stock_id' => $stockId, 'attr_key' => $key]
        );
    }

    /**
     * Remove every custom attribute for a product (called on item delete).
     */
    public function removeAll(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_custom_attributes` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }

    /**
     * Replace the full attribute map for a product in one operation.
     *
     * @param array<string, string> $attributes Key => value map
     */
    public function sync(string $stockId, array $attributes): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_custom_attributes` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );

        foreach ($attributes as $key => $value) {
            $key   = (string)$key;
            $value = trim((string)($value ?? ''));
            if ($key !== '' && $value !== '') {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_custom_attributes` (stock_id, attr_key, attr_value)'
                    . ' VALUES (:stock_id, :attr_key, :attr_value)',
                    ['stock_id' => $stockId, 'attr_key' => $key, 'attr_value' => $value]
                );
            }
        }
    }
}
