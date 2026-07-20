# Test Plan

## 1. Test Strategy

| Level | Scope | Tool | Execution |
|-------|-------|------|-----------|
| Unit | Individual classes, DAOs, services | PHPUnit | CI pipeline |
| Integration | Hook registration, items.php wiring | PHPUnit | CI pipeline |
| UAT | Full item edit workflow in FA | Manual | ACPT instance |

## 2. Unit Test Inventory

### 2.1 Core Attributes

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `AddAssignmentActionTest.php` | 4 | Add category-value assignment |
| `AddCategoryAssignmentActionTest.php` | 3 | Add category to product |
| `DeleteAssignmentActionTest.php` | 3 | Remove assignment |
| `DeleteCategoryActionTest.php` | 3 | Delete/deactivate category |
| `DeleteValueActionTest.php` | 3 | Delete/deactivate value |
| `RemoveCategoryAssignmentActionTest.php` | 3 | Remove category from product |
| `UpdateCategoryAssignmentsActionTest.php` | 3 | Bulk sync categories |
| `UpsertCategoryActionTest.php` | 4 | Create/update category |
| `UpsertValueActionTest.php` | 4 | Create/update value |
| `ActionHandlerTest.php` | 5 | Route dispatch |

### 2.2 Shipping

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ShippingAttributesDaoTest.php` | 6 | CRUD operations |
| `UpsertShippingAttributesActionTest.php` | 4 | Validation + save |
| `ShippingAttributesTabTest.php` | 3 | Tab rendering |

### 2.3 Identifiers

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ProductIdentifiersDaoTest.php` | 6 | CRUD operations |
| `UpsertProductIdentifiersActionTest.php` | 4 | Validation + save |
| `ProductIdentifiersTabTest.php` | 3 | Tab rendering |

### 2.4 Lifecycle

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ProductLifecycleDaoTest.php` | 6 | CRUD + flag operations |
| `UpsertProductLifecycleActionTest.php` | 4 | Validation + save |
| `ProductLifecycleTabTest.php` | 3 | Tab rendering |

### 2.5 Media

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ProductMediaDaoTest.php` | 6 | CRUD + variation links |
| `MediaActionsTest.php` | 5 | Upload, delete, file handling |
| `ProductMediaTabTest.php` | 3 | Tab rendering |

### 2.6 Warranty

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ProductWarrantyDaoTest.php` | 4 | CRUD operations |
| `UpsertWarrantyActionTest.php` | 4 | Validation + save |
| `ProductWarrantyTabTest.php` | 3 | Tab rendering |

### 2.7 Tags

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ProductTagsDaoTest.php` | 5 | CRUD + assignments |
| `TagActionsTest.php` | 6 | Add/remove/delete tags |
| `ProductTagsTabTest.php` | 3 | Tab rendering |

### 2.8 Variations

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `VariationsDaoTest.php` | 6 | Parent-child, types |
| `VariationServiceTest.php` | 5 | Combinations, stock ID generation |
| `GenerateVariationsActionTest.php` | 4 | Full generation |
| `CreateChildActionTest.php` | 3 | Single child creation |
| `CreateMissingVariationsActionTest.php` | 3 | Incremental generation |
| `AssignParentActionTest.php` | 3 | Parent assignment |
| `MakeInactiveActionTest.php` | 3 | Deactivation cascade |
| `ReactivateVariationsActionTest.php` | 3 | Reactivation cascade |
| `UpdateProductTypesActionTest.php` | 3 | Type management |
| `PricingRulesServiceTest.php` | 5 | Pricing adjustments |
| `FrontAccountingVariationServiceTest.php` | 4 | FA-specific integration |
| `RetroactiveApplicationServiceTest.php` | 3 | Retroactive analysis |
| `VariationsButtonsPanelTest.php` | 2 | Button rendering |
| `VariationsIntegrationTest.php` | 3 | Hook integration |
| `RoyalOrderHelperTest.php` | 3 | Sort order logic |
| `ProductRelationshipTableTest.php` | 2 | Table rendering |
| `ProductTypesTabTest.php` | 2 | Tab rendering |

### 2.9 Infrastructure

| Test File | Test Count | Covers |
|-----------|-----------|--------|
| `ModuleHooksRegistrationTest.php` | 4 | hooks.php structure |
| `ItemsIntegrationTest.php` | 9 | items.php hook wiring |
| `PluginLoaderTest.php` | 3 | Plugin discovery |
| `ComposerInstallerTest.php` | 3 | Composer bootstrap |
| `SeedDataInstallerTest.php` | 3 | Royal Order seed data |
| `AccessCheckerTest.php` | 4 | Security checks |
| `BulkOperationsServiceTest.php` | 3 | Bulk operations framework |
| `VariationsDashboardServiceTest.php` | 3 | Dashboard pagination |
| `AttributeReportServiceTest.php` | 3 | Data integrity reporting |
| `PerformanceTest.php` | 3 | Query performance |
| `CompatibilityTest.php` | 2 | PHP version compat |

## 3. UAT Test Cases

### 3.1 Item Create with Attributes

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to Inventory → Items | Item list displays |
| 2 | Click "New Item" | Item edit form shows |
| 3 | Fill in item code, name, category | Form validates |
| 4 | Click Shipping tab | Empty shipping form displays |
| 5 | Enter dimensions (30×20×10 cm, 2.5 kg) | Fields accept values |
| 6 | Click "Save Shipping Attributes" | Success message |
| 7 | Click Identifiers tab | Empty identifiers form |
| 8 | Enter brand "Acme", UPC "1234567890123" | Fields accept values |
| 9 | Click "Save Identifiers" | Success message |
| 10 | Click "Insert New Item" | Item created with all attributes |

### 3.2 Item Edit with Attributes

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Select existing item from list | Item loads with tabs |
| 2 | Click Shipping tab | Previously saved data displays |
| 3 | Change weight to 3.0 kg | Field updates |
| 4 | Click "Save Shipping Attributes" | Updated message |
| 5 | Click Lifecycle tab | Empty lifecycle form |
| 6 | Select "Active" status | Radio button selects |
| 7 | Check "Featured" flag | Checkbox toggles |
| 8 | Click "Save Lifecycle" | Success message |
| 9 | Click "Update Item" | Item updated |

### 3.3 Media Upload

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Select item, click Media tab | Media tab displays |
| 2 | Verify primary image shows | Thumbnail displays (if exists) |
| 3 | Select JPEG file for upload | File input accepts |
| 4 | Enter alt text "Product photo" | Text field accepts |
| 5 | Click "Upload Image" | Redirect, image appears in list |
| 6 | Click "Delete" on uploaded image | Confirmation prompt |
| 7 | Confirm delete | Image removed from list and disk |

### 3.4 URL Attachments

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Select item, click URLs tab | URLs tab displays |
| 2 | Enter URL "https://youtube.com/watch?v=abc123" | URL field accepts |
| 3 | Enter description "Product demo video" | Description accepts |
| 4 | Click "Add Attachment" | Redirect, URL appears in list |
| 5 | Click "Delete" on attachment | Confirmation prompt |
| 6 | Confirm delete | Attachment removed |

### 3.5 Warranty

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Select item, click Warranty tab | Warranty tab displays |
| 2 | Select "Manufacturer Warranty" | Radio selects, duration fields enable |
| 3 | Enter 12 months | Duration accepts |
| 4 | Select "Extended Warranty" | Additional duration fields enable |
| 5 | Enter 24 months | Duration accepts |
| 6 | Enter warranty notes "Standard manufacturer coverage" | Textarea accepts |
| 7 | Click "Save Warranty" | Success message |

### 3.6 Item Delete Cascade

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Select item with attributes | Item loads |
| 2 | Click "Delete This Item" | Confirmation prompt |
| 3 | Confirm delete | Item deleted |
| 4 | Verify shipping record deleted | No orphaned data |
| 5 | Verify identifiers record deleted | No orphaned data |
| 6 | Verify lifecycle record deleted | No orphaned data |
| 7 | Verify media files deleted from disk | No orphaned files |

## 4. Regression Checklist

| Area | Check |
|------|-------|
| FA core items still save correctly | Create, edit, delete items |
| FA core pricing still works | Sales/purchase pricing |
| FA core stock movements still work | Adjustments, transfers |
| No FA core files modified | `diff` against clean FA install |
| Composer autoloading works | No class-not-found errors |
| UAT deployment works | Pull and activate without errors |
