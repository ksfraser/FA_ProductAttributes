# BR-PA-005 — Variations Tab Batch Generation

## Status

Revised (2-button design, #60; supersedes the earlier "Generate Variations"
/ single-child "Create Child" description)

## Statement

The Variations tab exposes exactly **two** buttons/actions for generating
children from a parent product's assigned category/value combinations:

1. **Generate Combinations** (`generate_combos`) — computes the cartesian
   product of the parent's assigned category values and **persists** that
   set into the combo pool (`product_variation_combos`). It does NOT create
   any stock_master children.
2. **Create Child Product** (`create_child_product`) — reads the persisted
   combo pool, creates one stock_master child per not-yet-instantiated combo,
   and reconciles this parent's existing children against the pool.

## Rationale

Legacy behavior had three to four overlapping creation paths
(`generate_variations`, and at one point a single-child "Create Child" that
only created one product regardless of the combination count). This caused the
"silently does nothing" failures of issue #34 and divergent clone coverage
across paths. The design was converged to the two-action model so that:
- combination computation (the cartesian set) is stored once and explicit;
- child creation is deterministic and idempotent against the stored pool;
- creating/removing categories or options is reconciled safely (see
  Acceptance Criteria 6-8).

## Acceptance Criteria

1. "Generate Combinations" computes the cartesian product of all assigned
   category values and stores each combination in `product_variation_combos`
   (keyed by parent_stock_id, deduped by value_set_key). Running it shows a
   notification of how many combinations persist.
2. "Generate Combinations" is idempotent: combos already in the pool are left
   untouched; re-running after a category/value change only adds
   newly-produced combos (it never auto-rewrites the existing set).
3. "Create Child Product" reads the persisted pool and creates one child per
   not-yet-instantiated combo, skipping combos that already exist in
   stock_master, and stamps the combo's child_stock_id.
4. Each child created is registered through FA's native item save
   (`add_item`, via the `VariationsDao::createChildProduct` chokepoint) so it
   gains its item_codes row and is selectable in the Direct Invoice / sales
   item picker.
5. Each child receives the FULL PA-attribute clone: category assignments
   (`copyParentCategoryAssignments`), concrete value assignments
   (`product_attribute_assignments` linked to the parent via
   parent_stock_id, from the pool's `value_set`), and the "other PA
   attributes" — identifiers / shipping attributes / warranty / lifecycle
   flags / tag assignments (`cloneProductAttributes`).
6. Add/remove of a category or option (then re-running "Generate
   Combinations"): newly-produced combos are added; a child no longer
   represented in the pool is reconciled — no stock_moves history → **delete**
   (fully removed); history but zero on-hand → **deactivate** (`inactive = 1`);
   history with on-hand → **left active** but reported as "with stock"
   (blocked list). Nothing is auto-rewritten without an explicit re-run, and a
   child with transaction history is never deleted.
7. Both buttons show a notification statement with the results (created /
   added / removed / inactivated / blocked counts).
8. Creation is scoped to a parent product; a child (variation) product cannot
   itself have children generated (buttons hidden / rejected).

## Source

- Actions/GenerateCombosAction.php
- Actions/CreateChildProductAction.php
- Variations/Dao/CombosDao.php
- Variations/Dao/VariationsDao.php
- sql/31_product_variation_combos.sql
- plugin-tests/GenerateCombosActionTest.php
- plugin-tests/CreateChildProductActionTest.php
- plugin-tests/Variations/CombosDaoTest.php