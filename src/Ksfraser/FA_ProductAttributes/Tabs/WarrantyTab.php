<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertWarrantyAction;

class WarrantyTab extends AbstractTab
{
    /** @var ProductWarrantyDao */
    private $dao;

    public function __construct(ProductWarrantyDao $dao)
    {
        $this->dao = $dao;
    }

    public function getName(): string
    {
        return 'product_warranty';
    }

    public function getTabKey(): string
    {
        return 'product_warranty';
    }

    public function getTabLabel(): string
    {
        return _('Warranty');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        $currentType = (string)($data['warranty_type'] ?? 'none');
        $types = [
            'none'         => 'None',
            'manufacturer' => 'Manufacturer Warranty',
            'extended'     => 'Extended Warranty',
            'third_party'  => 'Third-Party Warranty',
            'lifetime'     => 'Lifetime Warranty',
        ];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="save_product_warranty">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<fieldset><legend>' . _('Warranty Type') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach ($types as $val => $label) {
            $checked = ($currentType === $val) ? ' checked' : '';
            echo '<tr>';
            echo '<td><input type="radio" name="warranty_type" value="' . $val . '"' . $checked
                . ' id="wt_' . $val . '"></td>';
            echo '<td><label for="wt_' . $val . '">' . $label . '</label></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';

        $mfgDur = $data['manufacturer_duration'] ?? '';
        $mfgUnit = $data['manufacturer_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Manufacturer Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="manufacturer_duration" min="0" value="'
            . htmlspecialchars((string)$mfgDur) . '"></td>';
        echo '<td>' . $this->durationUnitSelect('manufacturer_duration_unit', $mfgUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $extDur = $data['extended_duration'] ?? '';
        $extUnit = $data['extended_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Extended Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="extended_duration" min="0" value="'
            . htmlspecialchars((string)$extDur) . '"></td>';
        echo '<td>' . $this->durationUnitSelect('extended_duration_unit', $extUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $tpDur = $data['third_party_duration'] ?? '';
        $tpUnit = $data['third_party_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Third-Party Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="third_party_duration" min="0" value="'
            . htmlspecialchars((string)$tpDur) . '"></td>';
        echo '<td>' . $this->durationUnitSelect('third_party_duration_unit', $tpUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $lifeNotes = htmlspecialchars((string)($data['lifetime_notes'] ?? ''));
        echo '<fieldset><legend>' . _('Lifetime Warranty Notes') . '</legend>';
        echo '<input type="text" name="lifetime_notes" maxlength="255" value="' . $lifeNotes . '" style="width:100%">';
        echo '</fieldset>';

        $warrantyNotes = htmlspecialchars((string)($data['warranty_notes'] ?? ''));
        echo '<fieldset><legend>' . _('Warranty Terms / General Notes') . '</legend>';
        echo '<textarea name="warranty_notes" rows="4" style="width:100%">' . $warrantyNotes . '</textarea>';
        echo '</fieldset>';

        echo '<p><input type="submit" value="' . _('Save Warranty') . '"></p>';
        echo '</form>';
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }
        if (($_POST['action'] ?? '') !== 'save_product_warranty') {
            return;
        }

        $this->handleSave($stockId, $_POST);

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $action = new UpsertWarrantyAction($this->dao);
        $action->handle($stockId, $postData);
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->delete($stockId);
    }

    private function durationUnitSelect(string $name, string $current): string
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
