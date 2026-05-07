# FA_ProductAttributes — Architecture & Data Model Documentation

> NFR9 compliance: PHPDoc, ERD, Message Flow, and architectural diagrams.

---

## 1. Entity-Relationship Diagram (ERD)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                        FA_ProductAttributes Schema                        │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────┐
│  0_product_attribute_categories │
│─────────────────────────────────│
│ PK  id            INT(11) AI    │
│     code          VARCHAR(64) UQ│
│     label         VARCHAR(64)   │
│     description   VARCHAR(255)  │
│     sort_order    INT(11)       │
│     active        TINYINT(1)   │
│     updated_ts    TIMESTAMP     │
└────────────────┬────────────────┘
                 │ 1
                 │
                 │ N
┌────────────────▼────────────────┐
│  0_product_attribute_values     │
│─────────────────────────────────│
│ PK  id            INT(11) AI    │
│ FK  category_id   INT(11)  ─────┼──► categories.id
│     value         VARCHAR(64)   │     (KEY idx_category)
│     slug          VARCHAR(32)   │     (UQ uq_category_slug)
│     sort_order    INT(11)       │
│     active        TINYINT(1)   │
│     updated_ts    TIMESTAMP     │
└─────────────────────────────────┘

              FA stock_master (external)
              ┌─────────────────────┐
              │  stock_id  PK/FK    │
              │  description        │
              │  inactive  TINYINT  │
              │  ...                │
              └──────┬──────────────┘
                     │ 1
           ┌─────────┼─────────┐
           │ N       │ N       │ N
           │         │         │
┌──────────▼──────┐  │  ┌──────▼──────────────────────────┐
│ 0_product_      │  │  │  0_product_attribute_assignments  │
│ attribute_      │  │  │──────────────────────────────────│
│ category_       │  │  │ PK  id            INT(11) AI      │
│ assignments     │  │  │     stock_id      VARCHAR(32)     │
│─────────────────│  │  │     category_id   INT(11)         │
│ PK id INT(11)   │  │  │     value_id      INT(11)         │
│    stock_id     │  │  │     parent_stock_id VARCHAR(32)   │
│    category_id  │  │  │     sort_order    INT(11)         │
│ UQ (stock,cat)  │  │  │ UQ (stock,cat,val)                │
│ KEY idx_stock   │  │  │ KEY idx_stock, idx_cat, idx_val   │
│ KEY idx_cat     │  │  │     idx_parent                    │
└─────────────────┘  │  └──────────────────────────────────┘
                     │
              ┌──────▼──────────────┐
              │  0_product_hierarchy │
              │─────────────────────│
              │ PK  id              │
              │     child_stock_id  │◄── UQ uq_child
              │     parent_stock_id │◄── KEY idx_parent
              │     updated_ts      │
              └─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                  Extended Product Attribute Tables (v0.6–v0.7)               │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────┐    ┌──────────────────────────────────┐
│  0_product_shipping_attributes   │    │  0_product_identifiers           │
│──────────────────────────────────│    │──────────────────────────────────│
│ PK  stock_id  VARCHAR(32)        │    │ PK  stock_id  VARCHAR(32)        │
│     weight_kg DECIMAL(10,3)      │    │     brand     VARCHAR(128)       │
│     length_cm DECIMAL(10,2)      │    │     manufacturer VARCHAR(128)    │
│     width_cm  DECIMAL(10,2)      │    │     mpn       VARCHAR(64)        │
│     height_cm DECIMAL(10,2)      │    │     gtin      VARCHAR(14)        │
│     is_fragile TINYINT(1)        │    │     ean       VARCHAR(13)        │
│     is_hazmat  TINYINT(1)        │    │     upc       VARCHAR(12)        │
│     ship_class VARCHAR(32)       │    │     isbn      VARCHAR(13)        │
│     ...                          │    │     asin      VARCHAR(10)        │
└──────────────────────────────────┘    │     internal_barcode VARCHAR(64) │
                                        │     supplier_part_no VARCHAR(64) │
                                        │     model_no  VARCHAR(64)        │
                                        └──────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│  0_product_lifecycle                                          │
│──────────────────────────────────────────────────────────────│
│ PK  stock_id       VARCHAR(32)                               │
│     status         ENUM(active,draft,discontinued,archived)  │
│     is_special_order   TINYINT(1)                            │
│     is_clearance       TINYINT(1)                            │
│     is_out_of_stock_notice TINYINT(1)                        │
│     is_new_arrival     TINYINT(1)                            │
│     is_bestseller      TINYINT(1)                            │
│     is_featured        TINYINT(1)                            │
│     is_seasonal        TINYINT(1)                            │
│     available_from     DATE                                  │
│     discontinue_on     DATE                                  │
│     clearance_note     VARCHAR(255)                          │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────┐    ┌────────────────────────────────────┐
│  0_product_tags              │    │  0_product_tag_assignments          │
│──────────────────────────────│    │────────────────────────────────────│
│ PK  id    INT(11) AI         │◄───┤ FK  tag_id   INT(11)               │
│     name  VARCHAR(128)       │    │ PK  stock_id VARCHAR(32) (compound)│
│     slug  VARCHAR(128) UQ    │    └────────────────────────────────────┘
└──────────────────────────────┘

┌──────────────────────────────────────────────┐
│  0_product_media                              │
│──────────────────────────────────────────────│
│ PK  id         INT(11) AI                    │
│     stock_id   VARCHAR(32)  KEY idx_stock    │
│     url        VARCHAR(1024)                 │
│     alt_text   VARCHAR(255)                  │
│     sort_order INT(11)                       │
│     media_type ENUM(image,video,document)    │
│     is_primary TINYINT(1)                    │
└──────────────────┬───────────────────────────┘
                   │ 1
                   │ N
┌──────────────────▼───────────────────────────┐
│  0_product_media_variation_links              │
│──────────────────────────────────────────────│
│ PK  media_id           INT(11) (compound)    │
│ PK  variation_stock_id VARCHAR(32) (compound)│
└──────────────────────────────────────────────┘
```

---

## 2. Class Architecture Diagram

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         FA_ProductAttributes Core                           │
│                                                                              │
│  ┌──────────────┐   ┌──────────────────────┐   ┌───────────────────────────┐  │
│  │ PluginLoader │──►│ ProductAttributesHndlr│──►│ ActionHandler             │  │
│  └──────────────┘   │ (fa-hooks callbacks)  │   │  ├ UpsertCategoryAction   │  │
│                     └──────────┬────────────┘   │  ├ DeleteCategoryAction   │  │
│                                │                │  ├ AddAssignmentAction    │  │
│                                │                │  ├ UpsertProductIdentifiers│  │
│                                ▼                │  ├ CloneIdentifiersToVars │  │
│  ┌──────────────────────────────────────────┐   │  ├ UpsertProductLifecycle │  │
│  │        ProductAttributesService          │   │  ├ CloneLifecycleToVars   │  │
│  │  renderProductAttributesTab()            │   │  ├ UpsertTag / DeleteTag  │  │
│  │  saveProductAttributes()                 │   │  ├ AddTagAssignment       │  │
│  └───────────────────┬──────────────────────┘   │  ├ RemoveTagAssignment    │  │
│                      │ uses                      │  ├ AddProductMedia        │  │
│            ┌─────────▼────────────┐             │  ├ DeleteProductMedia     │  │
│            │ DAOs                 │             │  └ SetMediaVariationLinks │  │
│            │ ProductAttributesDao │             └───────────────────────────┘  │
│            │ ShippingAttributesDao│                                             │
│            │ ProductIdentifiersDao│  ┌────────────────────────────────────┐    │
│            │ ProductLifecycleDao  │  │  UI Layer (TabDispatcher: 9 tabs)  │    │
│            │ ProductTagsDao       │  │  ├ CategoriesTab                   │    │
│            │ ProductMediaDao      │  │  ├ ValuesTab                       │    │
│            └─────────┬────────────┘  │  ├ AssignmentsTab                  │    │
│                      │               │  ├ ShippingAttributesTab           │    │
│            ┌─────────▼────────┐     │  ├ ShippingClonePanel              │    │
│            │ DbAdapterInterface│    │  ├ ProductIdentifiersTab            │    │
│            │  (PDO/FA bridge)  │    │  ├ IdentifiersClonePanel            │    │
│            └──────────────────┘     │  ├ ProductLifecycleTab              │    │
│                                      │  ├ LifecycleClonePanel              │    │
│            ┌─────────────────────┐   │  ├ ProductTagsTab                  │    │
│            │  REST API Layer      │  │  ├ ProductMediaTab                 │    │
│            │  ├ ApiRouter         │  │  └ ProductAttributesSummaryTab     │    │
│            │  ├ CategoriesApi     │  └────────────────────────────────────┘    │
│            │  ├ ValuesApi         │                                             │
│            │  └ AssignmentsApi    │                                             │
│            └─────────────────────┘                                             │
└────────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│               fa_product_attributes_variations Plugin                       │
│                                                                              │
│  ┌──────────────────┐   ┌────────────────────────────────────────────────┐ │
│  │ VariationsHandler│──►│ VariationsDao                                  │ │
│  │ (fa-hooks wire)  │   │  getProductVariations()  setParentRelationship()│ │
│  └──────────────────┘   │  createChildProduct()    getParentProductData() │ │
│                          └───────────────┬────────────────────────────────┘ │
│                                          │                                   │
│  ┌────────────────────────────────────┐  │  ┌───────────────────────────┐  │
│  │  Service Layer                     │  │  │  UI Layer                 │  │
│  │  ├ VariationService                │  │  │  ├ VariationsButtonsPanel  │  │
│  │  │   generateVariations()          │  │  │  └ ProductRelationshipTable│  │
│  │  ├ PricingRulesService             │  │  └───────────────────────────┘  │
│  │  │   applyRule/applyRules()        │  │                                  │
│  │  ├ BulkOperationsService           │  │  ┌───────────────────────────┐  │
│  │  ├ RetroactiveApplicationService   │  │  │  Reporting                │  │
│  │  └ AttributeReportService          │  │  │  AttributeReportService   │  │
│  │      getProductsWithAttributes()   │  │  └───────────────────────────┘  │
│  │      validateInactiveParents()     │  │                                  │
│  └────────────────────────────────────┘  │  ┌───────────────────────────┐  │
│                                          │  │  Security                 │  │
│  ┌────────────────────────────────────┐  │  │  AccessChecker            │  │
│  │  Variation Action Handlers         │  │  │  canAccessAdminScreens()  │  │
│  │  ├ GenerateVariationsAction        │  │  │  canManageVariations()    │  │
│  │  ├ MakeInactiveAction              │  │  └───────────────────────────┘  │
│  │  ├ ReactivateVariationsAction      │  │                                  │
│  │  ├ CreateMissingVariationsAction   │  │                                  │
│  │  └ AssignParentAction              │  │                                  │
│  └────────────────────────────────────┘  │                                  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Message Flow Diagram — "Generate Variations" Button

```
User (browser)
    │
    │  POST variation_action=generate_variations, stock_id=SHIRT
    ▼
VariationsHandler::handlePost()
    │
    │  dispatch(action, postData)
    ▼
ActionHandler::handle('generate_variations', postData)
    │
    │  new GenerateVariationsAction(productAttributesDao, dbAdapter)
    ▼
GenerateVariationsAction::handle(postData)
    │
    ├─► ProductAttributesDao::listCategoryAssignments(stock_id)
    │       └─ SELECT from 0_product_attribute_category_assignments
    │
    ├─► ProductAttributesDao::listValues(category_id)  [per category]
    │       └─ SELECT from 0_product_attribute_values
    │
    ├─► generateCombinations(categoryValues)  [cartesian product, local]
    │
    ├─► sortCombinationByRoyalOrder(combination)  [RoyalOrderHelper]
    │
    └─► [for each new combination]
            VariationsDao::createChildProduct(childId, parentData)
                └─ INSERT into 0_stock_master
            VariationsDao::setParentRelationship(childId, parentId)
                └─ INSERT into 0_product_hierarchy
    │
    │  return "Created N variations"
    ▼
ProductAttributesHandler  →  render flash message in FA items.php
```

---

## 4. Message Flow Diagram — "Make Inactive" Button

```
User (browser)
    │
    │  POST variation_action=make_inactive, stock_id=SHIRT
    ▼
ActionHandler::handle('make_inactive', postData)
    │
    ▼
MakeInactiveAction::handle(postData)
    │
    ├─► VariationsDao::getProductVariations(stock_id)
    │       └─ SELECT child_stock_id FROM 0_product_hierarchy
    │
    ├─► db::execute("UPDATE 0_stock_master SET inactive=1 WHERE stock_id=:parent")
    │
    └─► [for each variation]
            db::query("SELECT SUM(qty_on_hand) FROM 0_stock_moves WHERE stock_id=:var")
            if qty == 0:
                db::execute("UPDATE 0_stock_master SET inactive=1 WHERE stock_id=:var")
            else:
                add to warnings list
    │
    │  return "Parent SHIRT deactivated. 3 variation(s) deactivated.
    │          WARNING: SHIRT-RED has stock (skipped)"
    ▼
Flash message rendered
```

---

## 5. Data Flow — REST API

```
External Client
    │
    │  GET /api/v1/categories
    │  POST /api/v1/assignments
    ▼
ApiRouter::route(method, path, postData)
    │
    ├─ /categories  ──► CategoriesApiController::list() / create() / update() / delete()
    ├─ /values      ──► ValuesApiController
    └─ /assignments ──► AssignmentsApiController
                            │
                            ▼
                    ProductAttributesDao (shared with core plugin)
                            │
                            ▼
                    DbAdapterInterface (PDO)
                            │
                            ▼
                    MySQL — FA database tables
```

---

## 6. Extension Points (Plugin Hook Architecture)

```
FrontAccounting Core
    │
    │  apply_filters('items_tabs', $tabs, $stockId)
    │  apply_filters('items_tab_content', $content, $stockId, $tab)
    │  do_action('items_save', $stockId, $postData)
    ▼
FA_ProductAttributes_Core / fa_hooks.php
    │  add_filter('items_tabs',        [ItemsIntegration, 'addTabHeaders'])
    │  add_filter('items_tab_content', [ItemsIntegration, 'getTabContent'])
    │  add_action('items_save',        [ProductAttributesHandler, 'save'])
    ▼
fa_product_attributes_variations / hooks.php
    │  add_filter('items_tabs',        [VariationsHandler, 'addTabHeaders'])
    │  add_action('items_save',        [VariationsHandler, 'handlePost'])
    │  (no FA core functions are overwritten — purely additive hooks)
```

---

## 7. Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| Hook-based integration (additive only) | Preserves FA core; no files modified (NFR1) |
| Prepared statements everywhere | SQL injection prevention + PDO/PHP 7.3 compat (NFR2, NFR6) |
| Indexes on all FK/lookup columns | Sub-2s load time for 10k products (NFR3) |
| Soft-delete (active flag) | Preserves referential integrity; prevents orphaned assignments |
| Royal Order sort_order | Consistent, predictable attribute ordering per BRD |
| VariationsDao wraps ProductAttributesDao | Variations plugin depends on core, not vice versa |
| AccessChecker wraps FA functions | Testable without live FA install (NFR2) |
