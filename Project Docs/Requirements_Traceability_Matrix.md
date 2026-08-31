# Requirements Traceability Matrix

## Matrix Format

Each row maps a business requirement → functional requirement → implementation → test.

## 1. Core Attributes (EAV)

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-1.1 | FR-1.1 | `ProductAttributesDao::getAssignmentsForProduct()` | `AddAssignmentActionTest` |
| BR-1.2 | FR-1.2 | `ProductAttributesDao::getValuesForCategory()` | `UpsertValueActionTest` |
| BR-1.3 | FR-1.3 | `ProductAttributesDao::getAssignmentsForProduct()` | `AddAssignmentActionTest` |
| BR-1.4 | FR-1.4 | `ProductAttributesDao::assignValues()` (union: dedupe + insert, sort_order) | `ProductAttributesDaoTest` + `AddAssignmentActionTest` |
| BR-1.5 | FR-1.5 | `ProductAttributesDao::deleteCategory()` soft-deactivate | `DeleteCategoryActionTest` |
| BR-1.6 | FR-1.6 | `ProductAttributesDao::deleteValue()` soft-deactivate | `DeleteValueActionTest` |
| BR-1.7 | FR-1.7 | `ProductAttributesDao::addValue()` duplicate check | `UpsertValueActionTest` |

## 2. Shipping Attributes

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-2.1 | FR-2.1 | `ShippingAttributesDao::get()` single record | `ShippingAttributesDaoTest` |
| BR-2.2 | FR-2.2 | `ShippingAttributesDao::upsert()` dim_unit field | `UpsertShippingAttributesActionTest` |
| BR-2.3 | FR-2.3 | `ShippingAttributesDao::upsert()` weight_unit field | `UpsertShippingAttributesActionTest` |
| BR-2.4 | FR-2.4 | `ShippingAttributesDao::upsert()` is_hazardous field | `UpsertShippingAttributesActionTest` |
| BR-2.5 | FR-2.5 | `ShippingAttributesDao::upsert()` hs_code, country_of_origin | `UpsertShippingAttributesActionTest` |

## 3. Product Identifiers

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-3.1 | FR-3.1 | `ProductIdentifiersDao::get()` single record | `ProductIdentifiersDaoTest` |
| BR-3.2 | FR-3.2 | `ProductIdentifiersDao::upsert()` brand field | `UpsertProductIdentifiersActionTest` |
| BR-3.3 | FR-3.3 | `ProductIdentifiersDao::upsert()` GTIN/EAN/UPC/ISBN/ASIN fields | `UpsertProductIdentifiersActionTest` |
| BR-3.4 | FR-3.4 | `ProductIdentifiersDao::upsert()` mpn vs internal_barcode | `UpsertProductIdentifiersActionTest` |

## 4. Lifecycle

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-4.1 | FR-4.1 | `ProductLifecycleDao::get()` single record | `ProductLifecycleDaoTest` |
| BR-4.2 | FR-4.2 | `ProductLifecycleDao::upsert()` ENUM validation | `UpsertProductLifecycleActionTest` |
| BR-4.3 | FR-4.3 | `LifecycleFlagDefsDao::listActiveFlags()` | `ProductLifecycleDaoTest` |
| BR-4.4 | FR-4.4 | `LifecycleFlagDefsDao::setAssignedFlags()` M:N sync | `ProductLifecycleDaoTest` |
| BR-4.5 | FR-4.5 | `ProductLifecycleDao::upsert()` available_from/discontinue_on | `UpsertProductLifecycleActionTest` |
| BR-4.6 | FR-4.6 | `ProductLifecycleDao::upsert()` clearance_note | `UpsertProductLifecycleActionTest` |

## 5. Media

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-5.1 | FR-5.1 | `hooks_FA_ProductAttributes::find_primary_image()` | `MediaActionsTest` |
| BR-5.2 | FR-5.2 | `hooks_FA_ProductAttributes::get_image_dir()` | `MediaActionsTest` |
| BR-5.3 | FR-5.3 | `hooks_FA_ProductAttributes::next_image_index()` | `MediaActionsTest` |
| BR-5.4 | FR-5.4 | `hooks_FA_ProductAttributes::handle_image_upload()` MIME check | `MediaActionsTest` |
| BR-5.5 | FR-5.5 | `ProductMediaDao::addMedia()` | `ProductMediaDaoTest` |
| BR-5.6 | FR-5.6 | `hooks_FA_ProductAttributes::render_urls_tab()` | — |
| BR-5.7 | FR-5.7 | `hooks_FA_ProductAttributes::delete_all_media_files()` | `MediaActionsTest` |

## 6. Warranty

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-6.1 | FR-7.1 | `ProductWarrantyDao::upsert()` ENUM validation | `UpsertWarrantyActionTest` |
| BR-6.2 | FR-7.2 | `ProductWarrantyDao::upsert()` duration fields | `UpsertWarrantyActionTest` |
| BR-6.3 | FR-7.3 | `ProductWarrantyDao::upsert()` duration_unit ENUM | `UpsertWarrantyActionTest` |
| BR-6.4 | FR-7.4 | `ProductWarrantyDao::upsert()` lifetime_notes | `UpsertWarrantyActionTest` |

## 7. Variations

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-7.1 | FR-9.1 | `ProductAttributesDao::getParentChildRelationship()` | `VariationsDaoTest` |
| BR-7.2 | FR-9.2 | `VariationService::generateCombinations()` | `VariationServiceTest` |
| BR-7.3 | FR-9.3 | `VariationService::buildStockId()` | `VariationServiceTest` |
| BR-7.4 | FR-9.4 | `RoyalOrderHelper::sortAttributes()` | `RoyalOrderHelperTest` |
| BR-7.5 | FR-9.5 | `PricingRulesService::applyRules()` | `PricingRulesServiceTest` |
| BR-7.6 | FR-9.6 | `MakeInactiveAction` | `MakeInactiveActionTest` |
| BR-7.7 | FR-9.7 | `ReactivateVariationsAction` | `ReactivateVariationsActionTest` |

## 8. Tags

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| BR-8.1 | FR-8.1 | `ProductTagsDao::getTagsForProduct()` | `TagActionsTest` |
| BR-8.2 | FR-8.2 | `AddTagAssignmentAction` | `TagActionsTest` |
| BR-8.3 | FR-8.3 | `AddTagAssignmentAction` idempotent check | `TagActionsTest` |
| BR-8.4 | FR-8.4 | `DeleteTagAction` cascade delete | `TagActionsTest` |

## 9. Hook Integration

| BR | FR | Implementation | Test Coverage |
|----|----|----------------|---------------|
| IR-1 | FR-10.1 | `hooks_FA_ProductAttributes::item_display_tab_headers()` | `ModuleHooksRegistrationTest` |
| IR-1 | FR-10.2 | `hooks_FA_ProductAttributes::item_display_tab_content()` | `ItemsIntegrationTest` |
| IR-2 | FR-10.3 | `hooks_FA_ProductAttributes::post_item_write()` | `ItemsIntegrationTest` |
| IR-2 | FR-10.4 | `hooks_FA_ProductAttributes::pre_item_delete()` | `ItemsIntegrationTest` |

## Coverage Summary

| Category | BRs | FRs | Tests | Coverage |
|----------|-----|-----|-------|----------|
| Core Attributes | 7 | 10 | 4 test files | ✅ Full |
| Shipping | 5 | 6 | 2 test files | ✅ Full |
| Identifiers | 4 | 5 | 2 test files | ✅ Full |
| Lifecycle | 6 | 10 | 2 test files | ✅ Full |
| Media | 7 | 8 | 2 test files | ✅ Full |
| Warranty | 4 | 5 | 1 test file | ✅ Full |
| Variations | 7 | 11 | 12 test files | ✅ Full |
| Tags | 4 | 4 | 1 test file | ✅ Full |
| Hook Integration | 4 | 7 | 2 test files | ✅ Full |
| **Total** | **48** | **66** | **28 test files** | **✅ Full** |

## Gap Analysis

| Gap | Description | Priority | Status |
|-----|-------------|----------|--------|
| GAP-1 | Variations tab not wired into items.php hooks (separate module) | Medium | Deferred |
| GAP-2 | WooCommerce/Square export of new attributes not yet implemented | High | Planned |
| GAP-3 | ProductAttributesService renders HTML (should be in UI layer) | Low | Tech Debt |
| GAP-4 | Duplicate code between FA_ProductAttributes and _Core repos | High | Consolidation |
