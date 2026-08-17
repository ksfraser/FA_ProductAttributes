<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;

class MediaTab extends AbstractTab
{
    /** @var ProductMediaDao */
    private $dao;

    public function __construct(ProductMediaDao $dao)
    {
        $this->dao = $dao;
    }

    public function getName(): string
    {
        return 'product_media';
    }

    public function getTabKey(): string
    {
        return 'product_media';
    }

    public function getTabLabel(): string
    {
        return _('Media');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $mediaItems = ($stockId !== '') ? $this->dao->getProductMedia($stockId) : [];
        $imageDir = company_path() . '/images';

        echo '<fieldset><legend>' . _('Primary Image') . '</legend>';
        $primaryFile = $this->findPrimaryImage($stockId);
        if ($primaryFile) {
            $url = $this->getImageUrl($primaryFile);
            echo '<p><img src="' . $url . '" style="max-width:200px;max-height:200px;border:1px solid #ccc"></p>';
            echo '<p><small>' . htmlspecialchars(basename($primaryFile)) . '</small></p>';
        } else {
            echo '<p>' . _('No primary image set.') . '</p>';
        }
        echo '</fieldset>';

        echo '<fieldset><legend>' . _('Additional Images & Media') . '</legend>';
        if (empty($mediaItems)) {
            echo '<p>' . _('No additional images or media uploaded yet.') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Preview') . '</th><th>' . _('Type') . '</th>'
                . '<th>' . _('Alt Text') . '</th><th>' . _('Order') . '</th><th></th></tr>';
            foreach ($mediaItems as $item) {
                $id   = (int)($item['id'] ?? 0);
                $url  = (string)($item['url'] ?? '');
                $type = htmlspecialchars((string)($item['media_type'] ?? 'image'));
                $alt  = htmlspecialchars((string)($item['alt_text'] ?? ''));
                $sort = (int)($item['sort_order'] ?? 0);
                $isPrimary = (int)($item['is_primary'] ?? 0);

                echo '<tr>';
                if ($type === 'image') {
                    $imgUrl = $this->resolveMediaUrl($url, $imageDir);
                    echo '<td><img src="' . $imgUrl . '" style="max-width:80px;max-height:80px;border:1px solid #ccc"></td>';
                } else {
                    echo '<td><small>' . htmlspecialchars($url) . '</small></td>';
                }
                $typeLabel = $isPrimary ? $type . ' ★' : $type;
                echo '<td>' . $typeLabel . '</td>';
                echo '<td>' . $alt . '</td>';
                echo '<td>' . $sort . '</td>';
                echo '<td><button type="submit" name="pa_media_delete" value="' . $id . '" '
                    . 'style="color:red" formnovalidate '
                    . 'onclick="return confirm(\'' . _('Delete this media item and its file?') . '\')">'
                    . _('Delete') . '</button></td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</fieldset>';

        echo '<fieldset><legend>' . _('Upload Image') . '</legend>';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('File') . '</td>';
        echo '<td><input type="file" name="media_file" accept="image/jpeg,image/png,image/gif"></td></tr>';
        echo '<tr><td>' . _('Alt Text') . '</td>';
        echo '<td><input type="text" name="alt_text" maxlength="255" style="width:100%" '
            . 'placeholder="Describe the image for accessibility"></td></tr>';
        echo '<tr><td>' . _('Sort Order') . '</td>';
        echo '<td><input type="number" name="sort_order" min="0" value="0"></td></tr>';
        echo '</table>';
        echo '<p><small>' . _('Accepted formats: JPEG, PNG, GIF.') . '</small></p>';
        echo '<p><input type="submit" name="pa_media_upload" value="' . _('Upload Image') . '"></p>';
        echo '</fieldset>';
    }

    public function handleDelete(string $stockId): void
    {
        $this->deleteAllMediaFiles($stockId);
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (isset($_POST['pa_media_upload']) && isset($_FILES['media_file'])) {
            $this->handleImageUpload($stockId, $_FILES['media_file']);
            return;
        }

        if (isset($_POST['pa_media_delete'])) {
            $mediaId = (int)$_POST['pa_media_delete'];
            if ($mediaId > 0) {
                $mediaItem = $this->dao->getMediaItem($mediaId);
                if ($mediaItem && $mediaItem['stock_id'] === $stockId) {
                    $this->deleteMediaFile((string)($mediaItem['url'] ?? ''));
                    $this->dao->deleteMedia($mediaId);
                    if (function_exists('display_notification')) {
                        display_notification(_('Media item deleted.'));
                    }
                }
            }
            return;
        }
    }

    private function handleImageUpload(string $stockId, array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
            display_error(_('File upload failed or empty.'));
            return;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            display_error(_('Only JPEG, PNG, and GIF images are accepted.'));
            return;
        }

        global $SysPrefs;
        $maxSize = isset($SysPrefs->max_image_size) ? (int)$SysPrefs->max_image_size : 500000;
        if ($file['size'] > $maxSize) {
            display_error(_('File exceeds maximum image size.'));
            return;
        }

        $imageDir = company_path() . '/images';
        if (!is_dir($imageDir)) {
            @mkdir($imageDir, 0755, true);
        }

        $ext = $allowedTypes[$mimeType];
        $cleanStockId = item_img_name($stockId);
        $filename = $cleanStockId . '-' . $this->nextImageIndex($imageDir, $cleanStockId) . '.' . $ext;
        $dest = $imageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            display_error(_('Failed to save uploaded file.'));
            return;
        }

        $altText = trim((string)($_POST['alt_text'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $this->dao->addMedia($stockId, 'images/' . $filename, $altText, $sortOrder, 'image', false, '');
        display_notification(_('Image uploaded successfully.'));
    }

    private function nextImageIndex(string $imageDir, string $cleanStockId): int
    {
        $index = 1;
        while (count(glob($imageDir . '/' . $cleanStockId . '-' . $index . '.*')) > 0) {
            $index++;
        }
        return $index;
    }

    private function deleteMediaFile(string $url): void
    {
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return;
        }
        $path = company_path() . '/images' . '/' . basename($url);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function deleteAllMediaFiles(string $stockId): void
    {
        $mediaItems = $this->dao->getProductMedia($stockId);
        foreach ($mediaItems as $item) {
            $this->deleteMediaFile((string)($item['url'] ?? ''));
            $this->dao->deleteMedia((int)($item['id'] ?? 0));
        }
    }

    private function findPrimaryImage(string $stockId): ?string
    {
        $imageDir = company_path() . '/images';
        $cleanName = item_img_name($stockId);
        foreach (['jpg', 'png', 'gif'] as $ext) {
            $path = $imageDir . '/' . $cleanName . '.' . $ext;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function getImageUrl(string $absolutePath): string
    {
        global $path_to_root;
        $companyPath = company_path();
        $relative = str_replace($companyPath, '', $absolutePath);
        return $path_to_root . '/company' . $relative;
    }

    private function resolveMediaUrl(string $url, string $imageDir): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $path = $imageDir . '/' . basename($url);
        if (is_file($path)) {
            return $this->getImageUrl($path);
        }
        return $url;
    }
}
