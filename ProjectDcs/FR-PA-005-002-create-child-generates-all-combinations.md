# FR-PA-005-002 — CreateChild Generates All Combinations

## Status

Fixed (GitHub issue #34)

## Requirement

CreateChildAction shall compute the cartesian product of all assigned
category values and create one child product per combination, with
deterministic stock IDs based on value slugs.

## Source

- CreateChildAction::handle()
- Test: CreateChildActionTest::testHandleCreatesAllCombinations

## Acceptance Criteria

1. For each category assignment, fetch all values via listValues().
2. Compute cartesian product across all categories.
3. For each combination, build stock ID as `parent-slug1-slug2-...`.
4. Skip combinations that already exist in stock_master.
5. For each new combination: createChildProduct(), copyParentCategoryAssignments(),
   setParentRelationship(), recordAssignments().
6. Return a message with the count of created products.
