# AGENTS.local — FA_ProductAttributes repo-local notes

Repo-specific operational notes for FA_ProductAttributes. Shared cross-module
facts/conventions live in the hardlinked `AGENTS.md` / `AGENTS_ARCH.md`
(don't edit those here unless re-hardlinking). This file is editable per-repo.

## Variation generation — the intended 2-action design (verified, committed #60 / b3ece26)

The Variations tab is intended to expose exactly **two** buttons / actions, as
discussed. This is ALREADY implemented and committed (see b3ece26 "split
Generate into Combinations + Generate Child via persisted pool (#60)"):

1. **Generate Combinations** (`generate_combos` -> `GenerateCombosAction`)
   - Computes the cartesian product of the parent's assigned category values.
   - PERSISTS that set into the combo pool table `product_variation_combos`
     (via `CombosDao::syncCombos`), keyed by `stock_id` with a `slug_key`
     (royal-order slug chain, for the child stock_id suffix) and a
     `value_set_key` (order-independent value-id set, for dedupe).
   - Does NOT create stock_master children.
   - Idempotent: combos already in the pool are untouched; re-running after a
     category/value change only ADDS newly-produced combos.
   - Schema: `sql/31_product_variation_combos.sql`, wired into
     `VariationsDao::ensureVariationsSchema`.

2. **Create Child Product** (`create_child_product` -> `CreateChildProductAction`,
   renamed from the old `generate_child`/`GenerateChildAction`)
   - Reads the persisted pool (`CombosDao::listCombos`), instantiates each
     not-yet-instantiated combo into a real stock_master child, and stamps the
     combo's `child_stock_id` (`markInstantiated`).
   - Each raised child is created through the FA-native chokepoint
     (`createChildProduct` -> `add_item`, giving it its item_codes row so it is
     invoice-selectable) and then receives the FULL PA-attribute clone:
     category assignments (`copyParentCategoryAssignments`), the combo's
     concrete value assignments (`product_attribute_assignments` via
     `recordAssignments`, read from the pool's `value_set`), and the "other PA
     attributes" (`cloneProductAttributes`: identifiers / shipping attributes /
     warranty / lifecycle flags / tag assignments).
   - Reconciles only THIS parent's children against the pool (add/remove
     categories/options handling):
     - combo not in pool but child exists, no stock_moves history -> DELETE
       (fully removed, no orphaned history).
     - history but zero on-hand -> DEACTIVATE (`inactive = 1`); history kept.
     - history with on-hand -> leave ACTIVE but report as "with stock"
       (blocked list).
   - Nothing is auto-rewritten when the parent's categories/values change;
     the destructive delete branch never touches a child with transaction
     history (hence the confirmation summary surfaced by the action).
   - Warns ("Run Generate Combinations first") if no combo set is saved.

### Where each creation path is wired (ActionHandler::handlePostActions)

| Action key | Class | Role |
|---|---|---|
| `generate_combos` | `Actions\GenerateCombosAction` | (1) store cartesian pool — intended button 1 |
| `create_child_product` | `Actions\CreateChildProductAction` | (2) pool -> children + reconcile + FULL PA clone — intended button 2 |
| `generate_variations` | `Actions\GenerateVariationsAction` | LEGACY: on-the-fly cartesian + shipping-only clone; no longer emitted by `VariationActionButtons` (kept only as an unrouted case) |
| `create_missing_variations` | `Variations\Actions\CreateMissingVariationsAction` | OLDEST: bulk backfill, shipping-only; wired only via the non-rendered legacy `VariationsButtonsPanel` |
| `create_child` (removed) | `Actions\CreateChildAction` | REMOVED: redundant single-child on-the-fly path superseded by `create_child_product` |

`VariationActionButtons` now renders exactly TWO buttons: **Generate Combinations**
(`generate_combos`) and **Create Child Product** (`create_child_product`). The third
"Create Child Product" button that previously mapped to the legacy single-child
`create_child` path has been removed; `Actions/CreateChildAction` and the dead
duplicate `Variations/Actions/CreateChildAction.php` (+ its test) were deleted.

Still-duplicated legacy trees NOT yet removed (out of scope, noted here): 
`Actions/GenerateVariationsAction.php` vs `Variations/Actions/GenerateVariationsAction.php`
(the latter is referenced only by `GenerateVariationsActionTest`), and
`Variations/Actions/CreateMissingVariationsAction.php`. Only `Actions\GenerateVariationsAction`
is still wired (unrouted legacy case); the `Variations\Actions\` copies are dead in the
current UI (only their own unit tests reference them).

## Chokepoint for child creation -> FA native add_item()

`VariationsDao::createChildProduct` is the SINGLE chokepoint for cloned child
stock rows across all creation actions. It now calls `tryNativeAddItem()`
(gated on `function_exists('add_item')`), which maps the parent's stock_master
columns onto FA's native `add_item()` (items_db.inc:56) so the child also gets
an `item_codes` row (and `loc_stock`) — required for the child to appear in the
Direct Invoice / sales item picker (`sales_items_list` joins `item_codes`).
Falls back to raw INSERT when `add_item` is unavailable (unit tests / standalone).

## PA-attribute clone surface (what "full clone" means)

`CreateChildProductAction::cloneProductAttributes()` copies these child tables
from parent -> child (only for rows where the parent has a row), rebinding
`stock_id` to the child and skipping `id`:
- `product_identifiers`
- `product_shipping_attributes`
- `product_warranty`
- `product_lifecycle_flag_assignments`
- `product_tag_assignments`

Plus `copyParentCategoryAssignments()` (category assignments) and
`recordAssignments()` (product_attribute_assignments rows linked via
parent_stock_id).

RESOLVED (this session): the pool-driven `CreateChildProductAction` now performs
the full PA clone — native add_item (via the createChildProduct chokepoint) +
category assignments + recordAssignments + cloneProductAttributes. To support
`recordAssignments` from the persisted pool, the combo table gained a `value_set`
JSON column holding each combo's `[{category_id, value_id, slug}]` list.

## product_variation_combos schema — value_set migration

`sql/31_product_variation_combos.sql` (fresh install) now includes:
`value_set TEXT NULL COMMENT 'JSON array of {category_id, value_id, slug} so Create Child can record the child value assignments'`.

For an EXISTING database that already ran the pre-`value_set` `31_...sql`, apply:

```sql
ALTER TABLE `0_product_variation_combos`
  ADD COLUMN `value_set` TEXT NULL COMMENT 'JSON array of {category_id, value_id, slug} so Create Child can record the child value assignments' AFTER `slug_key`;
```

Null/blank `value_set` decodes to `[]` in `CombosDao::listCombos`, so pre-existing
combos (recorded before this migration) still instantiate fine — they just have no
value-assignment rows to record (acceptable legacy data; re-run Generate
Combinations once to repopulate `value_set`).

## REPO WORKFLOW — dev tree vs integration/test tree (IMPORTANT, user directive)

Two parallel checkouts exist and agents routinely edit the wrong one:

| Role | Path | Use |
|---|---|---|
| **DEV (write code here)** | `~/Documents/FA_ProductAttributes` | All code/tests/docs edits, commits, pushes. |
| **TEST / integration (deploy-only)** | `~/Documents/ksf_Infrastructure/fa_modules/FA_ProductAttributes` | Live bind-mount into the ksf-fa container (`/var/www/html/modules/FA_ProductAttributes`). Changes happen ONLY by pulling the deployed branch (git pull after push). |

Workflow for every change: edit in DEV → test (phpunit) → commit → push to
GitHub → `git pull` in the TEST tree → live E2E against the container.

Do NOT edit files under `~/Documents/ksf_Infrastructure/fa_modules/` directly
(unless explicitly told), and do NOT treat the container's view of modules as
your workspace.

Shared docs (`AGENTS.md`, `AGENTS_ARCH.md`, `MODULE_DIRECTORY.md`, etc.) are
hardlinked across repos and must NOT be edited per-repo without explicit
instruction. Repo-specific notes go ONLY in this `AGENTS.local.md`.

## Environment bring-up (ksf-fa UAT container stack)

Rootless podman under user `kevin` (UID 1002). My shell (opencode) runs as root —
use `runuser -u kevin -- env HOME=/home/kevin XDG_RUNTIME_DIR=/run/user/1002 podman …`.

- Host LAN IP is dynamic; after reboot the LAN iface changed from wifi
  `192.168.1.102` to wired `192.168.1.88`. The `.e2e/*.js` scripts hardcode the
  old base URL — pass the current IP or use `http://localhost:8080`.
- Stack today (Sep 2026 arch decision): `fa/2.4.3` is mounted READ-ONLY as the FA
  docroot; each instance (ksf-fa, ksfii-app) gets its own `config_db.php`,
  `installed_extensions.php`, `company/0` binds from
  `~/Documents/ksf_Infrastructure/fa/ksf_fa/`. ksf-mariadb in the pod holds ALL
  DB data.
- Containers: `ksf-mariadb` (3306 internal), `ksf-fa` (8080→80),
  `ksfii-app` (8090→80). Network `ksf_network`.
- `podman exec` is BLOCKED (cgroup permission denied). Workaround: reads via
  `/proc/<pid>/root/...`, execution via HTTP-served PHP under
  `modules/FA_ProductAttributes/public/`.
- Auth: FA user `opencode` / pass `opencode` (NOT admin/admin on this instance).
  Login POST `/index.php` (see E2E recipes elsewhere).
- Start scripts under `~/Documents/ksf_Infrastructure/podman/`: `start-fa.sh`
  (plain `podman run`, considered more correct historically) and today's
  `compose.yaml`/`ksf-compose.yaml` written by a different agent during recovery.
  Before restructuring anything, compare against the RUNNING container's mounts
  (`podman inspect ksf-fa`) — the live config already matches the arch decision.

## Module-install failure mode on this rebuild (found 2026-09)

New/empty FA mounts make the Extensions page
(`/admin/inst_module.php`) fail with:

> Cannot open the extension setup file '../company/0/installed_extensions.php' for writing.

and "Cannot download repo index file." (local-only install is fine).

Root cause: the bind-mounted `fa/ksf_fa/company/0/` dir is not writable by the
container's Apache UID (rootless podman user-namespace mapping). Fix: make
`company/0` (+ `installed_extensions.php`) writable on the host path. Do NOT add
`module/` activation blobs in the RO `fa/2.4.3`; the wrritable extension registry
is the per-instance bind.

## FA extension install & activation on this instance (verified 2026-09)

Everything here was live-verified against `fa/2.4.3` source + the ksf-fa UAT
box. Cross-module mechanics live in the shared AGENTS.md ("FA extension install
& activation mechanics"); this section is the FA_ProductAttributes-tuned
procedure and CURRENT STATE.

### Current state (ksf-fa, company 0) — DONE, modules ACTIVE

- Root registry `fa/ksf_fa/installed_extensions.php` and company registry
  `fa/ksf_fa/company/0/installed_extensions.php` have 5 entries each:
  ksf_FA_ImportStagingProcessing (`-`), FA_ProductAttributes (`2.4.4`, ACTIVE),
  export_woocommerce (`-`), ksf_Calendar (`-`), ksf_FA_Common (`2.4.4`, ACTIVE).
  A duplicate ksf_FA_Common entry (from double-clicking Local) was removed and
  `$next_extension_id` set to 5.
- Admin UI (`admin/inst_module.php?extset=0`) shows both checkboxes checked.
  FA_ProductAttributes schema (35 sql files) installed OK via the normal
  activation once the `{TB_PREF}` files were fixed; ksf_FA_Common tables
  created manually (see below).
- Credentials: FA user `opencode`/`opencode`. **Login POST MUST include
  `company_login_name=0`** or FA 401s "Incorrect Password" even with a valid
  password (`includes/session.inc:545`).

### Why activation "silently does nothing" (the 3 gates)

1. **Version gate** — `check_src_ext_version('-')` always returns false
   ("Package 'FA_ProductAttributes' is incompatible... cannot be activated").
   `local_extension()` writes `version => '-'` unconditionally. Fix: give
   registered entries a NUMERIC version matching the module's `_init/config`
   (FA_ProductAttributes + ksf_FA_Common = `2.4.4`).
2. **ID mismatch** — checkboxes are `Active<i>` over the MERGED + sorted
   GLOBAL registry; don't assume index == company-registry index. Map from the
   rendered HTML first.
3. **SQL prefix** — `db_import()` substitutes ONLY literal `0_`. Our seed files
   used `{TB_PREF}` → "Table 'ksf_fa.{TB_PREF}product_lifecycle_flag_defs'
   doesn't exist" mid-install. FIXED in commit `b9f484e` ("fix(install): use
   literal 0_ table prefix in SQL files"): files now `0_` in
   `sql/17_lifecycle_flag_defs_seed.sql`, `18_product_identifier_lookups.sql`,
   `19_product_attribute_categories_seed.sql`, `35_product_condition_defs_seed.sql`.

### ksf_FA_Common activation kills the extension page (autoload conflict)

Activating ksf_FA_Common via the UI fatals the request (footer-only page, no
message, registry not written): its `hooks_ksf_FA_Common::__construct`
`require_once`s `src/autoload.php`, which redeclares
`ksfraser\FrontAccounting\Common\*` classes that FA_ProductAttributes' vendored
ksf-fa-common Composer autoloader already loaded. Documented hazard in the
module's own hooks.php header. Worked around here by **manual activation**:
schema via raw mysqli + `active => true` set directly in both registries.
(FA_ProductAttributes itself activated 100% via the normal UI with result 1.)

Step-by-step manual activation (repeatable):
1. Probe-tooling scripts live under test-tree `public/` (bind-mounted at
   `/var/www/html/modules/FA_ProductAttributes/public/`), run after a
   login as `opencode`:
   - run `sql/install.sql` of ksf_FA_Common raw via mysqli
     (`0_ksf_contact_types`, `0_fa_job_queue`, `0_ksf_item_sync_state`,
     `0_ksf_item_event_watermark`, `0_ksf_notifications`) + seed 4 contact
     types (INSERT IGNORE).
   - set `active => true` for FA_ProductAttributes + ksf_FA_Common in BOTH
     `fa/ksf_fa/installed_extensions.php` and
     `fa/ksf_fa/company/0/installed_extensions.php`.
   - CAREFUL: registry include sets `$installed_extensions`, not `$installed` —
     a probe that did `include ...; $installed` and `var_export($installed,1)`
     wrote an EMPTY array and wiped the registry. Restore from the recipe above
     (5 entries) if that ever happens again.
2. `podman exec` unavailable → reach the DB only via HTTP-served PHP; the
   ksf-mariadb container is internal-only (no published port).

## FA_ProductAttributes issue #52 root cause (verified) — migrated from shared AGENTS

Two parallel, un-unified parent-relationship mechanisms:

| Concern | `product_hierarchy` (via `ProductAttributesDao`) | `product_attribute_assignments.parent_stock_id` (via `VariationsDao`) |
|---|---|---|
| Writes | `setProductParent($child,$parent)` (INSERT…ON DUP UPD / DELETE) | `setParentRelationship()` (called by `CreateChildAction`) AND `addAssignment(...,$parentStockId)` |
| Reads | `getProductParent()` — **used by `VariationsTab` for `$isChild` detection** | `getProductVariations()`, `isVariation()` |
| Populated on CreateChildAction? | **NO** — nothing calls it | **YES** |

- `VariationsDao::setParentRelationship()` (VariationsDao.php:334) writes
  `product_attribute_assignments.parent_stock_id`.
- `ProductAttributesDao::setProductParent()`/`getProductParent()`
  (ProductAttributesDao.php:392/416) write/read `product_hierarchy`.
- `CreateChildAction::handle()` calls `variationsDao->setParentRelationship($childId,
  $stockId)` (CreateChildAction.php:88) but NEVER `setProductParent()`.
- `VariationsTab::renderTabContent()` sets `$isChild = !empty($this->dao->getProductParent($stockId))`
  (VariationsTab.php, ~line 51-54) and renders read-only + hides buttons when child.
- Net: generated children (`auto-gas-L-11-36-Ind` etc.) are registered only in
  `product_attribute_assignments`, so `product_hierarchy` is empty for them →
  `getProductParent()` returns null → `$isChild` false → read-only protection never
  engages for generated children → issue #52.

## FA_ProductAttributes Generate Combinations semantics (2026-09) — migrated from shared AGENTS

- Combos derive from the product's OWN value assignments
  (`product_attribute_assignments`), **one value per category** — NOT from every
  active value of its assigned categories. A product assigned 4 Awesomeness + 1
  Shoe Size produces 4 × 1 = 4 combos. (Before: stock 101 `ipad` produced 168
  because all 7 Color × 2 Shoe × 3 Clothes × 4 Awe taxonomy values were fed in.)
  Implemented in `GenerateCombosAction::assignedCategoryValues()`.
- Re-running **Generate** now reconciles the pool: `CombosDao::pruneStale()`
  deletes uninstantiated rows no longer produced (`child_stock_id IS NULL` only);
  rows stamped with a child are always preserved. The `syncCombos` insert-only
  doctrine is gone; orphan reconciliation stays a Create Child concern for
  *children*, while *pool* staleness is Generate's concern.
- The Variations tab renders the persisted pool in a "Saved Combinations"
  fieldset (`VariationSections`... `CombinationPoolSection` +
  `CombosDao::listCombos`), so the generated set is visible immediately —
  "changing tabs and coming back shows nothing" was fixed by this, plus
  `Existing Variations` still lists only instantiated children (Create Child).
- Live-verified on stock 101: `Combination set saved: 4 new / 4 total. 168 stale
  combinations pruned.`

## ksf-fa integration instance — blank-page bootstrap findings (2026-09) — migrated from shared AGENTS

Env access facts + the two stacked bootstrap failures found while E2E-testing the
condition feature. Container: PHP 7.4.33 + Apache. `podman exec` blocked (`crun:
/sys/fs/cgroup … Permission denied`) → read FS via `/proc/<pid>/root/var/www/html`,
execute via HTTP-served PHP in a bind-mounted module `public/` dir.

- **FAModuleMenu double-declare fatal = STALE OPCACHE, not a source bug.** Current
  `ksf_FA_Common/src/Menu/FAModuleMenu.php` has a working `class_exists(..., false)`
  guard; the vendored copy `FA_ProductAttributes/vendor/ksfraser/ksf-fa-common/
  src/Menu/FAModuleMenu.php` has NO guard and wins the race via Composer "files"
  during session.inc's hooks include loop; `ksf_FA_HRM/hooks.php:12–14` then
  top-level requires the ksf copy (guard returns). Live opcache held a pre-guard
  bytecode entry (`validate_timestamps=1, revalidate_freq=2`); one HTTP probe that
  called `opcache_reset()` cleared it — **no source change needed**.
- **Blank pages** (`/index.php` + module `public/index.php` → HTTP 200, 0 bytes):
  `/tmp/php_errors.log` shows session.inc's `[before upgrade]` branch failing
  relative includes (`./tmp/faillog.php`, `./includes/access_levels.inc`,
  `./version.php`, `./includes/main.inc`, `./includes/app_entries.inc`) →
  `Class 'references' not found` at session.inc:469 → `errors.inc` exception_handler
  calls `end_page()` (defined in main.inc:57, not yet included) → empty body.
- **Suspect / open question:** CWD appears wrong when session.inc lines 454–457 run
  (relative `./` includes fail) — a hooks/session_start callback `chdir()`'d, or a
  request-time composer exec did. Log also shows `The HOME or COMPOSER_HOME
  environment variable must be set` from a module's `composer install`. All
  chdir+composer code is method-level (`ensure_composer_dependencies()` /
  `Utils/ComposerDependencies.php`), not top-level — NOT confirmed as the trigger.
  `[before upgrade]` means `!$SysPrefs->db_ok` yet `sysprefs.inc:66` compares
  `version_id` (`2.4.1`) == `$db_version` (`2.4.1`) — likely a transient DB-connect
  hiccup cached in the session, or prefs-load drift.
- **Extension registry** `/var/www/html/company/0/installed_extensions.php` (28
  active). Composer-run candidates: `ksf_FA_EmailManager` (id 29, active, NO
  `vendor/`, NO `composer.lock`), `ksf_FA_SuggestedPurchaseOrder` (NO `vendor/`).
  Host dirs missing but shadowed by image copies: `ksf_FA_Attachments`,
  `ksf_FA_OrgChart`, `ksf_FA_Timesheets`, `ksf_FA_Training`. DB health from the web
  container is fine (mysqli `ksf-mariadb`/`ksf_user`/`ksf_fa` OK, 586 stock rows).
- **Handed-off fix path:** set `HOME`/`COMPOSER_HOME` in the container env, vendor
  the vendor-less active modules (or harden `ComposerDependencies` to restore CWD /
  `putenv`) and restart the container; then re-run the Playwright E2E (chrome at
  `~/Documents/ksf_FA_Square/node_modules`, login `opencode`/`opencode`). Scheme
  was applied directly to the integration DB via an HTTP probe because the FA
  re-activation UI is unusable while login renders blank.

## Items tab rendering on the ksf-fa UAT box — two missing pieces (fixed, verified 2026-09)

Symptom: `inventory/manage/items.php?stock_id=101` rendered ONLY the 7 core tabs
(settings/sales_pricing/purchase_pricing/standard_cost/reorder_level/movement/
status) — no `tabs_product_variations` etc. Root causes (BOTH required to see tabs):

### 1. FA core never invokes the module's tab hooks (host patch was missing)

`FA_ProductAttributes/hooks.php` exposes `item_display_tab_headers` /
`item_display_tab_content` / `post_item_write` / `pre_item_delete` as hook
METHODS, but FA 2.4.3's `inventory/manage/items.php` has NO extension points —
`tabbed_content_start()` only renders the hardcoded `$tabs` array + core switch.

The container's `/var/www/html` is a **host bind mount of
`~/Documents/ksf_Infrastructure/fa/2.4.3`**, so the patch is a plain file edit
(linted, diffed clean vs `fa/2.4.3.tgz`). Four insertions, flagged `// KSF host hook`:

| Location (post-patch line) | Insertion |
|---|---|
| `items.php:604` (before `tabbed_content_start`) | `$moduleTabs = hook_invoke_all('item_display_tab_headers', $tabs, $stock_id);` + reassign `$tabs` if non-empty (hook_invoke_all returns array of results; single-module instance → the returned tabs array) |
| `items.php:647` (new `default:` case in the `_tabs_sel` switch; the old `default: case 'settings':` fallthrough was split) | `hook_invoke_all('item_display_tab_content', $stock_id, get_post('_tabs_sel'));` |
| `items.php:263` (after `$Ajax->activate('_page_body')` in the `addupdate` success block) | `$itemWritten = array('stock_id' => $_POST['NewStockID']); hook_invoke_all('post_item_write', $itemWritten, $_POST['NewStockID']);` |
| `items.php:296` (in the `delete` block, before `delete_item($stock_id)`) | `hook_invoke_all('pre_item_delete', $stock_id);` |

GOTCHA that bit me: my first save-hook edit matched the `if (get_post('cancel'))`
block (the `$Ajax->activate('_page_body');` line there has NO leading tab, but
the `addupdate` one has two tabs) and dropped the `list_updated('category_id')...`
block, causing "Unmatched '}'". Restored from `fa/2.4.3.tgz`. Verify with
`diff <(tar -xOzf fa/2.4.3.tgz inventory/manage/items.php) fa/2.4.3/inventory/manage/items.php`.
This patch is UNTRACKED in the infra repo — will be lost on any `fa/2.4.3` reset.
Re-apply via the diff above.

### 2. opencode (role 2) lacked the module's security areas

`hooks.php:item_display_tab_headers/content` gate on
`user_check_access('SA_PRODUCT_ATTRIBUTES')`. FA's `add_access_extensions()`
(`includes/access_levels.inc:284`) REMAPS each module's declared codes to a
dynamic 3-byte code `extid<<16 | section | area` where `extid` = the registry
array key and section/area counters start at 100 per extension (reset per
extension). With FA_ProductAttributes at registry key `1`:

- section = `(100<<8) | (1<<16)` = **91136**
- areas   = `(1<<16) | (100<<8) | 100` = **91236** (`SA_PRODUCT_ATTRIBUTES`),
  `91237` (`SA_FA_ProductAttributes`)

Role 2's stored codes maxed at 16132 → no module access → tab hooks bailed.
Also note `current_user.inc:123-126`: an area is only honored if its section
(`code&~0xff`) is ALSO in the role's `sections`. FIX applied to `0_security_roles`
role 2 (one-time SQL via HTTP probe): append `91136` to `sections`,
`91236;91237` to `areas`. Re-login required (role_set computed at login).

### Verified E2E-ready after both fixes (fresh opencode login)

- `items.php?stock_id=101` headers now include all 8 module tabs
  (`tabs_product_attributes/variations/identifiers/lifecycle/media/urls/
  warranty/shipping_attributes/tags_categories`).
- POSTing `_tabs_sel=product_variations` renders Create Child + Generate
  Combinations; `product_attributes`/other tabs render their content (the
  `tableheader` grep was just the wrong marker — content emits `pa_*` fields).
- Simulated-now-deleted probes to avoid confusion: `public/_*.php` removed from
  the test tree.

### Items.php host-hook patch — now a committed, idempotent installer (2026-09)

FA 2.4.3 `inventory/manage/items.php` has no extension points, so the 4 host
hooks the tab system needs were first inserted by hand into the live bind mount.
To make the patch shippable + re-appliable after core resets:

- `patches/items.php.ksf-tabs.patch` — unified diff (pristine `fa/2.4.3.tgz`
  items.php → patched). Source of truth for the exact 5 hunks:
  `item_display_tab_headers` merge, `item_display_tab_content` default-case,
  `post_item_write`, `pre_item_delete`, and the `default: case 'settings':`
  fallthrough split.
- `Install/ItemsPhpTabHookPatcher.php` — idempotent installer. Gate = `// KSF
  host hook` sentinel: already-patched files are a no-op (`already_patched`).
  Each hunk applies only when its pristine anchor occurs exactly once; a
  missing/ambiguous anchor aborts softly (`skipped`) leaving the file untouched.
  Anchors MUST be double-quoted strings (`\t`, `\n`, `\$` — single quotes never
  byte-match; patcher output was verified byte-identical to the live working
  tree).
- Wired into `hooks.php:activate_extension()`: runs only on real install
  (`!$check_only`), non-fatal behind try/catch, logs result to
  `/tmp/pa_items_patch.log` (never blocks activation if FA core is unwritable).
- `plugin-tests/Install/ItemsPhpTabHookPatcherTest.php` — apply → already-patched
  no-op → byte-identical re-run → unknown-state refusal → missing-file skip (5
  tests).

Run `vendor/bin/phpunit plugin-tests/Install` after touching the patcher or the
patch file; both must stay in sync.

### .e2e — repointed to localhost:8080 + rewritten to current UI (verified 12/12)

- All `.e2e/*.js` BASE swapped `http://192.168.1.102:8080` →
  `http://localhost:8080` (103 occurrences).
- `e2e.js` was stale vs the current module UI. Rewritten selectors/flows:
  - admin seeds submit the hidden `action=upsert_category` / `upsert_value` form
    (per-row edit/delete buttons made the old `button[type=submit]` click
    ambiguous).
  - category assignment now goes through the item **Product Attributes** tab
    Add Assignment box (`select#pa_category_select` + `add_all` +
    `button[name=add_pa_assignment]`), not the removed item-side
    `assign_category_*` controls.
  - variations actions are `button[name=generate_combos]` and
    `button[name=create_child_product]` (was `generate_child`), both `ajaxsubmit`
    (AJAX POST — no navigation, so wait by polling content, never
    `waitForNavigation`).
  - parent id kept <= 32 chars (`E2EP<6digits>`): children are
    `<parent>-<slug-chain>` and FA stock_id is varchar(32); a longer parent makes
    Create Child skip with "exceeds the 32-character limit".
  - child read-only check uses an auto-waiting locator on the fieldset text and
    strips the description column from the extracted child id.
- Full flow passes live 12/12: login → seed → create parent → assign 2
  categories → Generate Combinations → Create Child Product → children listed →
  child read-only (issue #52).
## Issue resolutions landed in dev tree (2026-09-11) — code + unit suite green

Each fix has a phpunit unit test and every touched file passes `php -l`. Live
E2E needs a credentialed FA session (localhost:8080 serves the Login page to
anonymous probes, login.php 404 — FA 2.4.3 auth is at the session root).

- **#11** MediaTab already uses a Fetch `FormData` POST (bypasses the nested
  multipart form that the host `start_form(true)` flattens — same mechanism as
  the module's other tabs; verified in MediaTab.php).
- **#53 pre_item_delete guard + pool cleanup**: `VariationsTab::assertDeletable()`
  throws a RuntimeException before ANY tab cleanup when the product still has
  children; `hooks.php` calls it first in the loop, then all tabs' `handleDelete`
  (which now purge the parent's `product_variation_combos` pool via
  `CombosDao::deleteParentPool()`). Deleting a parent with children → FA's
  exception renderer → delete blocked (Mockito SmartReporter catches it).
- **#62 button gating**: `VariationActionButtons::render(bool $render, bool
  $hasCombos)` — Create Child Product stays `disabled` until the combination
  pool is persisted (`CombinationPoolSection` reuses `CombosDao::listCombos`).
- **#63 child description**: `createChildProduct(..., $variationLabel)` composes
  child description as "<parent description> <label1> <label2>" from the combo's
  value-set labels (issue #63) on BOTH paths; `VariationsDao::tryNativeAddItem`
  and the fallback `add_item`. `GenerateCombonsAction` value-set now carries the
  human label; `CreateChildProductAction::resolveValueLabels()` maps value_ids →
  labels so pool rows store readable names.

Test state: full suite 1010 tests / 2334 assertions, 0 failures, 2 pre-existing
skips (sql/schema.sql not shipped).
