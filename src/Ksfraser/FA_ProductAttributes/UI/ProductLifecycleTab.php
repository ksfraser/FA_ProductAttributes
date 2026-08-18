<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;

/**
 * Single Responsibility: Renders the Product Lifecycle / Status admin tab.
 *
 * Surfaces storefront-facing status information beyond FA's own active/inactive
 * flag:
 *   - Sales / visibility status (active, draft, discontinued, archived)
 *   - Admin-configurable boolean flags (from product_lifecycle_flag_defs)
 *   - Availability window (available_from / discontinue_on dates)
 *   - Clearance note
 */
class ProductLifecycleTab
{
    /** @var ProductLifecycleDao */
    private $dao;

    /** @var LifecycleFlagDefsDao|null */
    private $flagDefsDao;

    /** @var string[] */
    private static $statusLabels = [
        'active'       => 'Active',
        'draft'        => 'Draft',
        'discontinued' => 'Discontinued',
        'archived'     => 'Archived',
    ];

    public function __construct(ProductLifecycleDao $dao, ?LifecycleFlagDefsDao $flagDefsDao = null)
    {
        $this->dao         = $dao;
        $this->flagDefsDao = $flagDefsDao;
    }

    /**
     * Render the lifecycle form.
     * When $stockId is empty the form renders with default values.
     */
    public function render(string $stockId = ''): void
    {
        $data = ($stockId !== '') ? ($this->dao->get($stockId) ?? []) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_lifecycle">';

        $this->renderStatus($data);
        $this->renderDynamicFlags($stockId, $data);
        $this->renderDates($data);

        echo '<p><input type="submit" value="' . _('Save Lifecycle') . '"></p>';
        echo '</form>';
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $d */
    private function renderStatus(array $d): void
    {
        $current = (string)($d['status'] ?? 'active');

        echo '<fieldset><legend>' . _('Status') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td><label for="lifecycle_status">' . _('Product Status') . '</label></td>';
        echo '<td><select id="lifecycle_status" name="status" class="form-control">';
        foreach (self::$statusLabels as $val => $label) {
            $sel = ($current === $val) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>'
                . htmlspecialchars(_($label)) . '</option>';
        }
        echo '</select></td>';
        echo '</tr></table></fieldset>';
    }

    /**
     * Render flags dynamically from admin config.
     * Falls back to hardcoded flags if the flag_defs DAO is not available.
     *
     * @param array<string, mixed> $d
     */
    private function renderDynamicFlags(string $stockId, array $d): void
    {
        if ($this->flagDefsDao === null) {
            $this->renderHardcodedFlags($d);
            return;
        }

        $flags = $this->flagDefsDao->listActiveFlags();
        if (empty($flags)) {
            return;
        }

        $assignedIds = ($stockId !== '') ? $this->flagDefsDao->getAssignedFlagIds($stockId) : [];
        $assignedSet = array_flip(array_map('strval', $assignedIds));

        echo '<fieldset><legend>' . _('Storefront Flags') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach ($flags as $flag) {
            $flagId  = (int)($flag['id'] ?? 0);
            $code    = htmlspecialchars((string)($flag['code'] ?? ''));
            $label   = htmlspecialchars((string)($flag['label'] ?? ''));
            $checked = isset($assignedSet[(string)$flagId]) ? ' checked' : '';
            echo '<tr>';
            echo '<td><label for="lifecycle_flag_' . $flagId . '">' . $label . '</label></td>';
            echo '<td><input type="checkbox" id="lifecycle_flag_' . $flagId . '" '
                . 'name="lifecycle_flags[]" value="' . $flagId . '"' . $checked . '></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';
    }

    /**
     * Fallback: render hardcoded flags when admin config is not available.
     *
     * @param array<string, mixed> $d
     */
    private function renderHardcodedFlags(array $d): void
    {
        $flags = [
            'is_special_order'       => _('Special Order (not kept in stock)'),
            'is_clearance'           => _('Clearance'),
            'is_out_of_stock_notice' => _('Display Out-of-Stock Notice'),
            'is_new_arrival'         => _('New Arrival'),
            'is_bestseller'          => _('Bestseller'),
            'is_featured'            => _('Featured (homepage / collection)'),
            'is_seasonal'            => _('Seasonal'),
        ];

        echo '<fieldset><legend>' . _('Storefront Flags') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach ($flags as $field => $label) {
            $checked = !empty($d[$field]) ? ' checked' : '';
            echo '<tr>';
            echo '<td><label for="lifecycle_' . $field . '">' . $label . '</label></td>';
            echo '<td><input type="checkbox" id="lifecycle_' . $field . '" '
                . 'name="' . $field . '" value="1"' . $checked . '></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';
    }

    /** @param array<string, mixed> $d */
    private function renderDates(array $d): void
    {
        echo '<fieldset><legend>' . _('Availability Window') . '</legend>';
        echo '<table class="tablestyle_noborder">';

        $from = htmlspecialchars((string)($d['available_from'] ?? ''));
        echo '<tr>';
        echo '<td><label for="lifecycle_available_from">' . _('Available From') . '</label></td>';
        echo '<td><input type="date" id="lifecycle_available_from" name="available_from" '
            . 'value="' . $from . '" class="form-control">'
            . ' <small>' . _('Pre-order / availability start date (YYYY-MM-DD)') . '</small></td>';
        echo '</tr>';

        $on = htmlspecialchars((string)($d['discontinue_on'] ?? ''));
        echo '<tr>';
        echo '<td><label for="lifecycle_discontinue_on">' . _('Discontinue On') . '</label></td>';
        echo '<td><input type="date" id="lifecycle_discontinue_on" name="discontinue_on" '
            . 'value="' . $on . '" class="form-control">'
            . ' <small>' . _('Planned end-of-life date (YYYY-MM-DD)') . '</small></td>';
        echo '</tr>';

        $note = htmlspecialchars((string)($d['clearance_note'] ?? ''));
        echo '<tr>';
        echo '<td><label for="lifecycle_clearance_note">' . _('Clearance Note') . '</label></td>';
        echo '<td><input type="text" id="lifecycle_clearance_note" name="clearance_note" '
            . 'value="' . $note . '" maxlength="255" class="form-control"></td>';
        echo '</tr>';

        echo '</table></fieldset>';
    }
}
