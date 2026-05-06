<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Provides summary, filtering, and pagination of product
 * variation data for the dashboard view.
 *
 * Queries FA's stockmaster table (via the parent_stock_id column added by
 * VariationsDao) and the product_attribute_assignments table to produce
 * variation-counts-per-product with multiple filter options.
 *
 * Absorbed from the standalone FA_ProductVariations repo's ProductVariation
 * model (filter/count/pagination methods) and DashboardController.
 */
class VariationsDashboardService
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Return a paginated list of parent products with their variation counts.
     *
     * A "parent product" is any stock item that has at least one child entry
     * whose parent_stock_id references it in product_attribute_assignments.
     *
     * @param int $page    1-based page number (default: 1)
     * @param int $perPage Items per page (default: 20)
     * @return array{
     *   rows: array<int, array{stock_id: string, description: string, variation_count: int}>,
     *   total: int,
     *   page: int,
     *   per_page: int,
     *   total_pages: int
     * }
     */
    public function getSummary(int $page = 1, int $perPage = 20): array
    {
        $p      = $this->db->getTablePrefix();
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->query(
            "SELECT sm.stock_id,
                    sm.description,
                    COUNT(child.stock_id) AS variation_count
             FROM `{$p}stockmaster` sm
             LEFT JOIN `{$p}product_attribute_assignments` child
                    ON child.parent_stock_id = sm.stock_id
             WHERE (sm.parent_stock_id IS NULL OR sm.parent_stock_id = '')
               AND sm.inactive = 0
             GROUP BY sm.stock_id, sm.description
             HAVING variation_count > 0
             ORDER BY sm.description
             LIMIT :limit OFFSET :offset",
            ['limit' => $perPage, 'offset' => $offset]
        );

        $total      = $this->getTotalProductCount();
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

        return [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Count parent products that have at least one generated variation.
     */
    public function getTotalProductCount(): int
    {
        $p      = $this->db->getTablePrefix();
        $result = $this->db->query(
            "SELECT COUNT(DISTINCT sm.stock_id) AS total
             FROM `{$p}stockmaster` sm
             JOIN `{$p}product_attribute_assignments` child
                  ON child.parent_stock_id = sm.stock_id
             WHERE (sm.parent_stock_id IS NULL OR sm.parent_stock_id = '')
               AND sm.inactive = 0"
        );
        return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
    }

    /**
     * Filter products that are assigned to a specific attribute category.
     *
     * @param int $categoryId
     * @return array<int, array{stock_id: string, description: string, variation_count: int}>
     */
    public function filterByCategory(int $categoryId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT sm.stock_id,
                    sm.description,
                    COUNT(child.stock_id) AS variation_count
             FROM `{$p}stockmaster` sm
             JOIN `{$p}product_attribute_assignments` a
                  ON a.stock_id = sm.stock_id AND a.category_id = :category_id
             LEFT JOIN `{$p}product_attribute_assignments` child
                    ON child.parent_stock_id = sm.stock_id
             WHERE (sm.parent_stock_id IS NULL OR sm.parent_stock_id = '')
               AND sm.inactive = 0
             GROUP BY sm.stock_id, sm.description
             ORDER BY sm.description",
            ['category_id' => $categoryId]
        );
    }

    /**
     * Filter products by number of generated variations.
     *
     * @param int $minCount Minimum variation count (inclusive)
     * @param int $maxCount Maximum variation count (inclusive, 0 = no upper limit)
     * @return array<int, array{stock_id: string, description: string, variation_count: int}>
     */
    public function filterByVariationCount(int $minCount = 0, int $maxCount = 0): array
    {
        $p = $this->db->getTablePrefix();

        $having = 'variation_count >= :min';
        $params = ['min' => $minCount];

        if ($maxCount > 0) {
            $having .= ' AND variation_count <= :max';
            $params['max'] = $maxCount;
        }

        return $this->db->query(
            "SELECT sm.stock_id,
                    sm.description,
                    COUNT(child.stock_id) AS variation_count
             FROM `{$p}stockmaster` sm
             LEFT JOIN `{$p}product_attribute_assignments` child
                    ON child.parent_stock_id = sm.stock_id
             WHERE (sm.parent_stock_id IS NULL OR sm.parent_stock_id = '')
               AND sm.inactive = 0
             GROUP BY sm.stock_id, sm.description
             HAVING {$having}
             ORDER BY variation_count DESC",
            $params
        );
    }

    /**
     * Filter products by their active/inactive status.
     *
     * @param bool $inactive  true = return inactive products, false = active (default)
     * @return array<int, array{stock_id: string, description: string, variation_count: int}>
     */
    public function filterByStockStatus(bool $inactive = false): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            "SELECT sm.stock_id,
                    sm.description,
                    COUNT(child.stock_id) AS variation_count
             FROM `{$p}stockmaster` sm
             LEFT JOIN `{$p}product_attribute_assignments` child
                    ON child.parent_stock_id = sm.stock_id
             WHERE (sm.parent_stock_id IS NULL OR sm.parent_stock_id = '')
               AND sm.inactive = :inactive
             GROUP BY sm.stock_id, sm.description
             ORDER BY sm.description",
            ['inactive' => $inactive ? 1 : 0]
        );
    }
}
