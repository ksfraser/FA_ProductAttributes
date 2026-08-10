<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Persistence for the per-product Square measurement
 * unit mapping (`item_variation_data.measurement_unit_id`).
 *
 * One record per stock_id; a missing row means "use the platform default".
 *
 * @since 1.1.0
 */
class ProductMeasurementUnitsDao
{
    /** @var DbAdapterInterface */
    private $db;

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
            'SELECT * FROM `' . $p . 'product_measurement_units` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Set the measurement unit mapping; null removes it.
     */
    public function upsert(string $stockId, ?string $measurementUnitId): void
    {
        $p = $this->db->getTablePrefix();
        if ($measurementUnitId === null || trim($measurementUnitId) === '') {
            $this->delete($stockId);
            return;
        }

        $this->db->execute(
            'INSERT INTO `' . $p . 'product_measurement_units` (stock_id, measurement_unit_id)'
            . ' VALUES (:stock_id, :measurement_unit_id)'
            . ' ON DUPLICATE KEY UPDATE measurement_unit_id = :measurement_unit_id',
            ['stock_id' => $stockId, 'measurement_unit_id' => trim($measurementUnitId)]
        );
    }

    /**
     * Remove the mapping (called on item delete).
     */
    public function delete(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_measurement_units` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }
}
