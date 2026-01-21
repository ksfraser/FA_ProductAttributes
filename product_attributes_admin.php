<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Fix TB_PREF if it's incorrectly set
if (isset($_SESSION['wa_current_user']->company)) {
    define('TB_PREF', $_SESSION['wa_current_user']->company . '_');
}

$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
if (is_file($autoload)) {
    require_once $autoload;
}

page(_("Product Attributes"));

use Ksfraser\FA_ProductAttributes\Db\DatabaseAdapterFactory;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\CategoriesTab;
use Ksfraser\FA_ProductAttributes\UI\ValuesTab;
use Ksfraser\FA_ProductAttributes\UI\AssignmentsTab;
use Ksfraser\FA_ProductAttributes\Actions\ActionHandler;

try {
    $db_adapter = DatabaseAdapterFactory::create('fa'); // Use FA driver via factory
    $dao = new ProductAttributesDao($db_adapter);
    //$dao->ensureSchema(); // Tables already exist
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

$tab = $_GET['tab'] ?? $_POST['tab'] ?? 'categories';
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

    // Redirect to preserve tab state after POST processing
    header("Location: ?tab=" . urlencode($tab));
    exit;
} else {
    display_notification("DEBUG: Not a POST request");
}


echo '<div style="margin:8px 0">'
    . '<a href="?tab=categories">Categories</a> | '
    . '<a href="?tab=values">Values</a> | '
    . '<a href="?tab=assignments">Assignments</a>'
    . '</div>';
display_notification("DEBUG: Tab navigation rendered");

display_notification("Current tab: '$tab'");

if ($tab === 'categories') {
    display_notification("Rendering categories tab");
    display_notification("DEBUG: About to instantiate CategoriesTab");
    display_notification("DEBUG: dao type: " . get_class($dao));
    display_notification("DEBUG: dao is object: " . (is_object($dao) ? 'yes' : 'no'));
    try {
        $categoriesTab = new CategoriesTab($dao);
        display_notification("DEBUG: CategoriesTab instantiated successfully");
        $categoriesTab->render();
        display_notification("DEBUG: CategoriesTab render() completed");
    } catch (Throwable $e) {
        display_error("ERROR instantiating CategoriesTab: " . $e->getMessage());
        display_error("ERROR type: " . get_class($e));
        display_error("ERROR file: " . $e->getFile() . ":" . $e->getLine());
    }
} else if ($tab === 'values') {
    try {
        $valuesTab = new ValuesTab($dao);
        display_notification("DEBUG: ValuesTab instantiated successfully");
        $valuesTab->render();
        display_notification("DEBUG: ValuesTab render() completed");
    } catch (Throwable $e) {
        display_error("ERROR instantiating/rendering ValuesTab: " . $e->getMessage());
        display_error("ERROR type: " . get_class($e));
        display_error("ERROR file: " . $e->getFile() . ":" . $e->getLine());
    }
} else {
    try {
        $assignmentsTab = new AssignmentsTab($dao);
        display_notification("DEBUG: AssignmentsTab instantiated successfully");
        $assignmentsTab->render();
        display_notification("DEBUG: AssignmentsTab render() completed");
    } catch (Throwable $e) {
        display_error("ERROR instantiating/rendering AssignmentsTab: " . $e->getMessage());
        display_error("ERROR type: " . get_class($e));
        display_error("ERROR file: " . $e->getFile() . ":" . $e->getLine());
    }
}

display_notification("DEBUG: About to call end_page()");
end_page();
