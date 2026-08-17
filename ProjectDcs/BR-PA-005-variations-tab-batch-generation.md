# BR-PA-005 — Variations Tab Batch Generation

## Status

Fixed (GitHub issue #34)

## Statement

The Variations tab shall create all possible child products when the user
clicks "Generate Variations" or "Create Child Product". Both buttons shall
compute the cartesian product of all assigned category values and create one
child product per combination.

## Rationale

Issue #34: The "Generate Variations" button silently failed because
VariationsTab created DAOs on-the-fly that could fail with no feedback.
The "Create Child" button only created a single child product regardless
of how many category/value combinations existed.

## Acceptance Criteria

1. "Generate Variations" shall create N child products where N is the
   cartesian product of all assigned category value counts.
2. "Create Child Product" shall also create all combinations (same as
   "Generate Variations").
3. Both buttons shall show a notification with the count of created products.
4. Both buttons shall skip combinations that already exist in stock_master.
5. Both buttons shall record attribute assignments linked to the parent
   via parent_stock_id.
