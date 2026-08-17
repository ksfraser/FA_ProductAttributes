# BR-PA-002 — Product Variation Generation

## Status

Fixed (GitHub issue #34)

## Statement

When a user generates product variations from a parent product, each
variation shall record its attribute combination in
`product_attribute_assignments` linked back to the parent via
`parent_stock_id`. Additionally, parent shipping attributes shall be cloned
to each new variation.

## Rationale

Issue #34: Generated variations did not record their attribute combination
in `product_attribute_assignments`. The Variations tab and the variations
picker rely on this table to list and filter variations.

## Acceptance Criteria

1. Each generated variation shall have one `product_attribute_assignments`
   row per selected attribute value.
2. Each row links to the parent via `parent_stock_id`.
3. Assignments are sorted by the category's `sort_order` (royal order).
4. Parent shipping attributes are cloned when a `ShippingAttributesDao` is
   available.
