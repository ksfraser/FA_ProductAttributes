<?php

/**
 * Brand / Manufacturer Lookup Admin (FA-native).
 *
 * Manage the dropdown values for Brand and Manufacturer
 * that appear in the Product Identifiers tab.
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;

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

// Security area MUST be set before session.inc is included.
$page_security = 'SA_OPEN';

$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Required for direct-access module pages using extension security areas.
add_access_extensions();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$db  = new FrontAccountingDbAdapter($tablePrefix);
$dao = new IdentifierLookupsDao($db);

$types = ['brand' => 'Brand', 'manufacturer' => 'Manufacturer'];
$currentType = $_GET['type'] ?? 'brand';
if (!isset($types[$currentType])) {
    $currentType = 'brand';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_entry') {
        $type = $_POST['entry_type'] ?? 'brand';
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '' && isset($types[$type])) {
            $dao->add($type, $name);
        }
        header('Location: ?type=' . rawurlencode($type));
        exit;
    }

    if ($action === 'delete_entry') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $returnType = $_POST['entry_type'] ?? 'brand';
        if ($entryId > 0) {
            $dao->delete($entryId);
        }
        header('Location: ?type=' . rawurlencode($returnType));
        exit;
    }
}

$entries = $dao->listByType($currentType);

page(_('Brand / Manufacturer Management'), false, false, '', '');

echo '<h1>' . _('Brand / Manufacturer Management') . '</h1>';
echo '<p>' . _('Manage the dropdown values that appear in the Product Identifiers tab.') . '</p>';

echo '<div class="nav">';
foreach ($types as $key => $label) {
    $cls = $key === $currentType ? 'active' : '';
    echo '<a href="?type=' . htmlspecialchars($key) . '" class="' . $cls . '">'
        . htmlspecialchars($label) . '</a> &nbsp; ';
}
echo '</div>';
echo '<br>';

echo '<table class="tablestyle2">';
echo '<thead><tr>';
echo '<th>#</th><th>' . _('Name') . '</th><th></th>';
echo '</tr></thead><tbody>';

if (empty($entries)) {
    echo '<tr><td colspan="3"><em>' . _('No entries defined yet.') . '</em></td></tr>';
} else {
    foreach ($entries as $e) {
        echo '<tr>';
        echo '<td>' . (int)($e['id'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars((string)($e['name'] ?? '')) . '</td>';
        echo '<td>';
        echo '<form method="post" style="display:inline">';
        echo '<input type="hidden" name="action" value="delete_entry" />';
        echo '<input type="hidden" name="entry_id" value="' . (int)($e['id'] ?? 0) . '" />';
        echo '<input type="hidden" name="entry_type" value="' . htmlspecialchars($currentType) . '" />';
        echo '<button type="submit" style="color:red;background:none;border:none;cursor:pointer;text-decoration:underline" '
            . 'onclick="return confirm(\'' . _('Delete this entry?') . '\')">' . _('Delete') . '</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
echo '</tbody></table>';

echo '<fieldset>';
echo '<legend>' . _('Add') . ' ' . htmlspecialchars($types[$currentType]) . '</legend>';
echo '<form method="post">';
echo '<input type="hidden" name="action" value="add_entry" />';
echo '<input type="hidden" name="entry_type" value="' . htmlspecialchars($currentType) . '" />';
echo '<div><label for="name">' . _('Name') . '</label>';
echo '<input type="text" id="name" name="name" required maxlength="128" /></div>';
echo '<div style="margin-top:8px"><button type="submit">' . _('Add') . ' '
    . htmlspecialchars($types[$currentType]) . '</button></div>';
echo '</form>';
echo '</fieldset>';

end_page();
