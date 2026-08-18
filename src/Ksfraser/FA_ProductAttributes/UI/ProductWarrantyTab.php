<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;

/**
 * Single Responsibility: Renders the Product Warranty admin tab.
 *
 * Radio buttons for warranty type, with duration fields per type.
 */
class ProductWarrantyTab
{
    /** @var ProductWarrantyDao */
    private $dao;

    /** @var string[] */
    private static $warrantyTypes = [
        'none'        => 'None',
        'manufacturer' => 'Manufacturer Warranty',
        'extended'    => 'Extended Warranty',
        'third_party' => 'Third-Party Warranty',
        'lifetime'    => 'Lifetime Warranty',
    ];

    public function __construct(ProductWarrantyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the warranty form.
     * When $stockId is empty the form renders with default values.
     */
    public function render(string $stockId = ''): void
    {
        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        $this->renderWarrantyType($data);
        $this->renderManufacturerDuration($data);
        $this->renderExtendedDuration($data);
        $this->renderThirdPartyDuration($data);
        $this->renderLifetimeNotes($data);
        $this->renderWarrantyNotes($data);

        echo '<p><input type="submit" name="pa_warranty_save" value="' . _('Save') . '"></p>';
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $d */
    private function renderWarrantyType(array $d): void
    {
        $current = (string)($d['warranty_type'] ?? 'none');

        echo '<fieldset><legend>' . _('Warranty Type') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach (self::$warrantyTypes as $val => $label) {
            $checked = ($current === $val) ? ' checked' : '';
            echo '<tr>';
            echo '<td><input type="radio" name="warranty_type" value="' . htmlspecialchars($val) . '"'
                . $checked . ' id="warranty_type_' . htmlspecialchars($val) . '"></td>';
            echo '<td><label for="warranty_type_' . htmlspecialchars($val) . '">'
                . htmlspecialchars(_($label)) . '</label></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderManufacturerDuration(array $d): void
    {
        $duration = $d['manufacturer_duration'] ?? '';
        $unit     = $d['manufacturer_duration_unit'] ?? 'months';

        echo '<fieldset><legend>' . _('Manufacturer Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="manufacturer_duration" min="0" value="'
            . htmlspecialchars((string)$duration) . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('manufacturer_duration_unit', $unit) . '</td>';
        echo '</tr></table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderExtendedDuration(array $d): void
    {
        $duration = $d['extended_duration'] ?? '';
        $unit     = $d['extended_duration_unit'] ?? 'months';

        echo '<fieldset><legend>' . _('Extended Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="extended_duration" min="0" value="'
            . htmlspecialchars((string)$duration) . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('extended_duration_unit', $unit) . '</td>';
        echo '</tr></table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderThirdPartyDuration(array $d): void
    {
        $duration = $d['third_party_duration'] ?? '';
        $unit     = $d['third_party_duration_unit'] ?? 'months';

        echo '<fieldset><legend>' . _('Third-Party Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="third_party_duration" min="0" value="'
            . htmlspecialchars((string)$duration) . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('third_party_duration_unit', $unit) . '</td>';
        echo '</tr></table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderLifetimeNotes(array $d): void
    {
        $notes = htmlspecialchars((string)($d['lifetime_notes'] ?? ''));

        echo '<fieldset><legend>' . _('Lifetime Warranty Notes') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Notes') . '</td>';
        echo '<td><input type="text" name="lifetime_notes" maxlength="255" value="'
            . $notes . '" style="width:100%"></td>';
        echo '</tr></table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderWarrantyNotes(array $d): void
    {
        $notes = htmlspecialchars((string)($d['warranty_notes'] ?? ''));

        echo '<fieldset><legend>' . _('Warranty Terms / General Notes') . '</legend>';
        echo '<textarea name="warranty_notes" rows="4" style="width:100%">'
            . $notes . '</textarea>';
        echo '</fieldset>';
    }

    private function selectField(string $name, string $current): string
    {
        $options = ['days' => 'Days', 'months' => 'Months', 'years' => 'Years'];
        $html    = '<select name="' . $name . '">';
        foreach ($options as $val => $label) {
            $sel   = ($val === $current) ? ' selected' : '';
            $html .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
