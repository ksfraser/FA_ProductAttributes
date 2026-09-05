# Functional Requirements Specification

## 1. Overview

This document specifies the functional behavior of the Product Attributes module for FrontAccounting 2.4, covering all UI tabs, data operations, and integration points.

## 2. Tab: Product Attributes (EAV)

### 2.1 Tab Display

| ID | Requirement |
|----|-------------|
| FR-1.1 | Display assigned attribute categories and values for the current product |
| FR-1.2 | Allow reordering of assignments via sort_order |
| FR-1.3 | Show parent product (if this product is a variation) |
| FR-1.4 | Allow assignment of new category-value pairs |
| FR-1.5 | Allow removal of existing assignments |

### 2.2 Standalone Admin

| ID | Requirement |
|----|-------------|
| FR-1.6 | Stock → Product Attributes menu item opens admin page |
| FR-1.7 | Admin page has 3 sub-tabs: Categories, Values, Assignments |
| FR-1.8 | Categories: create, edit, deactivate, sort |
| FR-1.9 | Values: create within category, edit, deactivate, sort |
| FR-1.10 | Assignments: bulk-update product-category-value mappings |
| FR-1.11 | Assignments sub-tab selection section shows stock item dropdown and Load only; no category dropdown |
| FR-1.12 | Add Assignment section: category dropdown, value checkboxes (multi-select), "Add All" checkbox, sort order |
| FR-1.13 | Add Assignment shows a brief Royal Order of Adjectives description (Quantity…Purpose) |
| FR-1.14 | Add Assignment skips already-assigned category-value pairs (idempotent multi-assign) |

## 3. Tab: Shipping

### 3.1 Display

| ID | Requirement |
|----|-------------|
| FR-2.1 | Show Package Dimensions fieldset: Length, Width, Height, Unit (cm/in) |
| FR-2.2 | Show Weight/Mass fieldset: Weight, Unit (kg/lb/g/oz) |
| FR-2.3 | Show Handling Requirements: checkboxes for hazardous, fragile, stackable, oversize, perishable |
| FR-2.4 | Show Customs/International Trade: HS Code, Country of Origin, Declared Value |

### 3.2 Save

| ID | Requirement |
|----|-------------|
| FR-2.5 | Upsert on save (insert if no record, update if exists) |
| FR-2.6 | Only changed fields are updated (whitelist-based) |

## 4. Tab: Identifiers

### 4.1 Display

| ID | Requirement |
|----|-------------|
| FR-3.1 | Show Brand & Manufacturer: Brand, Manufacturer, Model No. |
| FR-3.2 | Show Barcodes & Global Trade IDs: MPN, GTIN-14, EAN-13, UPC-A, ISBN-13, ASIN, Internal Barcode |
| FR-3.3 | Show Sourcing References: Supplier Part No. |

### 4.2 Save

| ID | Requirement |
|----|-------------|
| FR-3.4 | Upsert on save |
| FR-3.5 | Free-text fields (no FK validation for brand/manufacturer) |

## 5. Tab: Lifecycle

### 5.1 Display

| ID | Requirement |
|----|-------------|
| FR-4.1 | Show Status dropdown: Active, Draft, Discontinued, Archived |
| FR-4.2 | Show Storefront Flags checkboxes (dynamically loaded from flag_defs) |
| FR-4.3 | Show Availability Window: Available From, Discontinue On (date inputs) |
| FR-4.4 | Show Clearance Note text field |

### 5.2 Flag Definitions Admin

| ID | Requirement |
|----|-------------|
| FR-4.5 | Stock → Lifecycle Flags menu item opens admin page |
| FR-4.6 | Admin page allows create, edit, deactivate, reorder of flag definitions |
| FR-4.7 | Flag definitions are global (shared across all products) |
| FR-4.8 | When no definitions exist, fall back to hardcoded defaults |

### 5.3 Save

| ID | Requirement |
|----|-------------|
| FR-4.9 | Upsert lifecycle data |
| FR-4.10 | Sync flag assignments (replace all on save) |

## 6. Tab: Media

### 6.1 Primary Image Display

| ID | Requirement |
|----|-------------|
| FR-5.1 | Show primary image thumbnail from `{company_path}/images/{stock_id}.{jpg\|png\|gif}` |
| FR-5.2 | If no primary image, show "No primary image set" message |

### 6.2 Additional Images

| ID | Requirement |
|----|-------------|
| FR-5.3 | Show table of additional images with thumbnail, type, alt text, sort order |
| FR-5.4 | Upload form accepts JPEG, PNG, GIF files |
| FR-5.5 | Uploaded files saved as `{stock_id}-{N}.{ext}` in company images directory |
| FR-5.6 | MIME type validation via finfo (not just extension) |
| FR-5.7 | File size validation against `$SysPrefs->max_image_size` |
| FR-5.8 | Delete removes both DB record and file from disk |

## 7. Tab: URLs

### 7.1 Display

| ID | Requirement |
|----|-------------|
| FR-6.1 | Show table of URL attachments with link, description, date |
| FR-6.2 | Add form: URL (required), Description (optional) |
| FR-6.3 | Delete button with confirmation |

### 7.2 Save

| ID | Requirement |
|----|-------------|
| FR-6.4 | POST actions redirect to avoid re-submit |
| FR-6.5 | URL validation (must be valid URL format) |

## 8. Tab: Warranty

### 8.1 Display

| ID | Requirement |
|----|-------------|
| FR-7.1 | Show warranty type radio buttons: None, Manufacturer, Extended, Third-Party, Lifetime |
| FR-7.2 | Show duration + unit fields for Manufacturer, Extended, Third-Party |
| FR-7.3 | Show Lifetime Warranty Notes text field |
| FR-7.4 | Show Warranty Terms / General Notes textarea |

### 8.2 Save

| ID | Requirement |
|----|-------------|
| FR-7.5 | Upsert on save |

## 9. Tab: Tags

### 9.1 Display

| ID | Requirement |
|----|-------------|
| FR-8.1 | Show assigned tags for current product |
| FR-8.2 | Allow adding existing tags |
| FR-8.3 | Allow removing tag assignments |

### 9.2 Admin

| ID | Requirement |
|----|-------------|
| FR-8.4 | Create new global tags |
| FR-8.5 | Edit tag name and slug |
| FR-8.6 | Delete tag (cascades to assignments) |

## 10. Tab: Variations

### 10.1 Product Types

| ID | Requirement |
|----|-------------|
| FR-9.1 | Product type: Simple, Variable, Variation |
| FR-9.2 | Assign parent for variation products |

### 10.2 Variation Generation

#### 10.2.1 Generate Combinations (rename of "Generate Variations")

Separates the *definition* of the combination set from the *creation* of child products.
The combo pool (`product_variation_combos`) is persisted per parent and is **only
rebuilt on the explicit "Generate Combinations" action** — it is never auto-rewritten
when a parent's categories or values change.

**Two-button model (agreed).** The Variations tab exposes exactly two buttons / actions:
(1) **Generate Combinations** (`generate_combos` → `GenerateCombosAction`) persists the
cartesian combo pool; (2) **Create Child Product** (`create_child_product` →
`CreateChildProductAction`) instantiates the stored pool into child products and
applies the full PA clone. There is no third creation button; the legacy "create
individual child product" path was retired in favour of the pool-driven flow so that
every creation path performs the same full clone.

| ID | Requirement |
|----|-------------|
| FR-9.12 | Persist the cartesian combo pool per parent on explicit "Generate Combinations"; idempotent re-run (upsert by slug chain) |
| FR-9.13 | Create Child Product reconciles **only this parent's** children against the combo pool |
| FR-9.14 | Per-parent scoping — never delete/inactivate/discontinue a child of another parent or a top-level item |
| FR-9.15 | Post-action confirmation summarising delete / inactive / discontinued / new candidate counts after acting |

#### 10.2.2 Changes to existing FRs

| ID | Requirement |
|----|-------------|
| FR-9.3 | *Change:* "Generate all combinations" now **persists combos only** (does not create `stock_master` children) |
| FR-9.4 | *Change:* "Create only missing variations (incremental)" becomes **Create Child Product** — instantiates persisted combos into child products (full PA clone; inactive on history-less orphans; discontinued/blocked on stocked history) |
| FR-9.5 | *Retired:* The third "create a single child from a persisted combo" path is **removed** — all child creation now flows through the pool-driven Create Child Product action |

#### 10.2.3 Child reconciliation rules (Gen Child, per parent — FR-9.13/9.14)

| Orphan status (child not in combo pool) | Transaction history (GRN) | Stock on hand | Action |
|----|----|----|----|
| Orphan | None | Any | **Delete** stock_id |
| Orphan | Has history | None | **Set inactive** |
| Orphan | Has history | > 0 | **Leave active** + report as "with stock" (blocks further orders); planned follow-on: flag `stock_master.discontinued` so new SO/PO references are blocked (see 10.2.5 / FR-9.17) |
| New combo (no child yet) | n/a | n/a | **Create/clone** child + assignments |

Rule invariant: a stock_id with transaction history is **never deleted** — at best
inactive, or left active/discontinued while stock remains.

#### 10.2.4 Empty-value default for added categories

When a category is added to a parent, existing GRN-having children are mapped to a
default empty (`""`) value for that category so they remain valid. The empty value is
excluded from the stock_id slug chain (no stock_id rename/migration on category add).

Primary intent: **WooCommerce/Square option alignment.** When a new category is
inserted *mid Royal Order* into an existing active SKU's attribute chain, older active
SKUs (with stock and history) auto-map to the `""` option so that the exported option
DDLs line up correctly across all child products — most relevant for WooCommerce, where
a product-variation option set must be consistent; Square tolerates but does not require
this. The `""` mapping keeps every child's slug-chain position aligned without renaming
existing stock_ids.

| FR | Requirement |
|----|-------------|
| FR-9.16 | Adding a category assigns a default `""` value to existing children; `""` excluded from slug chain; ensures WooCommerce/Square option DDL alignment on export |

#### 10.2.5 Deferred inactivation on last-unit consumption (GAP-6)

When a **discontinued** child's stock is fully consumed (QOH reaches zero), it should
auto-flip to **inactive**. FA derives QOH from `stock_moves` (no denormalized QOH column)
and provides no module hook on stock movement, so a surgical core patch is required.

*Choke point (single core file):* `commit_transaction()` in
`includes/db/sql_functions.inc`. FA wraps every stock document (GRN, sales delivery,
credit, invoice, adjustment, transfer) in nestable `begin_transaction()` /
`commit_transaction()`. When `$transaction_level` drops to `0` (the outermost commit),
dispatch a `db_postcommit` hook to registered modules. Because this fires once per
committed document **after** all moves are committed, it is transaction-safe for
multi-move documents (unlike a per-`db_query`/per-`add_stock_move` hook, which would
evaluate QOH mid-transaction).

*Module-side "conversion module" (no core change):* the FA_ProductAttributes module
registers a `db_postcommit` handler. On each signal it scans only its **discontinued**
children (per parent, via `product_hierarchy`), computes QOH from `stock_moves`, and
flips `stock_master.inactive = 1` for those at `QOH <= 0` (`discontinued` may remain set
for history). This maps directly to the `ksf_common_db` translation-layer pattern
("interface defines commands; a conversion layer maps to FA `db_*` calls and launches
table-scoped hooks", per AGENTS.md).

*Minimal divergence:* the core patch is a single guarded `hook_invoke_all('db_postcommit')`
call that is inert when no module registers for it — tiny, upgrade-tolerant, and
unobtrusive.

| FR | Requirement |
|----|-------------|
| FR-9.17 | Discontinued child auto-flips to inactive when QOH reaches zero via a single `db_postcommit` hook patched into `commit_transaction()` |

### 10.3 Bulk Operations

| ID | Requirement |
|----|-------------|
| FR-9.7 | Clone shipping/identifiers/lifecycle from parent to variations |
| FR-9.8 | Make inactive: parent + zero-stock variations |
| FR-9.9 | Reactivate: parent + all existing variations |

### 10.4 Dashboard

| ID | Requirement |
|----|-------------|
| FR-9.10 | Paginated table of all products with type, parent, status |
| FR-9.11 | Filter by type, parent, status |

## 11. Hook Integration

### 11.1 items.php Hooks

| ID | Hook | Purpose |
|----|------|---------|
| FR-10.1 | `item_display_tab_headers` | Register all tabs in item edit screen |
| FR-10.2 | `item_display_tab_content` | Render tab content when selected |
| FR-10.3 | `post_item_write` | Save attributes after item update/insert |
| FR-10.4 | `pre_item_delete` | Clean up attributes before item deletion |

### 11.2 Hook Behavior

| ID | Requirement |
|----|-------------|
| FR-10.5 | Custom tabs fall through to settings if no hook handles them |
| FR-10.6 | post_item_write receives full $_POST data and stock_id |
| FR-10.7 | pre_item_delete receives stock_id and cascades cleanup |

## 12. Schema Installation

| ID | Requirement |
|----|-------------|
| FR-11.1 | `install_extension()` creates all tables on module activation |
| FR-11.2 | Tables use `0_` prefix (FA convention) |
| FR-11.3 | Schema is idempotent (`CREATE TABLE IF NOT EXISTS`) |
| FR-11.4 | Seed data inserts Royal Order categories on first install |
