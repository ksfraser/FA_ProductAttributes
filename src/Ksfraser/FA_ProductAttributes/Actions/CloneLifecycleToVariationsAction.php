<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Copies a parent product's lifecycle / status flags to
 * a selected subset of its variation products.
 *
 * Expected POST keys:
 *   stock_id              string     Parent product stock ID
 *   variation_stock_ids   string[]   Stock IDs of variations to update
 */
class CloneLifecycleToVariationsAction
{
    /** @var ProductLifecycleDao */
    private $dao;

    /** @var ProductAttributesDao */
    private $conditionDao;

    public function __construct(ProductLifecycleDao $dao, ProductAttributesDao $conditionDao)
    {
        $this->dao = $dao;
        $this->conditionDao = $conditionDao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $parentId = trim((string)($postData['stock_id'] ?? ''));
        if ($parentId === '') {
            return 'Invalid stock ID';
        }

        $rawIds = (array)($postData['variation_stock_ids'] ?? []);
        $varIds = array_values(array_filter(array_map('trim', $rawIds)));
        if (empty($varIds)) {
            return 'No variations selected';
        }

        $parentData = $this->dao->get($parentId);
        if ($parentData === null) {
            return 'Parent product has no lifecycle data to clone';
        }

        $cloneData = $parentData;
        unset($cloneData['stock_id']);

        // Carried alongside the lifecycle row: the parent's product condition
        // (xref table, one row per stock). Only copied when the parent has one
        // set; variations without a parent condition keep their own.
        $parentCondition = $this->conditionDao->getProductCondition($parentId);

        $count = 0;
        foreach ($varIds as $varId) {
            $varId = (string)$varId;
            if ($varId !== '') {
                $this->dao->upsert($varId, $cloneData);
                if ($parentCondition !== null) {
                    $this->conditionDao->setProductCondition($varId, (int)$parentCondition);
                }
                $count++;
            }
        }

        return sprintf(_('%d variation(s) updated with parent lifecycle data'), $count);
    }
}
