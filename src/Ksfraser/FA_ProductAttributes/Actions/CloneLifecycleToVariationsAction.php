<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;

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

    public function __construct(ProductLifecycleDao $dao)
    {
        $this->dao = $dao;
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

        $count = 0;
        foreach ($varIds as $varId) {
            $varId = (string)$varId;
            if ($varId !== '') {
                $this->dao->upsert($varId, $cloneData);
                $count++;
            }
        }

        return sprintf(_('%d variation(s) updated with parent lifecycle data'), $count);
    }
}
