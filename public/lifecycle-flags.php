<?php

/**
 * Lifecycle Flag Definitions Admin (FA-native).
 *
 * Manage the list of storefront flags that appear as checkboxes
 * on the product lifecycle tab.
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;

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
$dao = new LifecycleFlagDefsDao($db);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_flag') {
        $code      = trim((string)($_POST['code'] ?? ''));
        $label     = trim((string)($_POST['label'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active    = isset($_POST['active']) ? 1 : 0;

        if ($code !== '' && $label !== '') {
            $dao->upsertFlag([
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
        $flagId = (int)($_POST['flag_id'] ?? 0);
        if ($flagId > 0) {
            $dao->deleteFlag($flagId);
        }
        header('Location: ?tab=flags');
        exit;
    }
}

$flags = $dao->listFlags();

page(_('Lifecycle Flag Definitions'), false, false, '', '');

echo '<h1>' . _('Lifecycle Flag Definitions') . '</h1>';
echo '<p>' . _('Manage the storefront flags that appear as checkboxes on the product lifecycle tab.') . '</p>';

echo '<table class="tablestyle2">';
echo '<thead><tr>';
echo '<th>' . _('Code') . '</th><th>' . _('Label') . '</th><th>' . _('Sort') . '</th>'
    . '<th>' . _('Active') . '</th><th></th>';
echo '</tr></thead><tbody>';

if (empty($flags)) {
    echo '<tr><td colspan="5"><em>' . _('No flags defined yet.') . '</em></td></tr>';
} else {
    foreach ($flags as $f) {
        $active = !empty($f['active']);
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars((string)($f['code'] ?? '')) . '</code></td>';
        echo '<td>' . htmlspecialchars((string)($f['label'] ?? '')) . '</td>';
        echo '<td>' . (int)($f['sort_order'] ?? 0) . '</td>';
        echo '<td>' . ($active ? _('Yes') : _('No')) . '</td>';
        echo '<td>';
        echo '<form method="post" style="display:inline">';
        echo '<input type="hidden" name="action" value="delete_flag" />';
        echo '<input type="hidden" name="flag_id" value="' . (int)($f['id'] ?? 0) . '" />';
        echo '<button type="submit" style="color:red;background:none;border:none;cursor:pointer;text-decoration:underline" '
            . 'onclick="return confirm(\'' . _('Delete this flag? All products using it will lose the assignment.') . '\')">'
            . _('Delete') . '</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
echo '</tbody></table>';

echo '<fieldset>';
echo '<legend>' . _('Add / Update Flag') . '</legend>';
echo '<form method="post">';
echo '<input type="hidden" name="action" value="add_flag" />';
echo '<div><label for="code">' . _('Code') . '</label>';
echo '<input type="text" id="code" name="code" required placeholder="is_organic" pattern="[a-z_]+" '
    . 'title="' . _('Lowercase letters and underscores only') . '" /> ';
echo '<small>' . _('Internal identifier (lowercase, underscores)') . '</small></div>';
echo '<div><label for="label">' . _('Label') . '</label>';
echo '<input type="text" id="label" name="label" required placeholder="Organic Certified" /> ';
echo '<small>' . _('Display text on the lifecycle tab') . '</small></div>';
echo '<div><label for="sort_order">' . _('Sort Order') . '</label>';
echo '<input type="number" id="sort_order" name="sort_order" value="0" min="0" /></div>';
echo '<div><label for="active">' . _('Active') . '</label>';
echo '<input type="checkbox" id="active" name="active" checked /></div>';
echo '<div style="margin-top:8px"><button type="submit">' . _('Save Flag') . '</button></div>';
echo '</form>';
echo '</fieldset>';

end_page();
