<?php

/**
 * Lifecycle Flag Definitions — redirect stub.
 *
 * Lifecycle flags moved into the consolidated Product Attributes admin hub
 * (public/index.php?tab=flags). This file only redirects legacy URLs (old
 * bookmarks / previously-registered menu entries) to the hub. It still
 * bootstraps through FA's session layer so the redirect runs under the same
 * security and session rules as every other public page.
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

// Resolve all relative includes from this module directory. Restore CWD on
// shutdown so Apache mod_php does not leak it into subsequent requests.
chdir(__DIR__);
register_shutdown_function(function () {
    $target = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
    if (is_string($target) && is_dir($target)) {
        @chdir($target);
    }
});

// Load the Composer autoloader.
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
}
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Preload the FA database adapter before session.inc registers the other
// modules' autoloaders (see index.php for why this ordering matters).
class_exists(\Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter::class);

// Security area MUST be set before session.inc is included.
$page_security = 'SA_OPEN';

$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Required for direct-access module pages using extension security areas.
add_access_extensions();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$dbAdapter  = new FrontAccountingDbAdapter($tablePrefix);

header('Location: index.php?tab=flags');
exit;