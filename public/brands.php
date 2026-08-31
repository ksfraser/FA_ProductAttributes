<?php

/**
 * Brand / Manufacturer Lookup Admin (FA-native).
 *
 * Manage the dropdown values for Brand and Manufacturer
 * that appear in the Product Identifiers tab.
 *
 * The lookup list is rendered with the reusable
 * Ksfraser\Frontaccounting\HTML\MasterSummaryTable component (ksf_FA_Common),
 * which carries the record id + _tabs_sel through row actions so deletes and
 * edits return to the same type/tab (the no-hard-refresh pattern from #24).
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;
use Ksfraser\Frontaccounting\HTML\MasterSummaryTable;
use Ksfraser\Frontaccounting\HTML\TabContext;

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
$dao = new IdentifierLookupsDao($dbAdapter);

$types = ['brand' => 'Brand', 'manufacturer' => 'Manufacturer'];
$currentType = $_GET['type'] ?? 'brand';
if (!isset($types[$currentType])) {
    $currentType = 'brand';
}

/**
 * Build the MasterSummaryTable for the lookups of the given type.
 *
 * @param array<int, array<string, mixed>> $entries Lookup rows
 * @param string                           $type    Lookup type ('brand' | 'manufacturer')
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_lookups_summary(array $entries, string $type): MasterSummaryTable
{
    $rows = [];
    $n = 0;
    foreach ($entries as $e) {
        $n++;
        $rows[] = [
            'id'   => (int) ($e['id'] ?? 0),
            '#'    => $n,
            'name' => (string) ($e['name'] ?? ''),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => '#', 'label' => '#'],
            ['key' => 'name', 'label' => _('Name')],
        ],
        $rows,
        ['edit' => true, 'delete' => true],
        [
            'record_id_field'      => 'id',
            'row_id_field'         => 'id',
            'tab_sel'              => $type,
            'show_footer'          => false,
            'ajax'                 => false,
            'empty_message'        => _('No entries defined yet.'),
            'delete_confirm_message' => _('Delete this entry?'),
        ]
    );
}

$entries = $dao->listByType($currentType);

$editRowId = 0;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowAction = pa_lookups_summary($entries, $currentType)->getPostedAction($_POST);
    $action    = (string) ($_POST['action'] ?? '');

    // The row-action form posts _tabs_sel = lookup type; use it so deletes and
    // edits return to the same type.
    $returnType = TabContext::fromPost($_POST, 'id')->getTabSel();
    if (!isset($types[$returnType])) {
        $returnType = $currentType;
    }

    if ($rowAction !== null) {
        $rowId = (int) $rowAction['id'];

        if ($rowAction['action'] === 'delete') {
            if ($rowId > 0) {
                $dao->delete($rowId);
            }
            display_notification(_('Record deleted.'));

            // Re-query so the summary table no longer shows the deleted entry
            // in the same request (issue #57).
            $entries = $dao->listByType($currentType);
        } else {
            // Edit: fall through to render the form prefilled with this record.
            $editRowId = $rowId;
        }
    }

    if ($action === 'add_entry') {
        $type = $_POST['entry_type'] ?? 'brand';
        $name = trim((string) ($_POST['name'] ?? ''));
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        if ($name !== '' && isset($types[$type])) {
            if ($entryId > 0) {
                $dao->update($entryId, $name);
            } else {
                $dao->add($type, $name);
            }
        }
        header('Location: ?type=' . rawurlencode($returnType));
        exit;
    }

    if ($action === 'delete_entry') {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        if ($entryId > 0) {
            $dao->delete($entryId);
        }
        header('Location: ?type=' . rawurlencode($returnType));
        exit;
    }
}

$editEntryId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
$editingEntry = null;
foreach ($entries as $e) {
    if ((int) ($e['id'] ?? 0) === $editEntryId) {
        $editingEntry = $e;
        break;
    }
}

page(_('Brand / Manufacturer Management'), false, false, '', '');

echo '<h1>' . _('Brand / Manufacturer Management') . '</h1>';
echo '<p>' . _('Manage the dropdown values that appear in the Product Identifiers tab.') . '</p>';

echo '<div class="nav">';
foreach ($types as $key => $label) {
    $cls = $key === $currentType ? 'active' : '';
    echo '<a href="?type=' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a> &nbsp; ';
}
echo '</div>';
echo '<br>';

echo '<form method="post">';
pa_lookups_summary($entries, $currentType)->render();
echo '</form>';

echo '<fieldset>';
echo '<legend>' . ($editingEntry ? _('Edit') : _('Add')) . ' ' . htmlspecialchars($types[$currentType], ENT_QUOTES, 'UTF-8') . '</legend>';
echo '<form method="post">';
echo '<input type="hidden" name="action" value="add_entry" />';
echo '<input type="hidden" name="entry_type" value="' . htmlspecialchars($currentType, ENT_QUOTES, 'UTF-8') . '" />';
if ($editingEntry) {
    echo '<input type="hidden" name="entry_id" value="' . (int)$editingEntry['id'] . '" />';
}
echo '<div><label for="name">' . _('Name') . '</label>';
echo '<input type="text" id="name" name="name" required maxlength="128" '
    . 'value="' . htmlspecialchars((string)($editingEntry['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '" /></div>';
echo '<div style="margin-top:8px"><button type="submit">' . ($editingEntry ? _('Save') : _('Add')) . ' '
    . htmlspecialchars($types[$currentType], ENT_QUOTES, 'UTF-8') . '</button>';
if ($editingEntry) {
    echo ' <a href="?type=' . htmlspecialchars($currentType, ENT_QUOTES, 'UTF-8') . '" style="margin-left:8px">' . _('Cancel') . '</a>';
}
echo '</div>';
echo '</form>';
echo '</fieldset>';

end_page();
