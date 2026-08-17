# FR-PA-001-001 — Tags Tab Syncs Checkboxes on Save

## Status

Fixed

## Requirement

When the user clicks Save on the product edit form, the Tags tab shall call
`tagsDao->syncAssignments($stockId, $tagIds)` with the array of selected
tag IDs from `$_POST['product_tags']`.

## Source

- TagsTab::handleTagSave()
- Test: TagsTabTest::testHandleSaveDelegatesToTagSave

## Acceptance Criteria

1. `syncAssignments` is called exactly once per Save.
2. The tag ID array is `array_map('intval', $_POST['product_tags'])`.
3. If no tags are selected, an empty array is passed.
