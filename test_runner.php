<?php
/**
 * Simple Test Runner for FA_ProductAttributes
 * Runs tests manually without PHPUnit CLI
 */

echo "FA_ProductAttributes Test Runner\n";
echo "================================\n\n";

// Include the autoloader (assuming it works for our classes)
require_once __DIR__ . '/composer-lib/vendor/autoload.php';

// Test classes to run
$testClasses = [
    'Ksfraser\\FA_ProductAttributes\\Test\\Service\\ProductAttributesServiceTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Dao\\ProductAttributesDaoTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\AddCategoryActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\UpdateCategoryActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\DeleteCategoryActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\AddValueActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\UpdateValueActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\DeleteValueActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\AddAssignmentActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\UpdateAssignmentActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Action\\DeleteAssignmentActionTest',
    'Ksfraser\\FA_ProductAttributes\\Test\\Service\\BulkOperationsServiceTest',
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testClasses as $testClass) {
    echo "Running $testClass...\n";

    if (!class_exists($testClass)) {
        echo "❌ Test class $testClass not found\n\n";
        $failedTests++;
        continue;
    }

    try {
        $testInstance = new $testClass();

        // Get all test methods (methods starting with 'test')
        $reflection = new ReflectionClass($testInstance);
        $testMethods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), function($method) {
            return strpos($method->getName(), 'test') === 0;
        });

        foreach ($testMethods as $method) {
            $methodName = $method->getName();
            $totalTests++;

            try {
                // Run setUp if it exists
                if (method_exists($testInstance, 'setUp')) {
                    $testInstance->setUp();
                }

                // Run the test method
                $testInstance->$methodName();

                // Run tearDown if it exists
                if (method_exists($testInstance, 'tearDown')) {
                    $testInstance->tearDown();
                }

                echo "✅ $methodName\n";
                $passedTests++;

            } catch (Exception $e) {
                echo "❌ $methodName: " . $e->getMessage() . "\n";
                $failedTests++;
            }
        }

    } catch (Exception $e) {
        echo "❌ Failed to instantiate $testClass: " . $e->getMessage() . "\n";
        $failedTests++;
    }

    echo "\n";
}

echo "Test Summary:\n";
echo "=============\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";

if ($failedTests === 0) {
    echo "🎉 All tests passed!\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed\n";
    exit(1);
}