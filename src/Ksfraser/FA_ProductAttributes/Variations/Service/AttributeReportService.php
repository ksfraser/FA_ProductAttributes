<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Service;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Reporting service for product attributes and variation data integrity (BR7, BR7.1).
 *
 * Provides:
 *  - getProductsWithAttributes(): list every product that has at least one category assignment
 *    together with the category/value details.
 *  - validateInactiveParents(): identify inconsistencies where a parent is inactive but
 *    one or more of its variations still carry stock-on-hand.
 */
class AttributeReportService
{
    /** @var VariationsDao */
    private $variationsDao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(VariationsDao $variationsDao, DbAdapterInterface $db)
    {
        $this->variationsDao = $variationsDao;
        $this->db            = $db;
    }

    /**
     * Return all parent stock IDs that have at least one attribute assignment,
     * together with a summary of their assignments.
     *
     * Each element of the returned array has the shape:
     *   [
     *     'stock_id'    => string,
     *     'assignments' => array<int, array<string, mixed>>,
     *   ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductsWithAttributes(): array
    {
        $parentIds = $this->variationsDao->getParentStockIds();
        $results   = [];

        foreach ($parentIds as $row) {
            $stockId = is_array($row) ? ($row['parent_stock_id'] ?? $row['stock_id'] ?? '') : $row;

            if ($stockId === '') {
                continue;
            }

            $assignments = $this->variationsDao->listAssignments($stockId);

            if (!empty($assignments)) {
                $results[] = [
                    'stock_id'    => $stockId,
                    'assignments' => $assignments,
                ];
            }
        }

        return $results;
    }

    /**
     * Find parent products that are marked inactive but still have variations
     * with a positive quantity on hand.  Returns a list of "inconsistency" records.
     *
     * Each element has the shape:
     *   [
     *     'parent_stock_id' => string,
     *     'variation_stock_id' => string,
     *     'qty_on_hand'     => float|int,
     *   ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function validateInactiveParents(): array
    {
        $p = $this->db->getTablePrefix();

        $rows = $this->db->query(
            "SELECT sm_parent.stock_id   AS parent_stock_id,
                    sm_var.stock_id      AS variation_stock_id,
                    COALESCE(SUM(mv.qty_on_hand), 0) AS qty_on_hand
             FROM `{$p}stock_master` AS sm_parent
             JOIN `{$p}product_variation_relationships` pvr
                  ON pvr.parent_stock_id = sm_parent.stock_id
             JOIN `{$p}stock_master` AS sm_var
                  ON sm_var.stock_id = pvr.child_stock_id
             LEFT JOIN `{$p}stock_moves` mv
                  ON mv.stock_id = sm_var.stock_id
             WHERE sm_parent.inactive = 1
             GROUP BY sm_parent.stock_id, sm_var.stock_id
             HAVING qty_on_hand > 0
             ORDER BY sm_parent.stock_id, sm_var.stock_id"
        );

        return $rows ?: [];
    }
}
