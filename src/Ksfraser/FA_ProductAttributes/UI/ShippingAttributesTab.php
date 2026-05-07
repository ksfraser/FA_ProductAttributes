<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;

/**
 * Single Responsibility: Renders the Shipping Attributes admin tab.
 *
 * Covers all fields shipping carriers / customs authorities may require:
 *   - Package dimensions (L × W × H) + unit
 *   - Weight / mass + unit
 *   - Hazardous-goods data (IATA / DOT / TDG / IMDG)
 *   - Handling flags (fragile, stackable, oversize, perishable)
 *   - Temperature requirements
 *   - Customs / international trade (HS code, declared value, etc.)
 */
class ShippingAttributesTab
{
    /** @var ShippingAttributesDao */
    private $dao;

    public function __construct(ShippingAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the complete shipping-attributes form.
     *
     * When $stockId is empty the form renders with blanks (add-new context).
     */
    public function render(string $stockId = ''): void
    {
        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_shipping_attributes">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->renderDimensions($data);
        $this->renderWeight($data);
        $this->renderHazmat($data);
        $this->renderHandling($data);
        $this->renderTemperature($data);
        $this->renderCustoms($data);

        echo '<p><input type="submit" value="' . _('Save Shipping Attributes') . '"></p>';
        echo '</form>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Section renderers
    // ──────────────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $d */
    private function renderDimensions(array $d): void
    {
        echo '<fieldset><legend>' . _('Package Dimensions') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td>' . _('Length') . '</td>';
        echo '<td><input type="number" step="0.001" min="0" name="length" value="'
            . $this->vNum($d, 'length') . '"></td>';
        echo '<td>' . _('Width') . '</td>';
        echo '<td><input type="number" step="0.001" min="0" name="width" value="'
            . $this->vNum($d, 'width') . '"></td>';
        echo '<td>' . _('Height') . '</td>';
        echo '<td><input type="number" step="0.001" min="0" name="height" value="'
            . $this->vNum($d, 'height') . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('dim_unit', ['cm' => 'cm', 'in' => 'in'], $d, 'cm') . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderWeight(array $d): void
    {
        $units = ['kg' => 'kg', 'lb' => 'lb', 'g' => 'g', 'oz' => 'oz'];
        echo '<fieldset><legend>' . _('Weight / Mass') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td>' . _('Weight') . '</td>';
        echo '<td><input type="number" step="0.001" min="0" name="weight" value="'
            . $this->vNum($d, 'weight') . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('weight_unit', $units, $d, 'kg') . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderHazmat(array $d): void
    {
        echo '<fieldset><legend>' . _('Hazardous / Dangerous Goods') . '</legend>';
        echo '<p><label><input type="checkbox" name="is_hazardous" value="1"'
            . $this->checked($d, 'is_hazardous') . '> '
            . _('This product is classified as hazardous / dangerous goods') . '</label></p>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td>' . _('Hazmat Class (1–9)') . '</td>';
        echo '<td><input type="text" name="hazmat_class" maxlength="8" value="'
            . $this->v($d, 'hazmat_class') . '" placeholder="e.g. 3"></td>';
        echo '<td>' . _('UN Number') . '</td>';
        echo '<td><input type="text" name="un_number" maxlength="8" value="'
            . $this->v($d, 'un_number') . '" placeholder="e.g. 1234"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Proper Shipping Name') . '</td>';
        echo '<td colspan="3"><input type="text" name="proper_shipping_name" maxlength="255" '
            . 'style="width:100%" value="' . $this->v($d, 'proper_shipping_name') . '"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Packing Group') . '</td>';
        echo '<td>' . $this->selectField(
            'packing_group',
            ['' => '—', 'I' => 'I (high danger)', 'II' => 'II (medium danger)', 'III' => 'III (low danger)'],
            $d,
            ''
        ) . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderHandling(array $d): void
    {
        $flags = [
            'is_fragile'    => _('Fragile — handle with care'),
            'is_stackable'  => _('Stackable'),
            'is_oversize'   => _('Oversize — non-standard carrier fees may apply'),
            'is_perishable' => _('Perishable'),
        ];
        $defaults = ['is_stackable' => true]; // stackable defaults on

        echo '<fieldset><legend>' . _('Handling Requirements') . '</legend>';
        foreach ($flags as $field => $label) {
            $default = !empty($defaults[$field]);
            echo '<p><label><input type="checkbox" name="' . $field . '" value="1"'
                . $this->checked($d, $field, $default) . '> ' . $label . '</label></p>';
        }
        echo '</fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderTemperature(array $d): void
    {
        echo '<fieldset><legend>' . _('Temperature Requirements') . '</legend>';
        echo '<p><label><input type="checkbox" name="temperature_sensitive" value="1"'
            . $this->checked($d, 'temperature_sensitive') . '> '
            . _('Temperature Sensitive — requires controlled shipping') . '</label></p>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td>' . _('Min Temperature') . '</td>';
        echo '<td><input type="number" step="0.1" name="temp_min" value="'
            . $this->vNum($d, 'temp_min') . '"></td>';
        echo '<td>' . _('Max Temperature') . '</td>';
        echo '<td><input type="number" step="0.1" name="temp_max" value="'
            . $this->vNum($d, 'temp_max') . '"></td>';
        echo '<td>' . _('Unit') . '</td>';
        echo '<td>' . $this->selectField('temp_unit', ['C' => '°C', 'F' => '°F'], $d, 'C') . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderCustoms(array $d): void
    {
        echo '<fieldset><legend>' . _('Customs / International Trade') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td>' . _('HS Code') . '</td>';
        echo '<td><input type="text" name="hs_code" maxlength="16" value="'
            . $this->v($d, 'hs_code') . '" placeholder="e.g. 6109.10.00"></td>';
        echo '<td>' . _('Country of Origin') . '</td>';
        echo '<td><input type="text" name="country_of_origin" maxlength="64" value="'
            . $this->v($d, 'country_of_origin') . '"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Customs Description') . '</td>';
        echo '<td colspan="3"><input type="text" name="customs_description" maxlength="255" '
            . 'style="width:100%" value="' . $this->v($d, 'customs_description') . '"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Declared Value') . '</td>';
        echo '<td><input type="number" step="0.01" min="0" name="declared_value" value="'
            . $this->vNum($d, 'declared_value') . '"></td>';
        echo '</tr>';
        echo '</table>';
        echo '</fieldset>';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return an HTML-escaped field value from data, or empty string if absent/null.
     *
     * @param array<string, mixed> $data
     */
    private function v(array $data, string $key): string
    {
        $val = $data[$key] ?? null;
        return ($val !== null && $val !== '') ? htmlspecialchars((string)$val) : '';
    }

    /**
     * Return a normalized numeric HTML-escaped value (strips trailing decimal zeros).
     * e.g. '45.500' → '45.5', '30.000' → '30'.
     *
     * @param array<string, mixed> $data
     */
    private function vNum(array $data, string $key): string
    {
        $val = $data[$key] ?? null;
        if ($val === null || $val === '') {
            return '';
        }
        $str = (string)$val;
        return is_numeric($str)
            ? htmlspecialchars((string)((float)$str))
            : htmlspecialchars($str);
    }

    /**
     * Return ' checked' if the boolean field is truthy or defaults to true.
     *
     * @param array<string, mixed> $data
     */
    private function checked(array $data, string $key, bool $default = false): string
    {
        $val = isset($data[$key]) ? (bool)$data[$key] : $default;
        return $val ? ' checked' : '';
    }

    /**
     * Render a <select> element.
     *
     * @param array<string, string> $options  key => display label
     * @param array<string, mixed>  $data
     */
    private function selectField(string $name, array $options, array $data, string $default): string
    {
        $current = (string)($data[$name] ?? $default);
        $html    = '<select name="' . htmlspecialchars($name) . '">';
        foreach ($options as $val => $label) {
            $sel   = ((string)$val === $current) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars((string)$val) . '"' . $sel . '>'
                . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
