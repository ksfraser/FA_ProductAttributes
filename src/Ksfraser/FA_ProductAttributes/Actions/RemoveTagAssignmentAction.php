<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;

/**
 * Single Responsibility: Removes a tag assignment from a product.
 *
 * Expected POST keys:
 *   stock_id  string  Required
 *   tag_id    int     Required; must be > 0
 */
class RemoveTagAssignmentAction
{
    /** @var ProductTagsDao */
    private $dao;

    public function __construct(ProductTagsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));
        if ($stockId === '') {
            return 'Invalid stock ID';
        }

        $tagId = (int)($postData['tag_id'] ?? 0);
        if ($tagId <= 0) {
            return 'Invalid tag ID';
        }

        $this->dao->removeAssignment($stockId, $tagId);

        return _('Tag removed');
    }
}
