<?php

// Test script to verify module components work in FA environment
echo "Testing FA Product Attributes Module Components\n\n";

// Test 1: Check autoload
$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
echo "1. Autoload file exists: " . (file_exists($autoload) ? "YES" : "NO") . "\n";
echo "   Path: $autoload\n";

if (file_exists($autoload)) {
    require_once $autoload;
    echo "   Autoload loaded successfully\n";

    // Test 2: Check classes can be instantiated
    try {
        $db = new Ksfraser\ModulesDAO\Db\PdoDbAdapter(new PDO('sqlite::memory:'), '');
        echo "2. PdoDbAdapter instantiated: YES\n";

        $dao = new Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);
        echo "3. ProductAttributesDao instantiated: YES\n";

        // Test 3: Check HTML classes
        $table = new Ksfraser\HTML\Elements\HtmlTable(new Ksfraser\HTML\Elements\HtmlString(''));
        echo "4. HtmlTable instantiated: YES\n";

    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "   Autoload file not found - this is the problem!\n";
}

echo "\nTest complete.\n";