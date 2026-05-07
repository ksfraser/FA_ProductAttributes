<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Single Responsibility: Renders the Product Media (photos/videos/documents) tab.
 *
 * Media items are referenced by URL — file upload is not handled here
 * (FA has no built-in file storage layer; URLs should point to externally
 * hosted or CDN-backed assets).
 *
 * For each existing media item the panel shows:
 *   - URL, alt text, type badge, primary indicator
 *   - Variation-scope picker: which variations this media applies to
 *   - Delete form
 *
 * At the bottom an add-new-item form is provided.
 */
class ProductMediaTab
{
    /** @var ProductMediaDao */
    private $mediaDao;

    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(ProductMediaDao $mediaDao, VariationsDao $variationsDao)
    {
        $this->mediaDao      = $mediaDao;
        $this->variationsDao = $variationsDao;
    }

    /**
     * Render the media tab for a specific product.
     */
    public function render(string $stockId = ''): void
    {
        if ($stockId === '') {
            echo '<p>' . _('No product selected.') . '</p>';
            return;
        }

        $items      = $this->mediaDao->getProductMedia($stockId);
        $variations = $this->variationsDao->getProductVariations($stockId);

        $this->renderGallery($stockId, $items, $variations);
        $this->renderAddForm($stockId);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $variations
     */
    private function renderGallery(string $stockId, array $items, array $variations): void
    {
        echo '<fieldset><legend>' . _('Media Gallery') . '</legend>';

        if (empty($items)) {
            echo '<p>' . _('No media added yet.') . '</p>';
        } else {
            foreach ($items as $item) {
                $this->renderMediaItem($item, $variations);
            }
        }

        echo '</fieldset>';
    }

    /**
     * @param array<string, mixed>             $item
     * @param array<int, array<string, mixed>> $variations
     */
    private function renderMediaItem(array $item, array $variations): void
    {
        $mediaId   = (int)($item['id'] ?? 0);
        $url       = htmlspecialchars((string)($item['url'] ?? ''));
        $altText   = htmlspecialchars((string)($item['alt_text'] ?? ''));
        $mediaType = htmlspecialchars((string)($item['media_type'] ?? 'image'));
        $isPrimary = !empty($item['is_primary']);

        $linkedVarIds = [];
        foreach ($this->mediaDao->getVariationLinks($mediaId) as $vid) {
            $linkedVarIds[$vid] = true;
        }

        echo '<div class="media-item" style="border:1px solid #ccc;padding:8px;margin-bottom:12px;">';

        // Type badge + primary flag
        echo '<strong>' . strtoupper($mediaType) . '</strong>';
        if ($isPrimary) {
            echo ' &nbsp;<span style="color:green;">&#9733; ' . _('Primary') . '</span>';
        }

        // URL display (linked for images)
        echo '<br>';
        if ($mediaType === 'image') {
            echo '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
                . '<img src="' . $url . '" alt="' . $altText . '" '
                . 'style="max-width:120px;max-height:90px;vertical-align:middle;"> '
                . $url . '</a>';
        } else {
            echo '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
        }

        if ($altText !== '') {
            echo '<br><em>' . $altText . '</em>';
        }

        // Variation scope form
        if (!empty($variations)) {
            echo '<br><strong>' . _('Applied to variations:') . '</strong> ';
            echo '<form method="post" action="" style="display:inline">';
            echo '<input type="hidden" name="action"   value="set_media_variation_links">';
            echo '<input type="hidden" name="media_id" value="' . $mediaId . '">';
            $allChecked = empty($linkedVarIds);
            foreach ($variations as $var) {
                $varId   = htmlspecialchars((string)($var['stock_id'] ?? ''));
                $varDesc = htmlspecialchars((string)($var['description'] ?? $varId));
                $checked = isset($linkedVarIds[(string)($var['stock_id'] ?? '')]) ? ' checked' : '';
                echo '<label style="margin-right:8px;">';
                echo '<input type="checkbox" name="variation_stock_ids[]" value="' . $varId . '"' . $checked . '>';
                echo ' ' . $varDesc . '</label>';
            }
            echo ' <input type="submit" value="' . _('Update') . '">';
            echo '</form>';

            if ($allChecked) {
                echo ' <em>(' . _('all variations') . ')</em>';
            }
        }

        // Delete form
        echo '<br>';
        echo '<form method="post" action="" style="display:inline">';
        echo '<input type="hidden" name="action"   value="delete_product_media">';
        echo '<input type="hidden" name="media_id" value="' . $mediaId . '">';
        echo '<input type="submit" value="' . _('Delete') . '" '
            . 'onclick="return confirm(\'' . _('Delete this media item?') . '\')" '
            . 'style="color:red">';
        echo '</form>';

        echo '</div>';
    }

    /**
     * Render the add-new-media form.
     */
    private function renderAddForm(string $stockId): void
    {
        echo '<fieldset><legend>' . _('Add Media') . '</legend>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="add_product_media">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<table class="tablestyle_noborder">';

        echo '<tr>';
        echo '<td><label for="media_url">' . _('URL') . ' <span style="color:red">*</span></label></td>';
        echo '<td><input type="url" id="media_url" name="url" required maxlength="2048" '
            . 'class="form-control" style="min-width:400px" '
            . 'placeholder="https://..."></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td><label for="media_alt_text">' . _('Alt Text') . '</label></td>';
        echo '<td><input type="text" id="media_alt_text" name="alt_text" maxlength="255" '
            . 'class="form-control" placeholder="' . _('Descriptive text for accessibility') . '"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td><label for="media_type">' . _('Type') . '</label></td>';
        echo '<td><select id="media_type" name="media_type" class="form-control">';
        foreach (['image' => _('Image'), 'video' => _('Video'), 'document' => _('Document')] as $v => $l) {
            echo '<option value="' . $v . '">' . $l . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td><label for="media_sort_order">' . _('Sort Order') . '</label></td>';
        echo '<td><input type="number" id="media_sort_order" name="sort_order" value="0" '
            . 'min="0" class="form-control" style="width:80px"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td><label for="media_is_primary">' . _('Primary Image') . '</label></td>';
        echo '<td><input type="checkbox" id="media_is_primary" name="is_primary" value="1"></td>';
        echo '</tr>';

        echo '</table>';
        echo '<p><input type="submit" value="' . _('Add Media') . '"></p>';
        echo '</form>';
        echo '</fieldset>';
    }
}
