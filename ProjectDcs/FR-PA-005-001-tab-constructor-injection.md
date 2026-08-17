# FR-PA-005-001 — VariationsTab Uses Constructor Injection

## Status

Fixed

## Requirement

VariationsTab shall receive ProductAttributesDao and DbAdapterInterface
via its constructor, matching the pattern used by every other tab.
The tab shall not create DAO instances on-the-fly in handlePostActions().

## Source

- VariationsTab::__construct()
- hooks.php register_tabs() — passes dao + db services

## Acceptance Criteria

1. Constructor signature: `__construct(VariationsDao, ProductAttributesDao, DbAdapterInterface)`
2. No `new` operator for DAO/Adapter creation inside the tab class.
3. handlePostActions() uses `$this->coreDao` and `$this->db` directly.
