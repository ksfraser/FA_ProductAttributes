# Business Requirements Document

## 1. Executive Summary

The Product Attributes module extends FrontAccounting 2.4's inventory management with structured product metadata, enabling richer product information for e-commerce exports (WooCommerce, Square), catalogue generation, and internal inventory management.

## 2. Business Objectives

| ID | Objective | Priority |
|----|-----------|----------|
| BO-1 | Support structured product metadata beyond FA's flat `description` field | High |
| BO-2 | Enable e-commerce integration with WooCommerce and Square by providing complete product data (dimensions, weight, brand, identifiers) | High |
| BO-3 | Support product lifecycle management (active/draft/discontinued/archived) with configurable storefront flags | High |
| BO-4 | Manage product media (images, videos) for e-commerce catalogues | Medium |
| BO-5 | Track warranty information per product for customer service | Medium |
| BO-6 | Support product variations (size × color combinations) for WooCommerce | Medium |
| BO-7 | Maintain Royal Order of Adjectives for consistent attribute display | Low |

## 3. Business Rules

### 3.1 Core Attributes (EAV)

| ID | Rule |
|----|------|
| BR-1.1 | Each product may have zero or more attribute categories assigned |
| BR-1.2 | Each attribute category contains zero or more values |
| BR-1.3 | A product is linked to a category-value pair via assignments |
| BR-1.4 | Assignments have a sort order for display sequence |
| BR-1.5 | Categories can be soft-deactivated (not deleted) |
| BR-1.6 | Values can be soft-deactivated (not deleted) |
| BR-1.7 | Duplicate values within a category are prevented |

### 3.2 Shipping Attributes

| ID | Rule |
|----|------|
| BR-2.1 | Each product has at most one shipping attributes record |
| BR-2.2 | Dimensions support cm and in units |
| BR-2.3 | Weight supports kg, lb, g, oz units |
| BR-2.4 | Hazardous goods flag is boolean |
| BR-2.5 | HS Code and country of origin support international trade |

### 3.3 Product Identifiers

| ID | Rule |
|----|------|
| BR-3.1 | Each product has at most one identifiers record |
| BR-3.2 | Brand is a free-text field (not a FK to categories) |
| BR-3.3 | GTIN, EAN, UPC, ISBN, ASIN are independent fields |
| BR-3.4 | MPN (Manufacturer Part Number) is separate from internal barcode |

### 3.4 Lifecycle

| ID | Rule |
|----|------|
| BR-4.1 | Each product has exactly one lifecycle record |
| BR-4.2 | Status must be one of: active, draft, discontinued, archived |
| BR-4.3 | Storefront flags are admin-configurable definitions |
| BR-4.4 | A product can have zero or more flags assigned |
| BR-4.5 | Available-from and discontinue-on dates form an availability window |
| BR-4.6 | Clearance note is free-text for discount/markdown messaging |

### 3.5 Media

| ID | Rule |
|----|------|
| BR-5.1 | One primary image exists per product in FA's filesystem |
| BR-5.2 | Additional images are stored in the company images directory |
| BR-5.3 | Additional images follow naming convention: `{stock_id}-{N}.{ext}` |
| BR-5.4 | Only JPEG, PNG, GIF formats are accepted |
| BR-5.5 | Media type is classified as image, video, or document |
| BR-5.6 | External URLs (YouTube, etc.) are stored separately from local files |
| BR-5.7 | Deleting an item deletes all its media files from disk |

### 3.6 Warranty

| ID | Rule |
|----|------|
| BR-6.1 | Warranty type must be one of: none, manufacturer, extended, third_party, lifetime |
| BR-6.2 | Each warranty type (except none/lifetime) has a duration and unit |
| BR-6.3 | Duration units are: days, months, years |
| BR-6.4 | Lifetime warranty has a notes field instead of duration |

### 3.7 Product Variations

| ID | Rule |
|----|------|
| BR-7.1 | A parent product can have multiple child variations |
| BR-7.2 | Variation combinations are generated from attribute assignments |
| BR-7.3 | Child stock IDs follow pattern: `{parent}-{attr1}-{attr2}` |
| BR-7.4 | Royal Order of Adjectives determines sort sequence |
| BR-7.5 | Pricing rules support fixed amount or percentage adjustments |
| BR-7.6 | Make Inactive deactivates parent and zero-stock variations |
| BR-7.7 | Reactivate restores parent and all existing variations |

### 3.8 Tags

| ID | Rule |
|----|------|
| BR-8.1 | Tags are global (not per-product) |
| BR-8.2 | A product can have zero or more tags |
| BR-8.3 | Tag assignment is idempotent |
| BR-8.4 | Deleting a tag removes all its assignments |

### 3.9 Categories

| ID | Rule |
|----|------|
| BR-9.1 | Products can have sub-category assignments |
| BR-9.2 | Category assignments are synced (add/remove in bulk) |

## 4. Integration Requirements

| ID | Requirement | Source System |
|----|-------------|---------------|
| IR-1 | Tab rendering in FA items.php | FrontAccounting 2.4 |
| IR-2 | Save/delete lifecycle via hooks | FrontAccounting 2.4 |
| IR-3 | Image export to Square | ksf_FA_Square |
| IR-4 | Product data export to WooCommerce | ksf_FA_Woocommerce |
| IR-5 | Attribute data in catalogue generation | ksf_generate_catalogue |
| IR-6 | Schema installation via module activation | FrontAccounting 2.4 |

## 5. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-1 | PHP 7.3+ compatibility | FrontAccounting 2.4 environment |
| NFR-2 | No direct stock_master alterations | Use xref tables only |
| NFR-3 | PSR-4 autoloading | Composer standard |
| NFR-4 | All DB queries parameterized | SQL injection prevention |
| NFR-5 | File upload size validation | Server configuration limit |
| NFR-6 | CSRF protection via FA's form system | `start_form()` / `hidden()` |
