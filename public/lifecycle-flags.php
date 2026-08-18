<?php

/**
 * Lifecycle Flag Definitions Admin (FA-native).
 *
 * Manage the list of storefront flags that appear as checkboxes
 * on the product lifecycle tab.
 *
 * The flag list is rendered with the reusable
 * Ksfraser\Frontaccounting\HTML\MasterSummaryTable component (ksf_FA_Common),
 * which carries the record id + _tabs_sel through row actions so deletes and
 * edits return to the same tab (the no-hard-refresh pattern from issue #24).
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\Frontaccounting\HTML\MasterSummaryTable;

// Resolve all relative includes from this module directory.
chdir(__DIR__);

// Load the Composer autoloader.
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
}
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Preload the FA database adapter before session.inc registers the other
// modules' autoloaders. Some deployed modules vendor older ksf-modules-dao
// copies that declare the same class name; preloading here guarantees this
// module's newer implementation is the one used for the request.
class_exists(\Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter::class);

// Security area MUST be set before session.inc is included.
$page_security = 'SA_OPEN';

$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Required for direct-access module pages using extension security areas.
add_access_extensions();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$dbAdapter  = new FrontAccountingDbAdapter($tablePrefix);
$dao = new LifecycleFlagDefsDao($dbAdapter);

/**
 * Build the MasterSummaryTable for the flag definitions.
 *
 * @param array<int, array<string, mixed>> $flags Flag definition rows
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_flags_summary(array $flags): MasterSummaryTable
{
    $rows = [];
    foreach ($flags as $f) {
        $rows[] = [
            'id'         => (int) ($f['id'] ?? 0),
            'code'       => (string) ($f['code'] ?? ''),
            'label'      => (string) ($f['label'] ?? ''),
            'sort_order' => (int) ($f['sort_order'] ?? 0),
            'active'     => !empty($f['active']) ? _('Yes') : _('No'),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => 'code', 'label' => _('Code')],
            ['key' => 'label', 'label' => _('Label')],
            ['key' => 'sort_order', 'label' => _('Sort')],
            ['key' => 'active', 'label' => _('Active')],
        ],
        $rows,
        ['edit' => true, 'delete' => true],
        [
            'record_id_field'      => 'id',
            'row_id_field'         => 'id',
            'tab_sel'              => 'flags',
            'show_footer'          => false,
            'ajax'                 => false,
            'empty_message'        => _('No flags defined yet.'),
            'delete_confirm_message' => _('Delete this flag? All products using it will lose the assignment.'),
        ]
    );
}

$flags = $dao->listFlags();

$editRowId = 0;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowAction = pa_flags_summary($flags)->getPostedAction($_POST);
    $action    = (string) ($_POST['action'] ?? '');

    if ($rowAction !== null) {
        $rowId = (int) $rowAction['id'];

        if ($rowAction['action'] === 'delete') {
            if ($rowId > 0) {
                $dao->deleteFlag($rowId);
            }
            display_notification(_('Record deleted.'));
        }

        // Edit: fall through to render the form prefilled with this record.
        $editRowId = $rowId;
    }

    if ($action === 'add_flag') {
        $code      = trim((string) ($_POST['code'] ?? ''));
        $label     = trim((string) ($_POST['label'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $active    = isset($_POST['active']) ? 1 : 0;
        $flagId    = (int) ($_POST['flag_id'] ?? 0);

        if ($code !== '' && $label !== '') {
            $dao->upsertFlag([
                'id'         => $flagId > 0 ? $flagId : null,
                'code'       => $code,
                'label'      => $label,
                'sort_order' => $sortOrder,
                'active'     => $active,
            ]);
        }
        header('Location: ?tab=flags');
        exit;
    }

    if ($action === 'delete_flag') {
        $flagId = (int) ($_POST['flag_id'] ?? 0);
        if ($flagId > 0) {
            $dao->deleteFlag($flagId);
        }
        header('Location: ?tab=flags');
        exit;
    }
}

$editFlagId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
$editingFlag = null;
foreach ($flags as $f) {
    if ((int) ($f['id'] ?? 0) === $editFlagId) {
        $editingFlag = $f;
        break;
    }
}

page(_('Lifecycle Flag Definitions'), false, false, '', '');

echo '<h1>' . _('Lifecycle Flag Definitions') . '</h1>';
echo '<p>' . _('Manage the storefront flags that appear as checkboxes on the product lifecycle tab.') . '</p>';

echo '<form method="post">';
pa_flags_summary($flags)->render();
echo '</form>';

$formCode      = $editingFlag ? (string) ($editingFlag['code'] ?? '') : '';
$formLabel     = $editingFlag ? (string) ($editingFlag['label'] ?? '') : '';
$formSort      = $editingFlag ? (int) ($editingFlag['sort_order'] ?? 0) : 0;
$formActive    = $editingFlag ? ((int) ($editingFlag['active'] ?? 1) === 1) : true;

echo '<fieldset>';
echo '<legend>' . ($editingFlag ? _('Edit Flag') : _('Add Flag')) . '</legend>';
echo '<form method="post">';
echo '<input type="hidden" name="action" value="add_flag" />';
if ($editingFlag) {
    echo '<input type="hidden" name="flag_id" value="' . (int)$editingFlag['id'] . '" />';
}
echo '<div><label for="code">' . _('Code') . '</label>';
echo '<input type="text" id="code" name="code" required placeholder="is_organic" pattern="[a-z0-9_-]+" '
    . 'title="' . _('Letters, numbers, underscores and hyphens only') . '" value="' . htmlspecialchars($formCode, ENT_QUOTES, 'UTF-8') . '" /> ';
echo '<small>' . _('Internal identifier (letters, numbers, underscores)') . '</small></div>';
echo '<div><label for="label">' . _('Label') . '</label>';
echo '<input type="text" id="label" name="label" required placeholder="Organic Certified" '
    . 'value="' . htmlspecialchars($formLabel, ENT_QUOTES, 'UTF-8') . '" /> ';
echo '<small>' . _('Display text on the lifecycle tab') . '</small></div>';
echo '<div><label for="sort_order">' . _('Sort Order') . '</label>';
echo '<input type="number" id="sort_order" name="sort_order" value="' . $formSort . '" min="0" /></div>';
echo '<div><label for="active">' . _('Active') . '</label>';
echo '<input type="checkbox" id="active" name="active"' . ($formActive ? ' checked' : '') . ' /></div>';
echo '<div style="margin-top:8px"><button type="submit">' . _('Save Flag') . '</button>';
if ($editingFlag) {
    echo ' <a href="?tab=flags" style="margin-left:8px">' . _('Cancel') . '</a>';
}
echo '</div>';
echo '</form>';
echo '</fieldset>';

end_page();
