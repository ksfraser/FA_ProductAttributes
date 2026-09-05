# FR-PA-005-002 — Create Child Product Generates All Combinations From the Pool

## Status

Fixed (GitHub issue #34; revised for the 2-button design, #60)

## Requirement

The Variations tab "Create Child Product" action shall read the persisted
combination pool (`product_variation_combos`, written by "Generate
Combinations") and create one child product per not-yet-instantiated
combination, with deterministic stock IDs based on the combos' slug chains,
and apply the FULL PA-attribute clone to each raised child.

## Source

- Actions/CreateChildProductAction::handle()
- Variations/Dao/CombosDao.php (listCombos / markInstantiated / reconcile helpers)
- Test: CreateChildProductActionTest::testHandleCreatesUninstantiatedCombosAndStampsPool

## Acceptance Criteria

1. "Create Child Product" lists the parent's persisted combos via
   CombosDao::listCombos(); if none are saved, it instructs the user to run
   "Generate Combinations" first.
2. For each combo whose child_stock_id is unset (and whose slug_key is
   non-empty), it builds the child stock_id as `parent-slug1-slug2-...`.
3. It skips combos already instantiated (child_stock_id set) and skips any
   target stock_id that already exists in stock_master (adopting and stamping
   it rather than duplicating it).
4. Each new child is created through the native chokepoint
   (VariationsDao::createChildProduct → add_item) so it gains its item_codes
   row (invoice-selectable).
5. Each new child receives the full clone: copyParentCategoryAssignments,
   setParentRelationship (product_hierarchy + assignment parent link),
   setProductParent, recordAssignments (from the combo's persisted value_set),
   and cloneProductAttributes (identifiers / shipping / warranty / lifecycle
   flags / tags).
6. After creating each child it stamps the pool combo's child_stock_id
   (markInstantiated).
7. It reconciles this parent's children against the pool (see BR-PA-005
   Acceptance Criteria 6) and returns a message listing created / removed /
   inactivated / with-stock counts.