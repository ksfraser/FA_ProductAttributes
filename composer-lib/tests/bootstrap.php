<?php

// Load FA function mocks first
require_once __DIR__ . '/FAMock.php';

// Simple autoload for testing
$files = [
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/DbAdapterInterface.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/FrontAccountingDbAdapter.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Db/PdoDbAdapter.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Schema/SchemaManager.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Dao/ProductAttributesDao.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/VariationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/FrontAccountingVariationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/RetroactiveApplicationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/BaseApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/CategoriesApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/ValuesApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/AssignmentsApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/ApiRouter.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/RoyalOrderHelper.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/ActionHandler.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/AddAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/DeleteAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/AddCategoryAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/RemoveCategoryAssignmentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/GenerateVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Install/ComposerInstaller.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}