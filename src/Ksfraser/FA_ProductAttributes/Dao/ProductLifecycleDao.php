<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for product lifecycle / status flags.
 *
 * One record per stock_id (INSERT or UPDATE on save).
 */
class ProductLifecycleDao
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var string[] */
    private static $columns = [
        'status', 'is_special_order', 'is_clearance', 'is_out_of_stock_notice',
        'is_new_arrival', 'is_bestseller', 'is_featured', 'is_seasonal',
        'available_from', 'discontinue_on', 'clearance_note',
    ];

    /** @var string[] Valid status values */
    private static $validStatuses = ['active', 'draft', 'discontinued', 'archived'];

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
            'SELECT * FROM `' . $p . 'product_lifecycle` WHERE stock_id = :stock_id',
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
            'SELECT stock_id FROM `' . $p . 'product_lifecycle` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );

        $bound = array_merge(['stock_id' => $stockId], $safe);

        if (!empty($existing)) {
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $this->db->execute(
                'UPDATE `' . $p . 'product_lifecycle` SET ' . implode(', ', $sets)
                . ' WHERE stock_id = :stock_id',
                $bound
            );
        } else {
            $cols  = array_keys($bound);
            $names = implode(', ', array_map(function ($c) { return '`' . $c . '`'; }, $cols));
            $phs   = implode(', ', array_map(function ($c) { return ':' . $c; }, $cols));
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_lifecycle` (' . $names . ') VALUES (' . $phs . ')',
                $bound
            );
        }
    }

    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_lifecycle` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterData(array $data): array
    {
        $boolCols = [
            'is_special_order', 'is_clearance', 'is_out_of_stock_notice',
            'is_new_arrival', 'is_bestseller', 'is_featured', 'is_seasonal',
        ];
        $result = [];

        foreach (self::$columns as $col) {
            if (!array_key_exists($col, $data)) {
                continue;
            }
            $val = $data[$col];

            if ($col === 'status') {
                $s            = (string)($val ?? 'active');
                $result[$col] = in_array($s, self::$validStatuses, true) ? $s : 'active';
            } elseif (in_array($col, $boolCols, true)) {
                $result[$col] = (int)(bool)$val;
            } elseif ($col === 'available_from' || $col === 'discontinue_on') {
                $str          = trim((string)($val ?? ''));
                $result[$col] = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) ? $str : null;
            } else {
                $str          = trim((string)($val ?? ''));
                $result[$col] = $str !== '' ? $str : null;
            }
        }

        return $result;
    }
}
