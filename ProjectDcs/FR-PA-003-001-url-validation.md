# FR-PA-003-001 — URLs Tab Validates Non-Empty URL

## Status

Fixed (GitHub issue #16)

## Requirement

When the user submits the Add URL form, the tab shall trim the URL and
reject empty values with `display_error('A URL is required.')`. The DAO's
`add()` method shall not be called for empty URLs.

## Source

- UrlsTab::handlePostActions()
- Test: UrlsTabTest::testPostAddUrlIgnoresEmptyUrl

## Acceptance Criteria

1. `$url = trim((string)($_POST['url'] ?? ''))`.
2. If `$url === ''`, call `display_error()` and return.
3. `dao->add()` is only called for non-empty URLs.
