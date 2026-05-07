# Future Enhancements

Features that are intentionally deferred — either because they belong in a
separate module, require FrontAccounting core changes, or need further
business analysis before implementation.

---

## Marketing / SEO Module

These belong in a dedicated marketing / storefront-integration module, not in
the product-attributes plugin:

- **SEO title** (meta title — differs from product name)
- **Meta description** (search-result snippet)
- **URL slug / permalink** (human-readable product URL)
- **Meta keywords**
- **Canonical URL** (deduplication across variation URLs)
- **Search-boost tags / keywords** (internal search scoring)
- **Product badge / label** (New, Hot, Sale) — storefront decoration
- **Product visibility** (catalog / search / hidden) — storefront-specific
- **Age-restriction flag** (alcohol, tobacco) — combined legal/marketing concern

---

## Purchase / Supplier Module

These depend on per-supplier context and belong with purchase-order logic:

- **Minimum Order Quantity (MOQ)** — may differ per supplier
- **Maximum Order Quantity** — per supplier or per customer segment
- **Order Quantity Increment / step** (sell in cases of 6, 12, 24, etc.)
- **Multiple supplier profiles** — each with their own part number, MOQ, lead
  time, and pricing (requires a `product_supplier_profiles` bridge table)
- **Lead time (days to ship)** — per supplier or per product
- **Safety stock level** — required buffer before re-order triggers
- **Re-order point** — stock level that triggers a purchase order
- **Preferred supplier** link

---

## Inventory Management Module

- **Inventory turnover statistics** — calculated from stock-move history;
  not stored as product attributes
- **Average days on hand** — derived metric
- **Expiry / best-before tracking flag** — pharma, food; requires lot-level
  stock-move extensions
- **Lot / batch tracking flag** — per product, triggers lot entry on movements
- **Serial-number tracking flag** — per product, triggers serial entry
- **Bin / shelf location** — may be warehouse-specific

---

## Pricing Module

- **Sale price / compare-at price** ("was / now") — FA has price lists but no
  per-product "was" price
- **Scheduled promotion dates** (sale_from / sale_until)
- **MSRP / RRP** — manufacturer suggested retail
- **Tiered / quantity-break pricing** — FA price lists partially cover this
- **Rental / subscription pricing flag and rates**
- **Currency-specific pricing overrides**
- **Customer-segment pricing** (wholesale vs retail vs staff)

---

## Digital Products Module

- **Is downloadable flag**
- **Download file URL(s)**
- **Download count limit**
- **Download expiry (days after purchase)**
- **License key generation template**

---

## FrontAccounting Core Changes Required

These features require changes to FA's `stock_master` table or core logic;
they cannot be fully implemented as a plugin overlay:

- **Multiple product categories** — `stock_master.category_id` is a single
  foreign key; a proper M:M bridge table and UI changes to FA's item screens
  would be needed. A workaround is to use the existing
  `product_attribute_category_assignments` to tag products with additional
  e-commerce categories, but that conflates attribute categories with catalog
  categories.
- **Bill-of-Materials type flag** (phantom / kit / manufactured) — BOM is
  already supported; a type flag per BOM level needs a schema change.
- **Routing / workcenter assignment** — requires manufacturing-module changes.
- **Product document attachments** — FA has no file-storage layer; would need
  a file-upload infrastructure or external storage integration.
- **Warranty period / type** — requires a warranty-contract module.

---

## Storefront / E-Commerce Integration

These make most sense as part of a dedicated WooCommerce-sync or headless
storefront connector:

- **Product video URL / 3D model URL** (AR preview)
- **Upsell / cross-sell / related product links**
- **Sort / display order** within a category listing
- **Bundle / kit product flag** and component list
- **Subscription plan ID** (links to external subscription service)
- **Customer-group entitlements** (Salesforce-style price books per segment)

---

*Last updated: May 2026*
