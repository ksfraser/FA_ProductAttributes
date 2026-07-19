<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for product warranty information.
 *
 * One record per stock_id (INSERT or UPDATE on save).
 */
class ProductWarrantyDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var string[] */
    private static $columns = [
        'warranty_type',
        'manufacturer_duration', 'manufacturer_duration_unit',
        'extended_duration', 'extended_duration_unit',
        'third_party_duration', 'third_party_duration_unit',
        'lifetime_notes', 'warranty_notes',
    ];

    /** @var string[] */
    private static $validTypes = ['none', 'manufacturer', 'extended', 'third_party', 'lifetime'];

    /** @var string[] */
    private static $validUnits = ['days', 'months', 'years'];

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
            'SELECT * FROM `' . $p . 'product_warranty` WHERE stock_id = :stock_id',
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
            'SELECT stock_id FROM `' . $p . 'product_warranty` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );

        $bound = array_merge(['stock_id' => $stockId], $safe);

        if (!empty($existing)) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_warranty` SET ' . implode(', ', $sets)
                . ' WHERE stock_id = :stock_id',
                $bound
            );
        } else {
            $cols  = array_keys($bound);
            $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
            $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_warranty` (' . $names . ') VALUES (' . $phs . ')',
                $bound
            );
        }
    }

    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_warranty` WHERE stock_id = :stock_id',
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
            if (!array_key_exists($col, $data)) {
                continue;
            }
            $val = $data[$col];

            if ($col === 'warranty_type') {
                $s = (string)($val ?? 'none');
                $result[$col] = in_array($s, self::$validTypes, true) ? $s : 'none';
            } elseif (in_array($col, ['manufacturer_duration_unit', 'extended_duration_unit', 'third_party_duration_unit'], true)) {
                $s = (string)($val ?? 'months');
                $result[$col] = in_array($s, self::$validUnits, true) ? $s : 'months';
            } elseif (in_array($col, ['manufacturer_duration', 'extended_duration', 'third_party_duration'], true)) {
                $result[$col] = ($val !== null && $val !== '' && is_numeric($val)) ? (int)$val : null;
            } else {
                $str = trim((string)($val ?? ''));
                $result[$col] = $str !== '' ? $str : null;
            }
        }
        return $result;
    }
}
