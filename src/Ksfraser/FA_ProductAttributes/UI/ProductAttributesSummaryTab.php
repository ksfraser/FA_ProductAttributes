<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;

/**
 * Single Responsibility: Renders a read-only summary of all product attribute
 * groups for a single product.
 *
 * Intended as the first (overview) tab so administrators can quickly check
 * what has been configured for a product without switching between tabs.
 */
class ProductAttributesSummaryTab
{
    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var ProductIdentifiersDao */
    private $identifiersDao;

    /** @var ProductLifecycleDao */
    private $lifecycleDao;

    /** @var ProductTagsDao */
    private $tagsDao;

    /** @var ProductMediaDao */
    private $mediaDao;

    /** @var ShippingAttributesDao */
    private $shippingDao;

    public function __construct(
        ProductAttributesDao   $coreDao,
        ProductIdentifiersDao  $identifiersDao,
        ProductLifecycleDao    $lifecycleDao,
        ProductTagsDao         $tagsDao,
        ProductMediaDao        $mediaDao,
        ShippingAttributesDao  $shippingDao
    ) {
        $this->coreDao        = $coreDao;
        $this->identifiersDao = $identifiersDao;
        $this->lifecycleDao   = $lifecycleDao;
        $this->tagsDao        = $tagsDao;
        $this->mediaDao       = $mediaDao;
        $this->shippingDao    = $shippingDao;
    }

    /**
     * Render the summary panel for a product.
     */
    public function render(string $stockId = ''): void
    {
        if ($stockId === '') {
            echo '<p>' . _('No product selected.') . '</p>';
            return;
        }

        echo '<h3>' . _('Product Attributes Summary') . ' &mdash; '
            . htmlspecialchars($stockId) . '</h3>';

        $this->renderIdentifiersSummary($stockId);
        $this->renderLifecycleSummary($stockId);
        $this->renderTagsSummary($stockId);
        $this->renderMediaSummary($stockId);
        $this->renderShippingSummary($stockId);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function renderIdentifiersSummary(string $stockId): void
    {
        $data = $this->identifiersDao->get($stockId) ?? [];

        echo '<fieldset><legend>' . _('Identifiers') . '</legend>';

        if (empty($data) || count(array_filter($data)) === 0) {
            echo '<p><em>' . _('Not configured') . '</em></p></fieldset>';
            return;
        }

        $map = [
            'brand'            => _('Brand'),
            'manufacturer'     => _('Manufacturer'),
            'mpn'              => _('MPN'),
            'gtin'             => _('GTIN'),
            'ean'              => _('EAN'),
            'upc'              => _('UPC'),
            'internal_barcode' => _('Internal Barcode'),
            'supplier_part_no' => _('Supplier Part No.'),
            'model_no'         => _('Model No.'),
        ];

        echo '<table class="tablestyle_noborder">';
        foreach ($map as $field => $label) {
            if (!empty($data[$field])) {
                echo '<tr><td><strong>' . $label . '</strong></td>';
                echo '<td>' . htmlspecialchars((string)$data[$field]) . '</td></tr>';
            }
        }
        echo '</table></fieldset>';
    }

    private function renderLifecycleSummary(string $stockId): void
    {
        $data = $this->lifecycleDao->get($stockId) ?? [];

        echo '<fieldset><legend>' . _('Lifecycle') . '</legend>';

        if (empty($data)) {
            echo '<p><em>' . _('Not configured') . '</em></p></fieldset>';
            return;
        }

        $status = htmlspecialchars((string)($data['status'] ?? ''));
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td><strong>' . _('Status') . '</strong></td><td>' . $status . '</td></tr>';

        $flags = [
            'is_special_order'       => _('Special Order'),
            'is_clearance'           => _('Clearance'),
            'is_out_of_stock_notice' => _('Out-of-Stock Notice'),
            'is_new_arrival'         => _('New Arrival'),
            'is_bestseller'          => _('Bestseller'),
            'is_featured'            => _('Featured'),
            'is_seasonal'            => _('Seasonal'),
        ];
        $activeFlags = [];
        foreach ($flags as $field => $label) {
            if (!empty($data[$field])) {
                $activeFlags[] = $label;
            }
        }
        if (!empty($activeFlags)) {
            echo '<tr><td><strong>' . _('Active Flags') . '</strong></td>';
            echo '<td>' . htmlspecialchars(implode(', ', $activeFlags)) . '</td></tr>';
        }
        if (!empty($data['available_from'])) {
            echo '<tr><td><strong>' . _('Available From') . '</strong></td>';
            echo '<td>' . htmlspecialchars((string)$data['available_from']) . '</td></tr>';
        }
        if (!empty($data['discontinue_on'])) {
            echo '<tr><td><strong>' . _('Discontinue On') . '</strong></td>';
            echo '<td>' . htmlspecialchars((string)$data['discontinue_on']) . '</td></tr>';
        }
        echo '</table></fieldset>';
    }

    private function renderTagsSummary(string $stockId): void
    {
        $tags = $this->tagsDao->getProductTags($stockId);

        echo '<fieldset><legend>' . _('Tags') . '</legend>';

        if (empty($tags)) {
            echo '<p><em>' . _('No tags assigned') . '</em></p></fieldset>';
            return;
        }

        $names = [];
        foreach ($tags as $t) {
            $names[] = htmlspecialchars((string)$t['name']);
        }
        echo '<p>' . implode(', ', $names) . '</p>';
        echo '</fieldset>';
    }

    private function renderMediaSummary(string $stockId): void
    {
        $items = $this->mediaDao->getProductMedia($stockId);

        echo '<fieldset><legend>' . _('Media') . '</legend>';

        if (empty($items)) {
            echo '<p><em>' . _('No media added') . '</em></p></fieldset>';
            return;
        }

        echo '<table class="tablestyle_noborder">';
        foreach ($items as $item) {
            $url       = htmlspecialchars((string)($item['url'] ?? ''));
            $type      = strtoupper((string)($item['media_type'] ?? 'image'));
            $isPrimary = !empty($item['is_primary']);
            echo '<tr>';
            echo '<td><strong>' . $type . '</strong>'
                . ($isPrimary ? ' &#9733;' : '') . '</td>';
            echo '<td><a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
                . $url . '</a></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';
    }

    private function renderShippingSummary(string $stockId): void
    {
        $data = $this->shippingDao->get($stockId) ?? [];

        echo '<fieldset><legend>' . _('Shipping') . '</legend>';

        if (empty($data) || count(array_filter($data)) === 0) {
            echo '<p><em>' . _('Not configured') . '</em></p></fieldset>';
            return;
        }

        $map = [
            'weight'      => _('Weight'),
            'weight_unit' => _('Weight Unit'),
            'length'      => _('Length'),
            'width'       => _('Width'),
            'height'      => _('Height'),
            'dim_unit'    => _('Dimension Unit'),
            'hs_code'     => _('HS Code'),
        ];

        echo '<table class="tablestyle_noborder">';
        foreach ($map as $field => $label) {
            if (!empty($data[$field])) {
                echo '<tr><td><strong>' . $label . '</strong></td>';
                echo '<td>' . htmlspecialchars((string)$data[$field]) . '</td></tr>';
            }
        }
        echo '</table></fieldset>';
    }
}
