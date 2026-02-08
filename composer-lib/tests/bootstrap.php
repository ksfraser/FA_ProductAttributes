<?php

// Load Composer autoloader first (only if not already loaded)
if (!class_exists('ComposerAutoloaderInitd0ab8830ae0a5f8634b8c8ebafe07e51')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load FA function mocks from FAMock library
if (file_exists(__DIR__ . '/../vendor/ksfraser/famock/php/FAMock.php')) {
    require_once __DIR__ . '/../vendor/ksfraser/famock/php/FAMock.php';
} else {
    // Fallback to local FAMock for development
    require_once __DIR__ . '/FAMock.php';
}

// Simple autoload for testing
$files = [
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/DbAdapterInterface.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/FrontAccountingDbAdapter.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/PdoDbAdapter.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Dao/ProductAttributesDao.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/ProductAttributesService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/BulkOperationsService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Handler/ProductAttributesHandler.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/ActionHandler.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/AddAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/DeleteAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/AddCategoryAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/RemoveCategoryAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/UpsertCategoryAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/UpsertValueAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/DeleteCategoryAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/DeleteValueAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/GenerateVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/UpdateCategoryAssignmentsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/CreateChildAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/UpdateProductTypesAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Install/ComposerInstaller.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ProductAttributesUI.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ProductTypesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Integration/ItemsIntegration.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}