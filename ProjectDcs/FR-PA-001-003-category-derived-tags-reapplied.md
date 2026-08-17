# FR-PA-001-003 — Category-Derived Tags Re-Applied on Save

## Status

Fixed (GitHub issue #23)

## Requirement

After syncing tag checkboxes, the Tags tab shall re-apply tags that are
auto-created from assigned categories. This ensures a Save cannot silently
remove a category-derived tag.

## Source

- TagsTab::handleTagSave()
- Test: TagsTabTest::testAutoSyncCategoryTagCreatesTagWhenNotFound
- Test: TagsTabTest::testAutoSyncCategoryTagUsesExistingTag

## Acceptance Criteria

1. After `syncAssignments`, the tab calls `listCategoryAssignments($stockId)`
   to get all assigned categories.
2. For each assigned category, `autoSyncCategoryTag($stockId, $catId, true)`
   is called to ensure the tag exists and is linked.
