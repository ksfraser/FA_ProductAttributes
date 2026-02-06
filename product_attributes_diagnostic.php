<?php

// Minimal diagnostic version of product_attributes_admin.php
// This version has extensive error reporting to help debug the issue

error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_security = 'SA_FA_ProductAttributes';

$path_to_root = realpath(__DIR__ . '/../../');
if ($path_to_root === false) {
    $path_to_root = '../..';
}

echo "<!-- Debug: path_to_root = $path_to_root -->\n";

include($path_to_root . "/includes/session.inc");
echo "<!-- Debug: session.inc included -->\n";

add_security_extensions();
echo "<!-- Debug: security extensions added -->\n";

add_access_extensions();
echo "<!-- Debug: access extensions added -->\n";

include_once($path_to_root . "/includes/ui.inc");
echo "<!-- Debug: ui.inc included -->\n";

page(_("Product Attributes Diagnostic"));

echo "<h1>Product Attributes Module Diagnostic</h1>\n";

echo "<h2>Environment Check</h2>\n";
echo "<ul>\n";
echo "<li>PHP Version: " . phpversion() . "</li>\n";
echo "<li>Current Directory: " . __DIR__ . "</li>\n";
echo "<li>Path to Root: $path_to_root</li>\n";
echo "</ul>\n";

echo "<h2>Composer Autoload Check</h2>\n";
$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
echo "<ul>\n";
echo "<li>Autoload Path: $autoload</li>\n";
echo "<li>File Exists: " . (file_exists($autoload) ? "YES" : "NO") . "</li>\n";

if (file_exists($autoload)) {
    echo "<li>File Readable: " . (is_readable($autoload) ? "YES" : "NO") . "</li>\n";

    try {
        require_once $autoload;
        echo "<li>Autoload Loaded: SUCCESS</li>\n";

        echo "<h2>Class Loading Check</h2>\n";
        echo "<ul>\n";

        try {
            $db = new Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter();
            echo "<li>FrontAccountingDbAdapter: SUCCESS</li>\n";
        } catch (Exception $e) {
            echo "<li>FrontAccountingDbAdapter: FAILED - " . $e->getMessage() . "</li>\n";
        }

        try {
            $dao = new Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);
            echo "<li>ProductAttributesDao: SUCCESS</li>\n";
        } catch (Exception $e) {
            echo "<li>ProductAttributesDao: FAILED - " . $e->getMessage() . "</li>\n";
        }

        try {
            $table = new Ksfraser\HTML\Elements\HtmlTable(new Ksfraser\HTML\Elements\HtmlString(''));
            echo "<li>HtmlTable: SUCCESS</li>\n";
        } catch (Exception $e) {
            echo "<li>HtmlTable: FAILED - " . $e->getMessage() . "</li>\n";
        }

        echo "</ul>\n";

    } catch (Exception $e) {
        echo "<li>Autoload Loading: FAILED - " . $e->getMessage() . "</li>\n";
    }
} else {
    echo "<li><strong>PROBLEM: Autoload file does not exist!</strong></li>\n";
    echo "<li>This means composer dependencies were not installed during module activation.</li>\n";
    echo "<li>Please check the FA error logs and try reinstalling the module.</li>\n";
}

echo "</ul>\n";

end_page();