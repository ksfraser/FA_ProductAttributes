<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Action: Assign a child product to a parent product (BR1.7).
 *
 * Validates that both the child product and the proposed parent product exist in
 * stock_master, then records the relationship using VariationsDao.
 */
class AssignParentAction
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
     * @param array<string, mixed> $postData  Must contain 'stock_id' and 'assign_parent_stock_id'
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId       = trim((string)($postData['stock_id'] ?? ''));
        $parentStockId = trim((string)($postData['assign_parent_stock_id'] ?? ''));

        if ($stockId === '') {
            return _('Invalid stock ID');
        }

        if ($parentStockId === '') {
            return _('Invalid parent stock ID');
        }

        if ($stockId === $parentStockId) {
            return _('A product cannot be its own parent');
        }

        $p = $this->db->getTablePrefix();

        // Verify child exists
        $child = $this->db->query(
            "SELECT 1 FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );
        if (empty($child)) {
            return sprintf(_('Product %s not found'), $stockId);
        }

        // Verify proposed parent exists
        $parent = $this->db->query(
            "SELECT 1 FROM `{$p}stock_master` WHERE stock_id = :stock_id",
            ['stock_id' => $parentStockId]
        );
        if (empty($parent)) {
            return sprintf(_('Parent product %s not found'), $parentStockId);
        }

        $this->variationsDao->setParentRelationship($stockId, $parentStockId);

        return sprintf(_('Product %s assigned to parent %s'), $stockId, $parentStockId);
    }
}
