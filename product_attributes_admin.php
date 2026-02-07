<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FrontAccounting wrapper admin page.
// Place this repo under FA: modules/FA_ProductAttributes

$page_security = 'SA_FA_ProductAttributes';

$path_to_root = '../..';

include($path_to_root . "/includes/session.inc");

add_security_extensions();

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

use Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\UI\TabDispatcher;

try {
    $db_adapter = DatabaseAdapterFactory::create('fa'); // Use FA driver via factory
    $dao = new ProductAttributesDao($db_adapter);
    $variationsDao = new VariationsDao($db_adapter);
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

// Check if we're embedded in items.php
$isEmbedded = isset($_GET['selected_tab']);
$selectedTab = $_GET['selected_tab'] ?? $tab;

display_notification("DEBUG: isEmbedded: " . ($isEmbedded ? 'yes' : 'no'));
display_notification("DEBUG: selectedTab: '$selectedTab'");

// Handle both POST and GET actions
$requestData = $_POST;
$action = $_POST['action'] ?? '';

if (empty($action) && isset($_GET['action'])) {
    // Handle GET actions (like delete links)
    $action = $_GET['action'];
    $requestData = $_GET;
    display_notification("DEBUG: GET action detected: '$action'");
}

if (!empty($action)) {
    display_notification("DEBUG: Action detected: '$action'");
    display_notification("Request data: " . json_encode($requestData));

    // First try plugin action handlers via hooks
    $message = null;
    if (function_exists('hook_invoke_all')) {
        $message = hook_invoke_all('fa_product_attributes_handle_action', [$action, $requestData]);
        // hook_invoke_all returns an array, so get the first result if any
        $message = is_array($message) && !empty($message) ? $message[0] : null;
    }

    // If no plugin handled it, use core action handler
    if ($message === null) {
        $actionHandler = new ActionHandler($variationsDao, $dao, $db_adapter);
        display_notification("DEBUG: ActionHandler instantiated");
        $message = $actionHandler->handle($action, $requestData);
        display_notification("DEBUG: ActionHandler->handle() returned: '$message'");
    }

    if ($message) {
        display_notification($message);
    }
}

// Create tab dispatcher and render content
try {
    $dispatcher = new TabDispatcher($dao, $variationsDao, $selectedTab, $isEmbedded);
    display_notification("DEBUG: TabDispatcher instantiated successfully");
    $dispatcher->render();
    display_notification("DEBUG: TabDispatcher render() completed");
} catch (Throwable $e) {
    display_error("ERROR with TabDispatcher: " . $e->getMessage());
    display_error("ERROR type: " . get_class($e));
    display_error("ERROR file: " . $e->getFile() . ":" . $e->getLine());
}

display_notification("DEBUG: About to call end_page()");
end_page();
