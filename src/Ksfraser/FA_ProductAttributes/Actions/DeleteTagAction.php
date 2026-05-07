<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;

/**
 * Single Responsibility: Deletes a global product tag and all its assignments.
 *
 * Expected POST keys:
 *   tag_id  int  Required; must be > 0
 */
class DeleteTagAction
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
        $tagId = (int)($postData['tag_id'] ?? 0);
        if ($tagId <= 0) {
            return 'Invalid tag ID';
        }

        $this->dao->deleteTag($tagId);

        return _('Tag deleted');
    }
}
