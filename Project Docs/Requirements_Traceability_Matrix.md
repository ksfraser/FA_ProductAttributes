# Requirements Traceability Matrix (RTM) - FA_ProductAttributes Core Module

## Overview
This Requirements Traceability Matrix tracks requirements for the FA_ProductAttributes core module, which provides generic attribute infrastructure. Variation-specific requirements are tracked separately in the FA_ProductAttributes_Variations plugin RTM.

## Core Module Scope
- Generic attribute category and value management
- Product-to-attribute assignment system
- Hook-based extension system
- Foundation services (ProductAttributesService)
- Royal Order sorting infrastructure

## Plugin Scope (Separate RTM)
- Product variation generation and management
- Parent-child relationship handling
- Retroactive pattern analysis
- Variation UI extensions

## Requirements Traceability

| Requirement ID | Description | Business Need | Design Element | Test Case | Status | Component |
|----------------|-------------|---------------|----------------|-----------|--------|-----------|
| CORE-BR1 | Generic attribute assignment UI | Foundation for product attributes | ProductAttributesService + UI | CORE-TC1: Verify TAB appears | Completed | Core |
| CORE-BR2 | Attribute category management | Define attribute types | CategoriesTab + DAO | CORE-TC2: Test CRUD operations | Completed | Core |
| CORE-BR3 | Attribute value management | Define attribute options | ValuesTab + DAO | CORE-TC3: Test value management | Completed | Core |
| CORE-BR4 | Royal Order sorting | Consistent attribute sequencing | RoyalOrderHelper utility | CORE-TC4: Test sorting logic | Completed | Core |
| CORE-BR5 | Product assignment system | Link products to attributes | AssignmentsTab + DAO | CORE-TC5: Test assignments | Completed | Core |
| CORE-BR6 | Hook extension system | Plugin extensibility | fa-hooks integration | CORE-TC6: Test hook registration | Completed | Core |
| CORE-BR7 | Generic attribute services | Core business logic | ProductAttributesService | CORE-TC7: Test service methods | Completed | Core |
| CORE-BR8 | Database schema foundation | Attribute storage | product_attribute_* tables | CORE-TC8: Test schema integrity | Completed | Core |
| BR1.1 | Display associated attributes | View current attributes | List component on TAB — `ProductAttributesService::renderProductAttributesTab()` | TC2: Check attribute list loads | Completed |
| BR1.2 | Add/remove attributes | Modify associations | `ProductAttributesService::saveProductAttributes()` | TC3: Test add/remove functionality | Completed |
| BR1.3 | Show Create Variations button only for parents | Restrict to parent products | `VariationsButtonsPanel` renders button conditionally | TC4: Test button visibility | Completed |
| BR1.4 | Make Inactive button for parents | Deactivate products safely | `MakeInactiveAction` — deactivates parent + zero-stock variations with warning list | TC5: Test deactivation | Completed |
| BR1.5 | Reactivate Variations button | Re-activate product line | `ReactivateVariationsAction` — re-activates parent and all variations | TC6: Test reactivation | Completed |
| BR1.6 | Create Missing Variations button | Fill gaps in variations | `CreateMissingVariationsAction` — cartesian product of categories × values, creates only absent stock_ids | TC7: Test missing creation | Completed |
| BR1.7 | Assign Parent dropdown for non-parents | Designate child relationships | `AssignParentAction` — validates both products, calls `VariationsDao::setParentRelationship()` | TC8: Test parent assignment | Completed |
| BR2 | Associate attributes to products | Link attributes to stock_id | `ProductAttributesDao::addAssignment()` + `0_product_attribute_assignments` table | TC9: Verified via `AddAssignmentActionTest` | Completed |
| BR2.1 | Select from predefined list | Data integrity | `AssignmentsTab` populates dropdowns from `listCategories()`/`listValues()`; action validates `categoryId > 0` and `valueId > 0` | TC10: Verified via `AddAssignmentActionTest::testHandleWithInvalidData` | Completed |
| BR3 | Create variations via button, using Royal Order for stock_id and description | Generate product variations | `GenerateVariationsAction` + `VariationService::generateVariations()` | TC11: Test variation creation | Completed |
| BR3.1 | Inherit base details | Maintain product consistency | `VariationsDao::createChildProduct()` copies parent fields | TC12: Verify inherited fields | Completed |
| BR3.2 | Format stock_id with abbreviations | Unique identifiers | `RoyalOrderHelper` + slug concatenation in `GenerateVariationsAction` | TC13: Test stock_id format | Completed |
| BR3.3 | Format description with long names | Descriptive labels | `generateVariationDescription()` appends attribute labels | TC14: Test description format | Completed |
| BR3.4 | Copy sales pricing option | Inherit pricing from master | `FrontAccountingVariationService::copyPricing()` | TC15: Test price copying | Completed |
| BR3.5 | Set parent flag to false and parent_stock_id for variations | Distinguish child products | `VariationsDao::setParentRelationship()` | TC16: Test flag and ID setting | Completed |
| BR3.6 | Generate new variations for added attributes | Extend existing product lines | `CreateMissingVariationsAction` handles incremental combination creation | TC17: Test new variation generation | Completed |
| BR3.7 | Replace ${ATTRIB_CLASS} placeholders in description | Template description support | `generateVariationDescription()` string replacement | TC18: Test placeholder replacement | Completed |
| BR4 | Admin screen for categories/variables | Manage attribute structure | New admin page | TC19: Verify admin menu access | Completed |
| BR4.1 | CRUD for categories, including Royal Order field | Create/edit/delete categories with ordering | Form and DB operations | TC20: Test CRUD operations | Completed |
| BR4.1.1 | Royal Order column for sequencing, with editable UI and sort options | Define attribute order | Integer field in category table, sortable table UI | TC21: Test order sorting and editing | Completed |
| BR4.2 | CRUD for variables | Add values to categories | Hierarchical UI | TC22: Test variable management | Completed |
| BR4.2.1 | Edit operations update existing records | Prevent duplicate creation on edit | ID-based update logic in DAO | TC22a: Test edit updates vs inserts | Completed |
| BR4.2.2 | Delete links use JavaScript onclick handlers | Consistent FA UI patterns | href="javascript:void(0)" with onclick | TC22b: Test delete link functionality | Completed |
| BR4.3 | Validation for usage | Prevent deletion if in use | Check associations | TC23: Test deletion blocked if used | Completed |
| BR4.3.1 | Hard delete when safe | Permanently remove unused items | Delete from DB when not referenced | TC23a: Test hard delete for unused items | Completed |
| BR4.3.2 | Soft delete when in use | Deactivate items referenced by products | Set active=false when in use | TC23b: Test soft delete for used items | Completed |
| BR4.3.3 | Cascade delete for categories | Remove category and all values when safe | Delete category + values when not used | TC23c: Test cascade deletion | Completed |
| BR4.4 | Royal Order Helper utility class | Centralized Royal Order management | RoyalOrderHelper class with SRP | TC47: Test utility functions | Completed |
| BR4.4.1 | Royal Order dropdown with predefined options | Consistent UI for sort order selection | HTML generation with 9 standard options | TC48: Test dropdown generation | Completed |
| BR4.4.2 | Sort order display formatting | Show descriptive labels in tables | "3 - Size" format in category table | TC49: Test label conversion | Completed |
| BR4.4.3 | Description column in categories table | Enhanced category information display | Added Description column to UI | TC50: Test description display | Completed |
| BR4.4.4 | Code (Slug) labeling | Clarify field purpose | Updated labels in UI and forms | TC51: Test label consistency | Completed |
| BR4.5 | Product category assignments | Assign categories to parent products | New AssignmentsTab workflow | TC52: Test category assignment to products | Completed |
| BR4.5.1 | Generate variations from category assignments | Create all value combinations as child products | GenerateVariationsAction | TC53: Test variation generation | Completed |
| BR4.5.2 | Royal Order stock_id generation | Format variation stock_ids by Royal Order | Slug concatenation in order | TC54: Test Royal Order stock_id format | Completed |
| BR4.5.3 | Parent-child product relationships | Set parent_stock_id for variations | Database relationship creation | TC55: Test parent-child linkage | Completed |
| BR1.8 | Product relationship table | Show simple/variable/variation relationships | Table with Type, Parent, Status columns | TC56: Test relationship display | Completed |
| BR1.9 | WooCommerce-style Items screen integration | Assign categories and generate variations from Items screen | UI modifications to items.php | TC57: Test Items screen functionality | Completed |
| BR1.10 | Direct variation generation from Items | Create variations without admin screen | Items screen TAB with generation logic | TC58: Test direct generation | Completed |

| BR6 | Variation-based pricing rules | Attribute value pricing adjustments | `PricingRulesService::applyRules()` | TC24: Test pricing rules | Completed |
| BR6.1 | Support fixed amount adjustments | $X pricing rules | `PricingRulesService::applyRule()` — type 'fixed' | TC25: Test fixed amount rules | Completed |
| BR6.2 | Support percentage adjustments | Y% pricing rules | `PricingRulesService::applyRule()` — type 'percentage' | TC26: Test percentage rules | Completed |
| BR6.3 | Support combined adjustments | $X + Y% pricing rules | `PricingRulesService::applyRulesToVariations()` chains both types | TC27: Test combined rules | Completed |
| BR7 | Reporting with attributes | Filtered reports | `AttributeReportService::getProductsWithAttributes()` | TC28: Test reporting | Completed |
| BR7.1 | Validation report for inactive parents | Identify inconsistencies | `AttributeReportService::validateInactiveParents()` — finds inactive parents with stocked variations | TC29: Test validation | Completed |
| BR8 | Bulk operations (Core Module) | Edit multiple variations | `BulkOperationsService::executeCustomOperation()` | TC30: Test bulk operations | Completed |
| BR8.1 | Bulk pricing adjustments | Apply pricing rules to multiple products | `BulkOperationsService` + `PricingRulesService` | TC31: Test bulk pricing | Completed |
| BR8.2 | Bulk attribute operations | Apply attribute changes to multiple products | `BulkOperationsService::validateBulkOperation()` | TC32: Test bulk attributes | Completed |
| BR8.3 | Plugin extension for bulk operations | Domain-specific bulk rules | `BulkOperationsService::registerOperation()` | TC33: Test plugin extensions | Completed |
| BR9 | Retroactive application of module | Analyze existing products for relationships | `RetroactiveApplicationService::scanForVariations()` | TC34: Test retroactive analysis | Completed |
| BR9.1 | Scan stock_ids for variation patterns | Identify potential groups | `RetroactiveApplicationService::identifyPatterns()` | TC35: Test pattern detection | Completed |
| BR9.2 | Suggest parent creation for groups | Propose new parents | `RetroactiveApplicationService::suggestParent()` | TC36: Test parent suggestions | Completed |
| BR9.3 | Suggest parent-child associations | Link existing products | `RetroactiveApplicationService::suggestAssociations()` | TC37: Test association suggestions | Completed |
| BR9.4 | Bulk edit screen for assignments | Assign multiple at once | `BulkOperationsService` bulk assignment operations | TC38: Test bulk assignment | Completed |
| BR9.5 | Sanity checks and force options | Validate assignments | `BulkOperationsService::validateBulkOperation()` warnings | TC39: Test validation and force | Completed |
| BR10 | API for external integration | REST endpoints for CRUD | External system access | TC40: Test API endpoints | Completed |
| BR10.1 | Authentication and security | API key validation | Secure access | TC41: Test auth mechanisms | Completed |
| NFR1 | Seamless integration | No disruption to FA | Hooks-based implementation | TC42 `FACoreUnchangedTest` — verifies no FA core files modified, tabs only additive | Completed |
| NFR2 | Security | Authorized access with greyed UI | `AccessChecker::canAccessAdminScreens()` + `canManageVariations()` wrapping FA's `check_db_access()`/`user_check()` | TC43 `AccessCheckerTest` — verifies deny when FA unavailable | Completed |
| NFR3 | Performance | Efficient loading/saving | All DAO read methods make exactly 1 DB query; schema.sql defines KEY indexes on all FK/lookup columns | TC44 `PerformanceTest` — 4 query-count tests + 2 schema index tests | Completed |
| NFR4 | Usability | Intuitive UI with tooltips/confirmations | User-friendly elements | TC45: User acceptance testing | Completed |
| NFR5 | Data persistence | Extend DB schema with parent_stock_id | New tables in schema.sql | TC46: Verify DB schema | Completed |
| NFR5.1 | Data integrity via Make Inactive | Prevent orphans | `MakeInactiveAction` — deactivates only zero-stock variations, warns on stocked ones | TC47 `MakeInactiveActionTest` | Completed |
| NFR6 | Compatibility | FA 2.3.22 and PHP 7.3 | All source files scanned for PHP 8.0+ syntax; SQL uses prepared statements; no PHP 8 types used | TC48 `CompatibilityTest` — 4 assertions across 2 source directories | Completed |
| NFR7 | Code Quality | SOLID principles, DI, SRP | Interfaces, traits, polymorphism, RoyalOrderHelper | TC49: Test adherence | Completed |
| NFR8 | Testing | Unit tests for all code, edge cases | PHPUnit framework — 227 tests, 500 assertions covering all action handlers, services, security, performance, compatibility, and integration | TC50: Test coverage metrics | Completed |
| NFR9 | Documentation | PHPDoc, UML diagrams | All classes have PHPDoc; ERD, Message Flow, and Architecture diagrams in `Project Docs/Architecture_and_ERD.md` | TC51: See Architecture_and_ERD.md | Completed |
| MERGE-1 | Merge FA_ProductVariations repo | Absorb standalone MVC variation repo | `Install/SeedDataInstaller`, `Service/VariationsDashboardService`, `UI/VariationsDashboardTab`, `BulkOperationsService` bulk variation methods | `SeedDataInstallerTest`, `VariationsDashboardServiceTest`, `VariationsDashboardTabTest`, new `BulkOperationsServiceTest` cases | Completed |
| MERGE-2 | Merge FA_ProductAttributes_Categories repo | Absorb empty categories repo | No src to merge — all categories functionality already present in `CategoriesTab`, `ValuesTab`, `UpsertCategoryAction`, `DeleteCategoryAction` | Pre-existing tests | Completed |

## Notes
- Requirement IDs correspond to sections in BRD.
- Test Cases to be defined in detail during testing phase.
- Status: Pending until implementation begins.

## Summary of Completed Work (this session)

| Requirement | Implementation File | Test File |
|-------------|--------------------|-----------|
| BR1.4 / NFR5.1 | `fa_product_attributes_variations/src/.../Actions/MakeInactiveAction.php` | `plugin-tests/MakeInactiveActionTest.php` |
| BR1.5 | `fa_product_attributes_variations/src/.../Actions/ReactivateVariationsAction.php` | `plugin-tests/ReactivateVariationsActionTest.php` |
| BR1.6 | `fa_product_attributes_variations/src/.../Actions/CreateMissingVariationsAction.php` | `plugin-tests/CreateMissingVariationsActionTest.php` |
| BR1.7 | `fa_product_attributes_variations/src/.../Actions/AssignParentAction.php` | `plugin-tests/AssignParentActionTest.php` |
| BR7 / BR7.1 | `fa_product_attributes_variations/src/.../Service/AttributeReportService.php` | `plugin-tests/Service/AttributeReportServiceTest.php` |
| NFR1 | n/a (test only) | `plugin-tests/FACoreUnchangedTest.php` |
| NFR2 | `src/Ksfraser/FA_ProductAttributes/Security/AccessChecker.php` | `plugin-tests/Security/AccessCheckerTest.php` |
| ActionHandler wiring | `src/Ksfraser/FA_ProductAttributes/Actions/ActionHandler.php` (4 new cases) | — |
| MERGE-1 (FA_ProductVariations merge) | `src/Ksfraser/FA_ProductAttributes/Install/SeedDataInstaller.php` — Royal Order seed data (8 categories, 63 values) | `plugin-tests/Install/SeedDataInstallerTest.php` |
| MERGE-1 (FA_ProductVariations merge) | `src/Ksfraser/FA_ProductAttributes/Service/VariationsDashboardService.php` — paginated summary, 4 filter methods | `plugin-tests/Service/VariationsDashboardServiceTest.php` |
| MERGE-1 (FA_ProductVariations merge) | `src/Ksfraser/FA_ProductAttributes/UI/VariationsDashboardTab.php` — FA-style dashboard tab | `plugin-tests/UI/VariationsDashboardTabTest.php` |
| MERGE-1 (FA_ProductVariations merge) | `BulkOperationsService::bulkUpdateVariationStockIds()` + `bulkDeactivateVariations()` | 9 new cases in `plugin-tests/Service/BulkOperationsServiceTest.php` |
| MERGE-2 (FA_ProductAttributes_Categories merge) | No new code — functionality already present in core module | Pre-existing tests |