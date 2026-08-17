# FR-PA-002-001 — Generated Variations Record Attribute Assignments

## Status

Fixed (GitHub issue #34)

## Requirement

For each generated variation, `GenerateVariationsAction::handle()` shall call
`recordVariationAssignments()` which inserts one
`product_attribute_assignments` row per attribute value in the combination,
linked to the parent via `parent_stock_id`.

## Source

- GenerateVariationsAction::recordVariationAssignments()
- Test: GenerateVariationsActionTest::testHandleRecordsParentAssignmentsForGeneratedVariations

## Acceptance Criteria

1. `dao->addAssignment()` is called once per attribute value in the
   combination.
2. The `$sortOrder` parameter increments from 1.
3. The `$parentStockId` parameter is the parent's stock_id.
4. Combinations are sorted by `sortCombinationByRoyalOrder()` before
   recording.
