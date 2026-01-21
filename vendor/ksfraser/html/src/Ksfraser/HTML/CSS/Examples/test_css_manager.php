<?php
/**
 * CSS Manager Example/Test File
 * 
 * This file demonstrates how to use the CSSManager system
 * and serves as a test for the CSS functionality.
 * 
 * When this CSS system is packaged as its own composer module,
 * this file will help developers understand how to use it.
 */

// Adjust path based on where this is run from
$autoloadPaths = [
    __DIR__ . '/../../UIRenderer/autoload.php',           // From CSS directory
    __DIR__ . '/../../../../UIRenderer/autoload.php',     // From project root
    __DIR__ . '/../../../UIRenderer/autoload.php'         // Alternative structure
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use Ksfraser\HTML\CSS\CSSManager;
use Ksfraser\HTML\CSS\Themes\DefaultThemeProvider;

try {
    echo "=== CSS Manager Example/Test ===\n\n";
    
    // Test 1: Basic CSS loading
    echo "1. Testing basic CSS components:\n";
    echo "✓ Base CSS loaded: " . (strlen(CSSManager::getBaseCSS()) > 0 ? "YES" : "NO") . "\n";
    echo "✓ Form CSS loaded: " . (strlen(CSSManager::getFormCSS()) > 0 ? "YES" : "NO") . "\n";
    echo "✓ Table CSS loaded: " . (strlen(CSSManager::getTableCSS()) > 0 ? "YES" : "NO") . "\n";
    echo "✓ Card CSS loaded: " . (strlen(CSSManager::getCardCSS()) > 0 ? "YES" : "NO") . "\n";
    echo "✓ Utility CSS loaded: " . (strlen(CSSManager::getUtilityCSS()) > 0 ? "YES" : "NO") . "\n\n";
    
    // Test 2: Theme system
    echo "2. Testing theme system:\n";
    echo "✓ Current theme: " . CSSManager::getCurrentTheme() . "\n";
    
    CSSManager::setTheme('dark');
    echo "✓ Changed to dark theme: " . CSSManager::getCurrentTheme() . "\n";
    
    CSSManager::setTheme('default');
    echo "✓ Back to default theme: " . CSSManager::getCurrentTheme() . "\n\n";
    
    // Test 3: Provider system
    echo "3. Testing provider system:\n";
    if (class_exists('Ksfraser\HTML\CSS\Themes\DefaultThemeProvider')) {
        $provider = new DefaultThemeProvider();
        CSSManager::registerProvider('default', $provider);
        echo "✓ DefaultThemeProvider registered successfully\n";
        echo "✓ Provider name: " . $provider->getName() . "\n";
        echo "✓ Supports default theme: " . ($provider->supportsTheme('default') ? "YES" : "NO") . "\n";
        echo "✓ Supports dark theme: " . ($provider->supportsTheme('dark') ? "YES" : "NO") . "\n\n";
    }
    
    // Test 4: Complete CSS generation
    echo "4. Testing complete CSS generation:\n";
    $fullCSS = CSSManager::getAllCSS();
    echo "✓ Full CSS length: " . strlen($fullCSS) . " characters\n";
    echo "✓ Contains base styles: " . (strpos($fullCSS, 'font-family') !== false ? "YES" : "NO") . "\n";
    echo "✓ Contains button styles: " . (strpos($fullCSS, '.btn') !== false ? "YES" : "NO") . "\n\n";
    
    // Test 5: Example HTML output
    echo "5. Example HTML output:\n";
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "    <title>CSS Manager Example</title>\n";
    echo "    <style>\n";
    echo "        /* CSS Manager Generated Styles */\n";
    echo substr($fullCSS, 0, 200) . "...\n";
    echo "    </style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "    <div class=\"container\">\n";
    echo "        <div class=\"management-section\">\n";
    echo "            <h1>CSS Manager Example</h1>\n";
    echo "            <p>This demonstrates the CSS Manager system.</p>\n";
    echo "            <button class=\"btn btn-primary\">Primary Button</button>\n";
    echo "            <button class=\"btn btn-success\">Success Button</button>\n";
    echo "        </div>\n";
    echo "    </div>\n";
    echo "</body>\n";
    echo "</html>\n\n";
    
    echo "=== All tests completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Usage examples for documentation:
echo "\n=== Usage Examples ===\n";
echo "\n// Basic usage:\n";
echo "use Ksfraser\\HTML\\CSS\\CSSManager;\n";
echo "echo '<style>' . CSSManager::getAllCSS() . '</style>';\n";

echo "\n// Theme switching:\n";
echo "CSSManager::setTheme('dark');\n";
echo "echo CSSManager::getAllCSS();\n";

echo "\n// Custom provider:\n";
echo "CSSManager::registerProvider('custom', new CustomThemeProvider());\n";
echo "echo CSSManager::getAllCSS();\n";

echo "\n// Individual components:\n";
echo "echo CSSManager::getBaseCSS();\n";
echo "echo CSSManager::getFormCSS();\n";
echo "echo CSSManager::getTableCSS();\n";
?>
