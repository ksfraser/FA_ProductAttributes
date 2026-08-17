# BR-PA-001 — Product Attribute Tags Management

## Status

Fixed (GitHub issues #22, #23)

## Statement

The Product Attributes module shall manage product–tag associations through
the Tags tab. Tag checkboxes are synced on every Save. Category assignments
are managed exclusively via the dedicated Add Category button and are never
re-interpreted from the DDL on a plain Save.

## Rationale

Issue #22: When a user removed a category assignment, the DDL's
`pa_category_id` value was still present in the POST data. The old
`handleTagSave` code treated it as a new assignment, silently re-adding the
category on the next Save.

Issue #23: The category-derived tag was lost when syncAssignments cleared all
tags before re-applying category-derived tags.

## Acceptance Criteria

1. A plain Save (clicking Save without touching the Add Category button) must
   **not** create new category assignments from the DDL value.
2. Category-derived tags (auto-created from assigned categories) must be
   re-applied after `syncAssignments` clears the tag list.
3. The DDL field name shall be `pa_category_id` (consistent with the Add
   Category button's POST key).
