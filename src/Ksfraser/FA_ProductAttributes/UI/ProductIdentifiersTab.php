<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;

/**
 * Single Responsibility: Renders the Product Identifiers admin tab.
 *
 * Covers industry-standard product codes used by retailers, suppliers,
 * and logistics systems:
 *   - Brand / manufacturer (DDL from lookup table)
 *   - MPN, GTIN, EAN, UPC, ISBN, ASIN
 *   - Internal barcode, supplier part number, model number
 */
class ProductIdentifiersTab
{
    /** @var ProductIdentifiersDao */
    private $dao;

    /** @var IdentifierLookupsDao|null */
    private $lookupsDao;

    public function __construct(ProductIdentifiersDao $dao, IdentifierLookupsDao $lookupsDao = null)
    {
        $this->dao        = $dao;
        $this->lookupsDao = $lookupsDao;
    }

    /**
     * Render the identifiers form.
     * When $stockId is empty the form renders with blank fields.
     */
    public function render(string $stockId = ''): void
    {
        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        echo '<input type="hidden" name="action"   value="save_product_identifiers">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->renderBrandSection($data);
        $this->renderBarcodeSection($data);
        $this->renderSourcingSection($data);

        echo '<p><input type="submit" name="addupdate" value="' . _('Save') . '"></p>';
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $d */
    private function renderBrandSection(array $d): void
    {
        echo '<fieldset><legend>' . _('Brand &amp; Manufacturer') . '</legend>';
        echo '<table class="tablestyle_noborder">';

        if ($this->lookupsDao !== null) {
            $this->ddlRow(_('Brand'),        'brand',        $d, 'brand');
            $this->ddlRow(_('Manufacturer'), 'manufacturer', $d, 'manufacturer');
        } else {
            $this->row(_('Brand'),        'brand',        $d);
            $this->row(_('Manufacturer'), 'manufacturer', $d);
        }

        $this->row(_('Model No.'), 'model_no', $d);
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

    /**
     * Render a label+DDL row from the lookup table.
     *
     * @param array<string, mixed> $d
     */
    private function ddlRow(string $label, string $field, array $d, string $lookupType): void
    {
        $current = (string)($d[$field] ?? '');
        $options = $this->lookupsDao->listByType($lookupType);

        echo '<tr>';
        echo '<td><label for="ident_' . $field . '">' . $label . '</label></td>';
        echo '<td>';
        echo '<select id="ident_' . $field . '" name="' . $field . '" class="form-control">';
        echo '<option value="">-- ' . _('Select') . ' --</option>';
        foreach ($options as $opt) {
            $optName = htmlspecialchars((string)($opt['name'] ?? ''));
            $sel     = ($optName === $current) ? ' selected' : '';
            echo '<option value="' . $optName . '"' . $sel . '>' . $optName . '</option>';
        }
        // If the current value is not in the lookup list, show it as a fallback option
        if ($current !== '' && !empty($options)) {
            $found = false;
            foreach ($options as $opt) {
                if ((string)($opt['name'] ?? '') === $current) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo '<option value="' . htmlspecialchars($current) . '" selected>'
                    . htmlspecialchars($current) . '</option>';
            }
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
    }
}
