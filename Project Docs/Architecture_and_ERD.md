# Architecture & Entity Relationship Diagram

## 1. System Overview

The Product Attributes system extends FrontAccounting 2.4's inventory module with structured product metadata. It is split into two packages:

| Package | Type | Namespace | Purpose |
|---------|------|-----------|---------|
| `FA_ProductAttributes_Core` | Composer library | `FrontAccounting\ProductAttributes\` | Business logic, persistence, domain services |
| `FA_ProductAttributes` | FA module | `FrontAccounting\ProductAttribute\{Tab}\` | FA hooks, UI tabs, actions, integration |

## 2. Package Architecture

### 2.1 FA_ProductAttributes_Core (Business Logic)

```
FA_ProductAttributes_Core/
├── src/
│   └── FrontAccounting/
│       └── ProductAttributes/
│           ├── Dao/                    # Persistence layer
│           │   ├── ProductAttributesDao.php
│           │   ├── ShippingAttributesDao.php
│           │   ├── ProductIdentifiersDao.php
│           │   ├── ProductLifecycleDao.php
│           │   ├── ProductMediaDao.php
│           │   ├── MediaAttachmentsDao.php
│           │   ├── ProductWarrantyDao.php
│           │   ├── LifecycleFlagDefsDao.php
│           │   ├── ProductTagsDao.php
│           │   └── VariationsDao.php
│           ├── Service/                # Business logic
│           │   ├── ProductAttributesService.php
│           │   ├── BulkOperationsService.php
│           │   ├── VariationsDashboardService.php
│           │   ├── VariationService.php
│           │   ├── PricingRulesService.php
│           │   ├── AttributeReportService.php
│           │   └── RetroactiveApplicationService.php
│           ├── Security/
│           │   └── AccessChecker.php
│           └── Exception/              # Domain exceptions
├── composer.json                       # No FA dependency
└── tests/
```

**Key design principles:**
- No dependency on FrontAccounting core
- No UI code, no `display_*()` calls
- All database access through DAOs with parameterized queries
- Services orchestrate DAOs and enforce business rules

### 2.2 FA_ProductAttributes (FA Module)

```
FA_ProductAttributes/
├── hooks.php                          # hooks_FA_ProductAttributes extends hooks
├── src/
│   └── FrontAccounting/
│       └── ProductAttribute/
│           ├── Attributes/            # Core EAV tab
│           │   ├── AttributesTab.php
│           │   └── Actions/
│           │       ├── AddAssignmentAction.php
│           │       ├── DeleteAssignmentAction.php
│           │       ├── UpsertCategoryAction.php
│           │       ├── UpsertValueAction.php
│           │       ├── DeleteCategoryAction.php
│           │       ├── DeleteValueAction.php
│           │       ├── AddCategoryAssignmentAction.php
│           │       ├── RemoveCategoryAssignmentAction.php
│           │       └── UpdateCategoryAssignmentsAction.php
│           ├── Shipping/              # Dimensions & weight tab
│           │   ├── ShippingTab.php
│           │   ├── ClonePanel.php
│           │   └── Actions/
│           │       └── UpsertShippingAttributesAction.php
│           ├── Identifiers/           # Brand/MPN/GTIN tab
│           │   ├── IdentifiersTab.php
│           │   ├── ClonePanel.php
│           │   └── Actions/
│           │       └── UpsertProductIdentifiersAction.php
│           ├── Lifecycle/             # Status & flags tab
│           │   ├── LifecycleTab.php
│           │   ├── ClonePanel.php
│           │   ├── FlagDefsPage.php   # Admin page for flag definitions
│           │   └── Actions/
│           │       ├── UpsertProductLifecycleAction.php
│           │       └── SetFlagDefsAction.php
│           ├── Media/                 # Local images tab
│           │   ├── MediaTab.php
│           │   └── Actions/
│           │       ├── UploadImageAction.php
│           │       └── DeleteMediaItemAction.php
│           ├── Urls/                  # External links tab
│           │   ├── UrlsTab.php
│           │   └── Actions/
│           │       ├── AddAttachmentAction.php
│           │       └── DeleteAttachmentAction.php
│           ├── Warranty/              # Warranty info tab
│           │   ├── WarrantyTab.php
│           │   └── Actions/
│           │       └── UpsertWarrantyAction.php
│           ├── Tags/                  # Product tags tab
│           │   ├── TagsTab.php
│           │   └── Actions/
│           │       ├── UpsertTagAction.php
│           │       ├── DeleteTagAction.php
│           │       ├── AddTagAssignmentAction.php
│           │       └── RemoveTagAssignmentAction.php
│           ├── Categories/            # Sub-categories tab
│           │   └── CategoriesTab.php
│           └── Variations/            # Product variations tab
│               ├── VariationsTab.php
│               ├── DashboardTab.php
│               ├── ButtonsPanel.php
│               ├── RelationshipTable.php
│               ├── ProductTypesTab.php
│               ├── RoyalOrderHelper.php
│               └── Actions/
│                   ├── GenerateVariationsAction.php
│                   ├── CreateChildAction.php
│                   ├── CreateMissingVariationsAction.php
│                   ├── AssignParentAction.php
│                   ├── MakeInactiveAction.php
│                   ├── ReactivateVariationsAction.php
│                   └── UpdateProductTypesAction.php
├── public/
│   ├── index.php                      # Standalone admin entry
│   └── lifecycle-flags.php            # Flag definitions admin
├── sql/
│   ├── install.sql                    # Schema creation
│   └── seed.sql                       # Royal Order seed data
├── composer.json                      # Requires fa-product-attributes-core
└── plugin-tests/
```

## 3. Entity Relationship Diagram

```
┌─────────────────────────┐       ┌──────────────────────────┐
│ 0_product_attribute_    │       │ 0_product_attribute_     │
│ categories              │       │ values                   │
├─────────────────────────┤       ├──────────────────────────┤
│ id          INT PK      │◄──┐   │ id          INT PK       │
│ code        VARCHAR(32) │   │   │ category_id INT FK ──────┤
│ label       VARCHAR(64) │   │   │ value       VARCHAR(255) │
│ sort_order  SMALLINT    │   │   │ sort_order  SMALLINT     │
│ active      TINYINT(1)  │   │   │ active      TINYINT(1)  │
└─────────────────────────┘   │   └──────────────────────────┘
                              │
┌─────────────────────────────┴──────────────────────────────────┐
│ 0_product_attribute_assignments                                 │
├────────────────────────────────────────────────────────────────┤
│ id              INT PK                                         │
│ stock_id        VARCHAR(32) FK → 0_stock_master.stock_id       │
│ category_id     INT FK → 0_product_attribute_categories.id     │
│ value_id        INT FK → 0_product_attribute_values.id         │
│ sort_order      SMALLINT                                       │
└────────────────────────────────────────────────────────────────┘

┌──────────────────────────────┐    ┌─────────────────────────────┐
│ 0_product_shipping_          │    │ 0_product_identifiers        │
│ attributes                   │    │                              │
├──────────────────────────────┤    ├─────────────────────────────┤
│ stock_id       VARCHAR(32) PK│    │ stock_id        VARCHAR(32) PK│
│ length         DECIMAL(10,3) │    │ brand           VARCHAR(128) │
│ width          DECIMAL(10,3) │    │ manufacturer    VARCHAR(128) │
│ height         DECIMAL(10,3) │    │ model_no        VARCHAR(128) │
│ dim_unit       VARCHAR(5)    │    │ mpn             VARCHAR(128) │
│ weight         DECIMAL(10,3) │    │ gtin            VARCHAR(128) │
│ weight_unit    VARCHAR(5)    │    │ ean             VARCHAR(128) │
│ is_hazardous   TINYINT(1)    │    │ upc             VARCHAR(128) │
│ is_fragile     TINYINT(1)    │    │ isbn            VARCHAR(128) │
│ is_stackable   TINYINT(1)    │    │ asin            VARCHAR(128) │
│ is_oversize    TINYINT(1)    │    │ internal_barcode VARCHAR(128)│
│ is_perishable  TINYINT(1)    │    │ supplier_part_no VARCHAR(128)│
│ hs_code        VARCHAR(20)   │    └─────────────────────────────┘
│ country_of_origin VARCHAR(2) │
│ declared_value DECIMAL(12,2) │
└──────────────────────────────┘

┌──────────────────────────────┐    ┌─────────────────────────────┐
│ 0_product_lifecycle          │    │ 0_product_lifecycle_         │
├──────────────────────────────┤    │ flag_defs                    │
│ stock_id    VARCHAR(32) PK   │    ├─────────────────────────────┤
│ status      ENUM(active,     │    │ id    INT PK                │
│   draft,discontinued,        │    │ code  VARCHAR(32)           │
│   archived)                  │    │ label VARCHAR(64)           │
│ available_from DATE          │    │ sort_order SMALLINT         │
│ discontinue_on DATE          │    │ active  TINYINT(1)          │
│ clearance_note VARCHAR(255)  │    └─────────────────────────────┘
└──────────────────────────────┘           │
                              ┌─────────────┘
                              │
┌─────────────────────────────┴──────────────────────────────────┐
│ 0_product_lifecycle_flag_assignments                             │
├────────────────────────────────────────────────────────────────┤
│ lifecycle_id  INT FK → 0_product_lifecycle.stock_id            │
│ flag_id       INT FK → 0_product_lifecycle_flag_defs.id        │
│ PRIMARY KEY (lifecycle_id, flag_id)                             │
└────────────────────────────────────────────────────────────────┘

┌──────────────────────────────┐    ┌─────────────────────────────┐
│ 0_product_media              │    │ 0_product_media_             │
├──────────────────────────────┤    │ attachments                  │
│ id          INT PK           │    ├─────────────────────────────┤
│ stock_id    VARCHAR(32)      │    │ id          INT PK          │
│ url         VARCHAR(2048)    │    │ stock_id    VARCHAR(32)     │
│ alt_text    VARCHAR(255)     │    │ url         VARCHAR(2048)   │
│ sort_order  SMALLINT         │    │ description VARCHAR(255)    │
│ media_type  ENUM(image,      │    │ created_date DATE           │
│   video,document)            │    └─────────────────────────────┘
│ is_primary  TINYINT(1)       │
│ download_url VARCHAR(2048)   │
└──────────────────────────────┘

┌──────────────────────────────┐
│ 0_product_warranty           │
├──────────────────────────────┤
│ stock_id                    VARCHAR(32) PK │
│ warranty_type               ENUM(none,manufacturer,extended,third_party,lifetime) │
│ manufacturer_duration       INT            │
│ manufacturer_duration_unit  ENUM(days,months,years) │
│ extended_duration           INT            │
│ extended_duration_unit      ENUM(days,months,years) │
│ third_party_duration        INT            │
│ third_party_duration_unit   ENUM(days,months,years) │
│ lifetime_notes              VARCHAR(255)   │
│ warranty_notes              TEXT           │
└──────────────────────────────┘

┌──────────────────────────────┐    ┌─────────────────────────────┐
│ 0_product_tags               │    │ 0_product_tag_assignments    │
├──────────────────────────────┤    ├─────────────────────────────┤
│ id       INT PK              │    │ tag_id    INT FK            │
│ name     VARCHAR(64)         │    │ stock_id  VARCHAR(32)       │
│ slug     VARCHAR(64)         │    │ PRIMARY KEY (tag_id, stock_id)│
│ sort_order SMALLINT          │    └─────────────────────────────┘
└──────────────────────────────┘

┌──────────────────────────────┐
│ 0_product_hierarchy          │
├──────────────────────────────┤
│ parent_stock_id  VARCHAR(32) │
│ child_stock_id   VARCHAR(32) │
│ sort_order       SMALLINT    │
│ PRIMARY KEY (parent_stock_id, child_stock_id) │
└──────────────────────────────┘

┌──────────────────────────────┐    ┌─────────────────────────────┐
│ 0_product_attribute_         │    │ 0_product_media_             │
│ category_assignments         │    │ variation_links              │
├──────────────────────────────┤    ├─────────────────────────────┤
│ stock_id     VARCHAR(32)     │    │ media_id          INT FK    │
│ category_id  INT FK          │    │ variation_stock_id VARCHAR(32)│
│ sort_order   SMALLINT        │    │ PRIMARY KEY (media_id,      │
│ PRIMARY KEY (stock_id, cat_id)│   │   variation_stock_id)        │
└──────────────────────────────┘    └─────────────────────────────┘
```

## 4. Data Flow

### 4.1 Item Save Flow (items.php → hooks)

```
User clicks "Update Item" in items.php
    │
    ├─── FA core calls update_item() / add_item()
    │    (updates 0_stock_master)
    │
    ├─── FA core calls hook_invoke_all('post_item_write', $_POST, $stock_id)
    │    │
    │    └─── hooks_FA_ProductAttributes::post_item_write()
    │         │
    │         ├── save_shipping_from_post()   → ShippingAttributesDao::upsert()
    │         ├── save_identifiers_from_post() → ProductIdentifiersDao::upsert()
    │         ├── save_lifecycle_from_post()   → ProductLifecycleDao::upsert()
    │         └── save_warranty_from_post()    → ProductWarrantyDao::upsert()
    │
    └─── Item edit complete
```

### 4.2 Item Delete Flow

```
User clicks "Delete Item" in items.php
    │
    ├─── FA core calls hook_invoke_all('pre_item_delete', $stock_id)
    │    │
    │    └─── hooks_FA_ProductAttributes::pre_item_delete()
    │         │
    │         ├── ProductAttributesHandler::handle_delete()
    │         ├── ShippingAttributesDao::delete()
    │         ├── ProductIdentifiersDao::delete()
    │         ├── ProductLifecycleDao::delete()
    │         ├── LifecycleFlagDefsDao::deleteAssignments()
    │         ├── ProductWarrantyDao::delete()
    │         ├── ProductMediaDao::deleteMedia() (all items)
    │         └── MediaAttachmentsDao::deleteByStockId()
    │
    ├─── FA core calls delete_item()
    │    (removes from 0_stock_master)
    │
    └─── Item deleted
```

### 4.3 Tab Render Flow

```
FA core calls hook_invoke_all('item_display_tab_headers', $tabs, $stock_id)
    │
    └─── hooks_FA_ProductAttributes::item_display_tab_headers()
         │
         ├── Add 'product_attributes' tab → AttributesTab
         ├── Add 'shipping_attributes' tab → ShippingTab
         ├── Add 'product_identifiers' tab → IdentifiersTab
         ├── Add 'product_lifecycle' tab → LifecycleTab
         ├── Add 'product_media' tab → MediaTab
         ├── Add 'product_urls' tab → UrlsTab
         ├── Add 'product_warranty' tab → WarrantyTab
         ├── Add 'product_tags' tab → TagsTab
         └── Add 'product_variations' tab → VariationsTab

FA core calls hook_invoke_all('item_display_tab_content', $stock_id, $selectedTab)
    │
    └─── hooks_FA_ProductAttributes::item_display_tab_content()
         │
         └── Routes to appropriate tab class based on $selectedTab
```

## 5. Module Dependencies

```
FA_ProductAttributes (FA module)
    │
    ├── requires: FA_ProductAttributes_Core (composer)
    │   │
    │   ├── FrontAccounting\ProductAttributes\Dao\*
    │   ├── FrontAccounting\ProductAttributes\Service\*
    │   └── FrontAccounting\ProductAttributes\Security\*
    │
    ├── requires: ksf-modules-dao (composer)
    │   └── FrontAccounting\ModulesDAO\Db\FrontAccountingDbAdapter
    │
    └── requires: FA 2.4 core
        ├── hook_invoke_all()
        ├── company_path()
        ├── item_img_name()
        └── display_notification()
```

## 6. Security Model

| Security Section | Value | Purpose |
|-----------------|-------|---------|
| `SS_PRODUCT_ATTRIBUTES` | `115 << 8` (29440) | Top-level section |
| `SA_PRODUCT_ATTRIBUTES` | `SS_PRODUCT_ATTRIBUTES \| 1` | Product attributes admin access |

All tab rendering and save operations check `user_check_access('SA_PRODUCT_ATTRIBUTES')` before proceeding.

## 7. Database Schema Summary

| Table | Records Per | Key Columns |
|-------|------------|-------------|
| `0_product_attribute_categories` | One per attribute type | code, label, sort_order, active |
| `0_product_attribute_values` | Many per category | category_id FK, value, sort_order |
| `0_product_attribute_assignments` | Many per product | stock_id, category_id FK, value_id FK |
| `0_product_attribute_category_assignments` | Many per product | stock_id, category_id FK |
| `0_product_shipping_attributes` | One per product | stock_id PK, dimensions, weight, hazmat flags |
| `0_product_identifiers` | One per product | stock_id PK, brand, MPN, GTIN, EAN, UPC, etc. |
| `0_product_lifecycle` | One per product | stock_id PK, status, dates |
| `0_product_lifecycle_flag_defs` | One per flag type | code, label, sort_order, active |
| `0_product_lifecycle_flag_assignments` | Many per product | lifecycle_id FK, flag_id FK |
| `0_product_media` | Many per product | stock_id, url, media_type, is_primary, sort_order |
| `0_product_media_attachments` | Many per product | stock_id, url, description |
| `0_product_warranty` | One per product | stock_id PK, warranty_type, durations |
| `0_product_tags` | One per tag | name, slug, sort_order |
| `0_product_tag_assignments` | Many per product | tag_id FK, stock_id |
| `0_product_hierarchy` | Many per parent | parent_stock_id, child_stock_id |
| `0_product_media_variation_links` | Many per media | media_id FK, variation_stock_id |
