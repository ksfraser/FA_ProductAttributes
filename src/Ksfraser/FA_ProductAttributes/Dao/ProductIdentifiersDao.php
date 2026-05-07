<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for product identifier / barcode data.
 *
 * One record per stock_id (INSERT or UPDATE on save).
 */
class ProductIdentifiersDao
{
    /** @var DbAdapterInterface */
    private $db;

    /**
     * @var string[]  Columns stored in the table (excluding stock_id / updated_ts).
     */
    private static $columns = [
        'brand', 'manufacturer', 'mpn', 'gtin', 'ean', 'upc',
        'isbn', 'asin', 'internal_barcode', 'supplier_part_no', 'model_no',
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
            'SELECT * FROM `' . $p . 'product_identifiers` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsert(string $stockId, array $data): void
    {
        $p    = $this->db->getTablePrefix();
        $safe = $this->filterData($data);

        $existing = $this->db->query(
            'SELECT stock_id FROM `' . $p . 'product_identifiers` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );

        $bound = array_merge(['stock_id' => $stockId], $safe);

        if (!empty($existing)) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_identifiers` SET ' . implode(', ', $sets)
                . ' WHERE stock_id = :stock_id',
                $bound
            );
        } else {
            $cols  = array_keys($bound);
            $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
            $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_identifiers` (' . $names . ') VALUES (' . $phs . ')',
                $bound
            );
        }
    }

    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_identifiers` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterData(array $data): array
    {
        $result = [];
        foreach (self::$columns as $col) {
            if (array_key_exists($col, $data)) {
                $val           = $data[$col];
                $str           = trim((string)($val ?? ''));
                $result[$col]  = $str !== '' ? $str : null;
            }
        }
        return $result;
    }
}
