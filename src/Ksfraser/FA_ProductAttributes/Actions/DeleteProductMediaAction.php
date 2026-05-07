<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;

/**
 * Single Responsibility: Deletes a media item and all its variation links.
 *
 * Expected POST keys:
 *   media_id  int  Required; must be > 0
 */
class DeleteProductMediaAction
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

        $this->dao->deleteMedia($mediaId);

        return _('Media deleted');
    }
}
