<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for product shipping/logistics attributes.
 *
 * One record per stock_id (INSERT or UPDATE on save).
 */
class ShippingAttributesDao
{
    /** @var DbAdapterInterface */
    private $db;

    /**
     * Columns stored in the shipping-attributes table (excluding stock_id and updated_ts).
     * Used to white-list keys from caller-supplied data arrays.
     *
     * @var string[]
     */
    private static $columns = [
        'shipping_class_id',
        'length', 'width', 'height', 'dim_unit',
        'weight', 'weight_unit',
        'is_hazardous', 'hazmat_class', 'un_number', 'proper_shipping_name', 'packing_group',
        'is_fragile', 'is_stackable', 'is_oversize', 'is_perishable',
        'temperature_sensitive', 'temp_min', 'temp_max', 'temp_unit',
        'hs_code', 'country_of_origin', 'customs_description', 'declared_value',
    ];

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch shipping attributes for a product.
     *
     * @return array<string, mixed>|null  Row array, or null if no record exists yet.
     */
    public function get(string $stockId): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            "SELECT * FROM `{$p}product_shipping_attributes` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Insert or update shipping attributes for a product.
     *
     * Only keys present in $data that match the allowed column list are persisted;
     * unknown keys are silently ignored.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(string $stockId, array $data): void
    {
        $p    = $this->db->getTablePrefix();
        $safe = $this->filterData($data);

        // Check whether a record already exists
        $existing = $this->db->query(
            "SELECT stock_id FROM `{$p}product_shipping_attributes` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );

        $bound = array_merge(['stock_id' => $stockId], $safe);

        if (!empty($existing)) {
            // UPDATE
            $sets = [];
            foreach ($safe as $col => $val) {
                $sets[] = "`{$col}` = :{$col}";
            }
            $this->db->execute(
                "UPDATE `{$p}product_shipping_attributes`\n"
                . "SET " . implode(', ', $sets) . "\n"
                . "WHERE stock_id = :stock_id",
                $bound
            );
        } else {
            // INSERT
            $cols  = array_keys($bound);
            $names = implode(', ', array_map(function ($c) { return "`{$c}`"; }, $cols));
            $phs   = implode(', ', array_map(function ($c) { return ":{$c}"; }, $cols));
            $this->db->execute(
                'INSERT INTO `' . $p . 'product_shipping_attributes` (' . $names . ')' . "\n"
                . 'VALUES (' . $phs . ')',
                $bound
            );
        }
    }

    /**
     * Remove the shipping-attributes record for a product (called on item delete).
     */
    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            "DELETE FROM `{$p}product_shipping_attributes` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
    }

    /**
     * Strip any keys from $data that are not in the allowed column list.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterData(array $data): array
    {
        $out = [];
        foreach (self::$columns as $col) {
            if (array_key_exists($col, $data)) {
                if ($col === 'shipping_class_id') {
                    $out[$col] = (int)$data[$col];
                    continue;
                }
                $out[$col] = $data[$col];
            }
        }
        return $out;
    }
}
