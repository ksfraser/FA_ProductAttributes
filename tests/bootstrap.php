<?php

// Load Composer autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

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
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Dao/ShippingAttributesDao.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/ProductAttributesService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/BulkOperationsService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Service/VariationsDashboardService.php',
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
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/UpsertShippingAttributesAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Actions/CloneShippingToVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Install/ComposerInstaller.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Install/SeedDataInstaller.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Integration/ItemsIntegration.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Plugin/PluginLoader.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ProductAttributesUI.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ProductTypesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/CategoriesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ValuesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/AssignmentsTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/TabDispatcher.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/VariationsDashboardTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Debug/DebugCompany.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Debug/DebugConnection.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Debug/DebugSchemaNames.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Debug/DebugTBPref.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Debug/DisplaySql.php',
    // REST API classes
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/BaseApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/CategoriesApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/ValuesApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/AssignmentsApiController.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Api/ApiRouter.php',
    // Variations classes (merged into main src/Variations/)
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Dao/VariationsDao.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Handler/VariationsHandler.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Integration/VariationsIntegration.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/AssignParentAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/CreateChildAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/CreateMissingVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/GenerateVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/MakeInactiveAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/ReactivateVariationsAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Actions/UpdateProductTypesAction.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Service/AttributeReportService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Service/PricingRulesService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Service/RetroactiveApplicationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Service/VariationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/Service/FrontAccountingVariationService.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/UI/ProductRelationshipTable.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/UI/ProductTypesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/UI/RoyalOrderHelper.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Variations/UI/VariationsButtonsPanel.php',
    // Security / access control (NFR2)
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Security/AccessChecker.php',
    // Controller classes (merged from FA_ProductAttributes_Core)
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/Controller/ProductAttributesTabController.php',
    // Additional UI classes (merged from FA_ProductAttributes_Core)
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ProductAttributesTabUI.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ShippingAttributesTab.php',
    __DIR__ . '/../src/Ksfraser/FA_ProductAttributes/UI/ShippingClonePanel.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}