<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Action: Deactivate a parent product and all of its zero-stock variations (BR1.4 / NFR5.1).
 *
 * Behaviour:
 *  - Deactivates the parent product (sets inactive = 1 in stock_master).
 *  - For each variation:
 *    - If stock qty == 0 → deactivate it too.
 *    - If stock qty > 0 → leave active, include in the warning list.
 *  - Returns a message indicating how many were deactivated and listing any
 *    variations that were skipped due to remaining stock.
 */
class MakeInactiveAction
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
     * @param array<string, mixed> $postData  Must contain 'stock_id'
     * @return string Result / warning message
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            return _('Invalid stock ID');
        }

        $variations   = $this->variationsDao->getProductVariations($stockId);
        $p            = $this->db->getTablePrefix();
        $deactivated  = 0;
        $withStock    = [];

        // Deactivate the parent
        $this->db->execute(
            "UPDATE `{$p}stock_master` SET inactive = 1 WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );

        // Process each variation
        foreach ($variations as $variation) {
            $varId = $variation['stock_id'];

            // Fetch current stock quantity
            $qtyResult = $this->db->query(
                "SELECT COALESCE(SUM(qty_on_hand), 0) as qty FROM `{$p}stock_moves` WHERE stock_id = :stock_id",
                ['stock_id' => $varId]
            );
            $qty = (float)($qtyResult[0]['qty'] ?? 0);

            if ($qty <= 0) {
                $this->db->execute(
                    "UPDATE `{$p}stock_master` SET inactive = 1 WHERE stock_id = :stock_id",
                    ['stock_id' => $varId]
                );
                $deactivated++;
            } else {
                $withStock[] = $varId;
            }
        }

        $msg = sprintf(
            _('Parent %s deactivated. %d zero-stock variations deactivated.'),
            $stockId,
            $deactivated
        );

        if (!empty($withStock)) {
            $msg .= ' ' . sprintf(
                _('The following variations were NOT deactivated due to remaining stock: %s'),
                implode(', ', $withStock)
            );
        }

        return $msg;
    }
}
