<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;

/**
 * Single Responsibility: Renders the Product Identifiers admin tab.
 *
 * Covers industry-standard product codes used by retailers, suppliers,
 * and logistics systems:
 *   - Brand / manufacturer
 *   - MPN, GTIN, EAN, UPC, ISBN, ASIN
 *   - Internal barcode, supplier part number, model number
 */
class ProductIdentifiersTab
{
    /** @var ProductIdentifiersDao */
    private $dao;

    public function __construct(ProductIdentifiersDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the identifiers form.
     * When $stockId is empty the form renders with blank fields.
     */
    public function render(string $stockId = ''): void
    {
        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_identifiers">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->renderBrandSection($data);
        $this->renderBarcodeSection($data);
        $this->renderSourcingSection($data);

        echo '<p><input type="submit" value="' . _('Save Identifiers') . '"></p>';
        echo '</form>';
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $d */
    private function renderBrandSection(array $d): void
    {
        echo '<fieldset><legend>' . _('Brand &amp; Manufacturer') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        $this->row(_('Brand'),        'brand',        $d);
        $this->row(_('Manufacturer'), 'manufacturer', $d);
        $this->row(_('Model No.'),    'model_no',     $d);
        echo '</table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderBarcodeSection(array $d): void
    {
        echo '<fieldset><legend>' . _('Barcodes &amp; Global Trade IDs') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        $this->row(_('MPN (Manufacturer Part Number)'), 'mpn',  $d);
        $this->row(_('GTIN-14'),                        'gtin', $d);
        $this->row(_('EAN-13'),                         'ean',  $d);
        $this->row(_('UPC-A'),                          'upc',  $d);
        $this->row(_('ISBN-13'),                        'isbn', $d);
        $this->row(_('ASIN (Amazon)'),                  'asin', $d);
        $this->row(_('Internal Barcode'),               'internal_barcode', $d);
        echo '</table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderSourcingSection(array $d): void
    {
        echo '<fieldset><legend>' . _('Sourcing References') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        $this->row(_('Supplier Part No.'), 'supplier_part_no', $d);
        echo '</table></fieldset>';
    }

    /**
     * Render a single label+input row.
     *
     * @param array<string, mixed> $d
     */
    private function row(string $label, string $field, array $d): void
    {
        $val = htmlspecialchars((string)($d[$field] ?? ''));
        echo '<tr>';
        echo '<td><label for="ident_' . $field . '">' . $label . '</label></td>';
        echo '<td><input type="text" id="ident_' . $field . '" name="' . $field . '" '
            . 'value="' . $val . '" maxlength="128" class="form-control"></td>';
        echo '</tr>';
    }
}
