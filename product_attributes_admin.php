<?php

// FrontAccounting wrapper admin page.
// Place this repo under FA: modules/FA_ProductAttributes

$page_security = 'SA_PRODUCTATTRIBUTES';

$path_to_root = '../..';

include($path_to_root . "/includes/session.inc");
display_notification("DEBUG: After session.inc include");

add_access_extensions();
display_notification("DEBUG: After add_access_extensions()");

include_once($path_to_root . "/includes/ui.inc");
display_notification("DEBUG: After ui.inc include");

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
display_notification("DEBUG: FA_ROOT defined");

// Fix TB_PREF if it's incorrectly set
if (isset($_SESSION['wa_current_user']->company)) {
    define('TB_PREF', $_SESSION['wa_current_user']->company . '_');
}
display_notification("DEBUG: TB_PREF check completed");

$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
display_notification("DEBUG: autoload path: " . $autoload);

if (is_file($autoload)) {
    require_once $autoload;
    display_notification("DEBUG: autoload file loaded");
} else {
    display_notification("DEBUG: autoload file NOT found");
}

page(_("Product Attributes"));
display_notification("DEBUG: page() function called");

use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\CategoriesTab;
use Ksfraser\FA_ProductAttributes\UI\ValuesTab;
use Ksfraser\FA_ProductAttributes\UI\AssignmentsTab;
use Ksfraser\FA_ProductAttributes\Actions\ActionHandler;
display_notification("DEBUG: use statements completed");

try {
    display_notification("DEBUG: Starting database initialization");
    $db_adapter = new FrontAccountingDbAdapter();
    display_notification("DEBUG: FrontAccountingDbAdapter instantiated");

    $dao = new ProductAttributesDao($db_adapter);
    display_notification("DEBUG: ProductAttributesDao instantiated");

    //$dao->ensureSchema(); // Tables already exist
    display_notification("DEBUG: ensureSchema commented out");

} catch (Exception $e) {
    display_error("Database error: " . $e->getMessage());
    end_page();
    exit;
}
display_notification("DEBUG: Database initialization completed");
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
display_notification("DEBUG: tab variable set to: '$tab'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    display_notification("DEBUG: POST request detected");
    $action = $_POST['action'] ?? '';
    display_notification("POST data: " . json_encode($_POST));

    $actionHandler = new ActionHandler($dao, $db_adapter);
    display_notification("DEBUG: ActionHandler instantiated");

    $message = $actionHandler->handle($action, $_POST);
    display_notification("DEBUG: ActionHandler->handle() returned: '$message'");

    if ($message) {
        display_notification($message);
    }
} else {
    display_notification("DEBUG: Not a POST request");
}


display_notification("DEBUG: About to render tab navigation");
echo '<div style="margin:8px 0">'
    . '<a href="?tab=categories">Categories</a> | '
    . '<a href="?tab=values">Values</a> | '
    . '<a href="?tab=assignments">Assignments</a>'
    . '</div>';
display_notification("DEBUG: Tab navigation rendered");

display_notification("Current tab: '$tab'");

if ($tab === 'categories') {
    display_notification("Rendering categories tab");
    $categoriesTab = new CategoriesTab($dao);
    $categoriesTab->render();
} else if ($tab === 'values') {
    display_notification("Rendering values tab");
    $valuesTab = new ValuesTab($dao);
    $valuesTab->render();
} else {
    display_notification("Rendering assignments tab");
    $assignmentsTab = new AssignmentsTab($dao);
    $assignmentsTab->render();
}

display_notification("DEBUG: About to call end_page()");
end_page();
