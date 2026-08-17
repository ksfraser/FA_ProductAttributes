# FR-PA-001-002 — Tags Tab Does Not Auto-Assign Categories from DDL

## Status

Fixed (GitHub issue #22)

## Requirement

A plain Save on the product edit form must **not** interpret the
`pa_category_id` DDL value as a category assignment request. Category
assignments are only created by the dedicated `pa_category_add` button
handler.

## Rationale

The DDL is a filter/selector UI element. Its value is always present in the
POST data even when the user did not intend to assign a category. Treating
it as an assignment causes removed categories to be silently re-added.

## Source

- TagsTab::handleTagSave()
- Test: TagsTabTest::testHandleSaveDoesNotAutoAssignFromDdl

## Acceptance Criteria

1. `attributesDao->addCategoryAssignment()` is **never** called from
   `handleTagSave()`.
2. Only the `pa_category_add` button handler (in `handlePostActions`) may
   trigger `addCategoryAssignment()`.
