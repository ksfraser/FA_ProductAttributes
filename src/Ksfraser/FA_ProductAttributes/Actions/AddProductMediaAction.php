<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;

/**
 * Single Responsibility: Adds a new media item (photo / video / document) to a product.
 *
 * Media is stored by URL — file upload is not handled here (FA has no
 * built-in file storage layer; the URL should point to an externally
 * hosted or CDN-backed asset).
 *
 * Expected POST keys:
 *   stock_id    string  Required
 *   url         string  Required; fully-qualified URL to the asset
 *   alt_text    string  Optional
 *   sort_order  int     Optional; defaults to 0
 *   media_type  string  image|video|document; defaults to 'image'
 *   is_primary  1|0     Optional; defaults to 0
 */
class AddProductMediaAction
{
    /** @var ProductMediaDao */
    private $dao;

    /** @var string[] */
    private static $validTypes = ['image', 'video', 'document'];

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
        $stockId = trim((string)($postData['stock_id'] ?? ''));
        if ($stockId === '') {
            return 'Invalid stock ID';
        }

        $url = trim((string)($postData['url'] ?? ''));
        if ($url === '') {
            return 'Media URL is required';
        }

        $altText   = trim((string)($postData['alt_text'] ?? ''));
        $sortOrder = (int)($postData['sort_order'] ?? 0);

        $rawType   = (string)($postData['media_type'] ?? 'image');
        $mediaType = in_array($rawType, self::$validTypes, true) ? $rawType : 'image';

        $isPrimary = (bool)($postData['is_primary'] ?? false);

        $downloadUrl = trim((string)($postData['download_url'] ?? ''));
        $downloadUrl = $downloadUrl !== '' ? $downloadUrl : null;

        $this->dao->addMedia($stockId, $url, $altText, $sortOrder, $mediaType, $isPrimary, $downloadUrl);

        return _('Media added');
    }
}
