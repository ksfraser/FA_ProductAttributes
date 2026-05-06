<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Variations\Service\VariationService;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Action: Re-activate an inactive parent and all of its existing variations (BR1.5).
 *
 * Behaviour:
 *  - Re-activates the parent product (sets inactive = 0).
 *  - Re-activates every known child variation.
 *  - Returns a count of reactivated items.
 */
class ReactivateVariationsAction
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
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));

        if ($stockId === '') {
            return _('Invalid stock ID');
        }

        $p          = $this->db->getTablePrefix();
        $reactivated = 0;

        // Re-activate the parent
        $this->db->execute(
            "UPDATE `{$p}stock_master` SET inactive = 0 WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );

        // Re-activate all known variations
        $variations = $this->variationsDao->getProductVariations($stockId);

        foreach ($variations as $variation) {
            $this->db->execute(
                "UPDATE `{$p}stock_master` SET inactive = 0 WHERE stock_id = :stock_id",
                ['stock_id' => $variation['stock_id']]
            );
            $reactivated++;
        }

        return sprintf(
            _('Parent %s reactivated. %d variation(s) reactivated.'),
            $stockId,
            $reactivated
        );
    }
}
