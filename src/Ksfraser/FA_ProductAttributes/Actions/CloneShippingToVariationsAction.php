<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;

/**
 * Single Responsibility: Copies a parent product's shipping attributes to a
 * selected subset of its variation products.
 *
 * This enables bulk-cloning shipping/logistics data to variations when, for
 * example, all sizes share the same hazmat classification or packaging type,
 * while allowing individual variations to override weight/dimensions afterwards.
 *
 * Expected POST keys:
 *   stock_id              string     Parent product stock ID
 *   variation_stock_ids   string[]   Stock IDs of the variations to update
 */
class CloneShippingToVariationsAction
{
    /** @var ShippingAttributesDao */
    private $dao;

    public function __construct(ShippingAttributesDao $dao)
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

        $parentShipping = $this->dao->get($parentId);
        if ($parentShipping === null) {
            return 'Parent product has no shipping attributes to clone';
        }

        // Strip the primary key — each variation row has its own stock_id
        $shippingData = $parentShipping;
        unset($shippingData['stock_id']);

        $count = 0;
        foreach ($varIds as $varId) {
            $varId = (string)$varId;
            if ($varId !== '') {
                $this->dao->upsert($varId, $shippingData);
                $count++;
            }
        }

        return sprintf(_('%d variation(s) updated with parent shipping attributes'), $count);
    }
}
