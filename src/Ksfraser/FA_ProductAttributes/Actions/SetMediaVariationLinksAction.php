<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;

/**
 * Single Responsibility: Replaces the variation-scope links for one media item.
 *
 * An empty variations list means the media is not scoped to any specific
 * variation (i.e. it appears for all variations / the parent product).
 *
 * Expected POST keys:
 *   media_id              int       Required; must be > 0
 *   variation_stock_ids   string[]  Optional; array of variation stock IDs
 */
class SetMediaVariationLinksAction
{
    /** @var ProductMediaDao */
    private $dao;

    public function __construct(ProductMediaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $mediaId = (int)($postData['media_id'] ?? 0);
        if ($mediaId <= 0) {
            return 'Invalid media ID';
        }

        $rawIds  = (array)($postData['variation_stock_ids'] ?? []);
        $varIds  = array_values(array_filter(array_map('trim', array_map('strval', $rawIds))));

        $this->dao->setVariationLinks($mediaId, $varIds);

        return _('Media variation links updated');
    }
}
