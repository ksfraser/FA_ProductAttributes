<?php

// FrontAccounting wrapper admin page.
// Place this repo under FA: modules/FA_ProductAttributes

$page_security = 'SA_PRODUCTATTRIBUTES';

$path_to_root = '../..';

include($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");

// Force use of configured database connection
/*global $db_connections, $db;
$company = $_SESSION['wa_current_user']->company;
$configured_db = mysql_connect($db_connections[$company]['host'], $db_connections[$company]['user'], $db_connections[$company]['password']);
mysql_select_db($db_connections[$company]['name'], $configured_db);
$db = $configured_db;
*/

/*
// Debug: check path
display_notification("path_to_root: " . $path_to_root);
display_notification("session.inc exists: " . (file_exists($path_to_root . "/includes/session.inc") ? "yes" : "no"));
*/

// Manually define FA_ROOT if it's not set
if (!defined('FA_ROOT')) {
    define('FA_ROOT', $path_to_root . '/');
}

/*
// Fix TB_PREF if it's incorrectly set
if (isset($_SESSION['wa_current_user']->company)) {
    define('TB_PREF', $_SESSION['wa_current_user']->company . '_');
}
    */

$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
if (is_file($autoload)) {
    require_once $autoload;
}

page(_("Product Attributes"));

use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\CategoriesTab;
use Ksfraser\FA_ProductAttributes\UI\ValuesTab;
use Ksfraser\FA_ProductAttributes\UI\AssignmentsTab;

try {
    $db_adapter = new FrontAccountingDbAdapter();
    $dao = new ProductAttributesDao($db_adapter);
    //$dao->ensureSchema();
} catch (Exception $e) {
    display_error("Database error: " . $e->getMessage());
    end_page();
    exit;
}
/*
try {
// Debug: show table prefix
DebugTBPref::debug(0);
display_notification("Table prefix: " . $db_adapter->getTablePrefix());

// Debug: check if tables exist
DebugSchemaNames::debug($db_adapter,0);

// Debug: test db connection
DebugConnection::debug($db_adapter,0);

// Debug: current company
DebugCompany::debug();
} catch (Exception $e) {
    display_error("Debug error: " . $e->getMessage());
}
    */

$tab = $_GET['tab'] ?? 'categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    display_notification("POST data: " . json_encode($_POST));

    if( $action !=== '')
        {
    try {
        if ($action === 'upsert_category') {
            $dao->upsertCategory(
                trim((string)($_POST['code'] ?? '')),
                trim((string)($_POST['label'] ?? '')),
                trim((string)($_POST['description'] ?? '')),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['active'])
            );
            // Debug: check count after save
            $check = $db_adapter->query("SELECT COUNT(*) as cnt FROM `" . $db_adapter->getTablePrefix() . "product_attribute_categories`");
            display_notification("Categories count after save: " . ($check[0]['cnt'] ?? 'error'));
            display_notification(_("Saved category"));
        }

        if ($action === 'upsert_value') {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $dao->upsertValue(
                $categoryId,
                trim((string)($_POST['value'] ?? '')),
                trim((string)($_POST['slug'] ?? '')),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['active'])
            );
            display_notification(_("Saved value"));
        }

        if ($action === 'add_assignment') {
            $stockId = trim((string)($_POST['stock_id'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $valueId = (int)($_POST['value_id'] ?? 0);
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($stockId !== '' && $categoryId > 0 && $valueId > 0) {
                $dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
                display_notification(_("Added assignment"));
            }
        }
    } catch (Exception $e) {
        display_error("Error saving: " . $e->getMessage());
    }
}
}

echo '<div style="margin:8px 0">'
    . '<a href="?tab=categories">Categories</a> | '
    . '<a href="?tab=values">Values</a> | '
    . '<a href="?tab=assignments">Assignments</a>'
    . '</div>';

if ($tab === 'categories') {
    $categoriesTab = new CategoriesTab($dao);
    $categoriesTab->render();
} else if ($tab === 'values') {
    $valuesTab = new ValuesTab($dao);
    $valuesTab->render();
} else {
    $assignmentsTab = new AssignmentsTab($dao);
    $assignmentsTab->render();
}

end_page();
