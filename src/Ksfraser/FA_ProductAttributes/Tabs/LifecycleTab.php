<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertProductLifecycleAction;

class LifecycleTab extends AbstractTab
{
    /** @var ProductLifecycleDao */
    private $lifecycleDao;

    /** @var LifecycleFlagDefsDao */
    private $flagDefsDao;

    public function __construct(ProductLifecycleDao $lifecycleDao, LifecycleFlagDefsDao $flagDefsDao)
    {
        $this->lifecycleDao = $lifecycleDao;
        $this->flagDefsDao = $flagDefsDao;
    }

    public function getName(): string
    {
        return 'product_lifecycle';
    }

    public function getTabKey(): string
    {
        return 'product_lifecycle';
    }

    public function getTabLabel(): string
    {
        return _('Lifecycle');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $data = ($stockId !== '') ? ($this->lifecycleDao->get($stockId) ?? []) : [];
        $currentFlags = ($stockId !== '') ? $this->flagDefsDao->getAssignedFlagIds($stockId) : [];
        $assignedSet  = array_flip(array_map('strval', $currentFlags));
        $flags = $this->flagDefsDao->listActiveFlags();

        $statusCurrent = (string)($data['status'] ?? 'active');
        $statuses = ['active' => 'Active', 'draft' => 'Draft', 'discontinued' => 'Discontinued', 'archived' => 'Archived'];

        echo '<input type="hidden" name="action"   value="save_product_lifecycle">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<fieldset><legend>' . _('Status') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Product Status') . '</td>';
        echo '<td><select name="status">';
        foreach ($statuses as $val => $label) {
            $sel = ($statusCurrent === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select></td></tr></table></fieldset>';

        if (!empty($flags)) {
            echo '<fieldset><legend>' . _('Storefront Flags') . '</legend>';
            echo '<table class="tablestyle_noborder">';
            foreach ($flags as $flag) {
                $flagId  = (int)($flag['id'] ?? 0);
                $code    = htmlspecialchars((string)($flag['code'] ?? ''));
                $label   = htmlspecialchars((string)($flag['label'] ?? ''));
                $checked = isset($assignedSet[(string)$flagId]) ? ' checked' : '';
                echo '<tr>';
                echo '<td><label for="lflag_' . $flagId . '">' . $label . '</label></td>';
                echo '<td><input type="checkbox" id="lflag_' . $flagId . '" '
                    . 'name="lifecycle_flags[]" value="' . $flagId . '"' . $checked . '></td>';
                echo '</tr>';
            }
            echo '</table></fieldset>';
        }

        $from = htmlspecialchars((string)($data['available_from'] ?? ''));
        $on   = htmlspecialchars((string)($data['discontinue_on'] ?? ''));
        $note = htmlspecialchars((string)($data['clearance_note'] ?? ''));

        echo '<fieldset><legend>' . _('Availability Window') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('Available From') . '</td>';
        echo '<td><input type="date" name="available_from" value="' . $from . '"></td></tr>';
        echo '<tr><td>' . _('Discontinue On') . '</td>';
        echo '<td><input type="date" name="discontinue_on" value="' . $on . '"></td></tr>';
        echo '<tr><td>' . _('Clearance Note') . '</td>';
        echo '<td><input type="text" name="clearance_note" maxlength="255" value="' . $note . '" style="width:100%"></td></tr>';
        echo '</table></fieldset>';

        echo '<p><input type="submit" name="pa_lifecycle_save" value="' . _('Save') . '"></p>';
    }

    /**
     * Handle inline POST actions from the tab (lifecycle save) so the product
     * and tab are retained after saving (no hard refresh).
     */
    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (($_POST['action'] ?? '') === 'save_product_lifecycle' && isset($_POST['pa_lifecycle_save'])) {
            $this->handleSave($stockId, $_POST);
            if (function_exists('display_notification')) {
                display_notification(_('Lifecycle details saved.'));
            }
        }
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $lifecycleKeys = ['status', 'available_from', 'discontinue_on', 'clearance_note'];
        $data = [];
        foreach ($lifecycleKeys as $key) {
            if (array_key_exists($key, $postData)) {
                $data[$key] = $postData[$key];
            }
        }
        if (!empty($data)) {
            $this->lifecycleDao->upsert($stockId, $data);
        }

        $flagIds = [];
        if (isset($postData['lifecycle_flags']) && is_array($postData['lifecycle_flags'])) {
            $flagIds = array_map('intval', $postData['lifecycle_flags']);
        }
        $this->flagDefsDao->setAssignedFlags($stockId, $flagIds);
    }

    public function handleDelete(string $stockId): void
    {
        $this->lifecycleDao->delete($stockId);
        $this->flagDefsDao->deleteAssignments($stockId);
    }
}
