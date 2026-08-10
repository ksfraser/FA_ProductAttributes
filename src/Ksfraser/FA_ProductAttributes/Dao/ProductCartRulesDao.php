<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for per-product cart rules.
 * Maps to WooCommerce `sold_individually`.
 *
 * One record per stock_id (INSERT or UPDATE on save).
 *
 * @since 1.1.0
 */
class ProductCartRulesDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var string[] Columns stored in the table (excluding stock_id / updated_ts). */
    private static $columns = [
        'sold_individually',
    ];

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $stockId): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_cart_rules` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Insert or update cart rules for a product.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(string $stockId, array $data): void
    {
        $p    = $this->db->getTablePrefix();
        $safe = $this->filterData($data);

        $existing = $this->db->query(
            'SELECT stock_id FROM `' . $p . 'product_cart_rules` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );

        $bound = array_merge(['stock_id' => $stockId], $safe);

        if (!empty($existing)) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_cart_rules` SET ' . implode(', ', $sets)
                . ' WHERE stock_id = :stock_id',
                $bound
            );
        } else {
            $cols  = array_keys($bound);
            $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
            $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_cart_rules` (' . $names . ') VALUES (' . $phs . ')',
                $bound
            );
        }
    }

    /**
     * Remove the cart-rules record for a product (called on item delete).
     */
    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_cart_rules` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterData(array $data): array
    {
        $out = [];
        foreach (self::$columns as $col) {
            if (array_key_exists($col, $data)) {
                $out[$col] = $data[$col];
            }
        }
        return $out;
    }
}
