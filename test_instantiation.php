<?php

echo "Starting test script\n";

// Simple test script to instantiate DAO and action classes
// This verifies that classes can be instantiated and methods called without syntax errors

require_once __DIR__ . '/composer-lib/vendor/autoload.php';

// Include FA mocks if needed
require_once __DIR__ . '/composer-lib/tests/FAMock.php';

// Load the classes manually like in bootstrap
$files = [
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Db/DbAdapterInterface.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Db/FrontAccountingDbAdapter.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Db/PdoDbAdapter.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Schema/SchemaManager.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Dao/ProductAttributesDao.php',
    __DIR__ . '/fa_product_attributes_variations/src/Ksfraser/FA_ProductAttributes_Variations/Dao/VariationsDao.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/ActionHandler.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/AddAssignmentAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/DeleteAssignmentAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/AddCategoryAssignmentAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/RemoveCategoryAssignmentAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/UpsertCategoryAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/UpsertValueAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/DeleteCategoryAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/DeleteValueAction.php',
    __DIR__ . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Actions/UpdateCategoryAssignmentsAction.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "Loaded $file\n";
    } else {
        echo "File not found: $file\n";
    }
}

echo "Files loaded\n";

// Mock DbAdapterInterface
$db = new class implements \Ksfraser\ModulesDAO\Db\DbAdapterInterface {
    public function getDialect(): string { return 'mysql'; }
    public function getTablePrefix(): string { return 'fa_'; }
    public function query(string $sql, array $params = []): array { return []; }
    public function execute(string $sql, array $params = []): void {}
    public function lastInsertId(): ?int { return 1; }
};

// Instantiate DAOs
$dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);
$variationsDao = new \Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao($db);

echo "DAOs instantiated successfully.\n";

// Call some DAO methods to check they exist
try {
    $dao->ensureSchema();
    echo "DAO ensureSchema() called.\n";
} catch (Exception $e) {
    echo "DAO ensureSchema() error: " . $e->getMessage() . "\n";
}

try {
    $categories = $dao->listCategories();
    echo "DAO listCategories() called, returned " . count($categories) . " items.\n";
} catch (Exception $e) {
    echo "DAO listCategories() error: " . $e->getMessage() . "\n";
}

// List of action classes with their required DAO
$actionClasses = [
    [\Ksfraser\FA_ProductAttributes\Actions\AddAssignmentAction::class, $dao],
    [\Ksfraser\FA_ProductAttributes\Actions\AddCategoryAssignmentAction::class, $dao],
    [\Ksfraser\FA_ProductAttributes\Actions\DeleteAssignmentAction::class, $dao],
    [\Ksfraser\FA_ProductAttributes\Actions\DeleteCategoryAction::class, $variationsDao],
    [\Ksfraser\FA_ProductAttributes\Actions\DeleteValueAction::class, $variationsDao],
    [\Ksfraser\FA_ProductAttributes\Actions\RemoveCategoryAssignmentAction::class, $dao],
    [\Ksfraser\FA_ProductAttributes\Actions\UpsertCategoryAction::class, $variationsDao],
    [\Ksfraser\FA_ProductAttributes\Actions\UpsertValueAction::class, $variationsDao],
    [\Ksfraser\FA_ProductAttributes\Actions\UpdateCategoryAssignmentsAction::class, $dao],
];

foreach ($actionClasses as $actionConfig) {
    $actionClass = $actionConfig[0];
    $requiredDao = $actionConfig[1];
    
    try {
        $action = new $actionClass($requiredDao);
        echo "Action $actionClass instantiated successfully.\n";

        // Call handle method with empty array to check it exists
        $result = $action->handle([]);
        echo "Action $actionClass handle() called, returned: '$result'\n";
    } catch (Exception $e) {
        echo "Action $actionClass error: " . $e->getMessage() . "\n";
    }
}

echo "Test script completed.\n";

?>