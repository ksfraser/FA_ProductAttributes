# FrontAccounting 2.4 Internals

## Extension Registration (`installed_extensions.php`)

FA stores installed extensions in **PHP files**, not the database.

**Global** (loaded at bootstrap):
```
<FA_ROOT>/installed_extensions.php
```

**Per-company override** (loaded after login, replaces global):
```
<FA_ROOT>/company/<N>/installed_extensions.php
```

**Loading order** (`includes/session.inc`):
1. Global file loaded in `frontaccounting.php` (bootstrap)
2. Per-company file loaded in `session.inc:495` after user login (overrides global)

**Definition** (`admin/db/company_db.inc:62`):
```php
function get_company_extensions($id = -1) {
    global $path_to_root;
    $file = $path_to_root . ($id == -1 ? '' : '/company/'.(int)$id) . '/installed_extensions.php';
    $installed_extensions = array();
    if (is_file($file)) include($file);
    return $installed_extensions;
}
```

**Writing** (`admin/db/maintenance_db.inc:108`): `write_extensions()`
**Sync across companies** (`admin/db/maintenance_db.inc:147`): `update_extensions()`

**Hooks loading** (`session.inc:354`):
```php
foreach ($installed_extensions as $ext) {
    if (file_exists($path_to_root.'/'.$ext['path'].'/hooks.php'))
        include_once($path_to_root.'/'.$ext['path'].'/hooks.php');
}
```

### Extension Array Structure

Each entry is keyed by numeric ID:
```php
$installed_extensions = array(
    0 => array(
        'package' => 'FA_ExtensionName',   // unique identifier
        'name'    => 'FA_ExtensionName',   // display name
        'version' => '-',                  // installed version ('-' = local)
        'available' => '',                 // latest from repo
        'type'    => 'extension',          // 'extension', 'theme', 'chart'
        'path'    => 'modules/FA_ExtensionName',  // relative from FA root
        'active'  => true,                 // enabled for this company
    ),
);
```

### Extension Listing (`includes/packages.inc`)

`get_extensions_list($type)` at line 485 works in three steps:

1. **Remote repo** (line 493): fetch FA extension repository index → `$pkgs` keyed by package name
2. **Scan modules/** (line 508): add every subdirectory under `modules/` to `$pkgs`
3. **Merge installed** (line 531): merge `installed_extensions.php` entries into `$pkgs`

**Critical bug at line 538**: The code does `array_merge($pkgs[$ext['package']], $ext)` without checking if `$pkgs[$ext['package']]` exists. If an extension was installed (has a record in `installed_extensions.php`) but is not in the repo AND has no directory in `modules/`, it causes:

```
Undefined index: FA_ProductAttributes_Core in file: packages.inc at line 538
```

The original safety check (now commented out) was:
```php
if (!isset($pkgs[$ext['package']]) || $ext['package'] == '')
    $pkgs[] = $ext;
else
    $pkgs[$ext['package']] = array_merge($pkgs[$ext['package']], $ext);
```

**Fix options** for stale extension records:
- Delete the entry from `installed_extensions.php` files (both global and per-company)
- Or create an empty directory `modules/<ExtensionName>/` so step 2 creates the `$pkgs` entry

---

## Items Page (`inventory/manage/items.php`)

### Hook Integration

Vanilla FA `items.php` has **zero hook calls**. Patched version at:
```
ksf_Infrastructure/fa_modules/inventory/manage/items.php
```

Added hook calls:
| Hook | When | Purpose |
|------|------|---------|
| `item_display_tab_headers` | During tab header rendering | Add custom tabs |
| `item_display_tab_content` | When a tab's content is requested | Render custom tab content |
| `post_item_write` | After item save | Persist custom tab data |
| `pre_item_delete` | Before item deletion | Clean up custom tab data |

### Tab System

Items page uses a **dual tab system**:

1. **Object-based tabs** (if `fa-hooks` module installed):
   ```php
   if (is_object($tabs) && method_exists($tabs, 'createTab')) {
       $tabs->createTab($tabKey, $tabLabel);
   }
   ```

2. **Array-based tabs** (vanilla FA):
   ```php
   $tabs[$tabKey] = array($tabLabel, $stockId);
   ```

Tab plugins implement `ProductAttributeTabInterface` (in `_Core`):
| Method | Purpose |
|--------|---------|
| `getTabKey()` | Unique tab identifier |
| `getTabLabel()` | Display label |
| `getTabClass()` | CSS class |
| `isAvailable($stockId)` | Whether tab shows for this item |
| `renderTabContent($stockId)` | Render HTML |
| `handleSave($stockId, $post)` | Persist on item save |
| `handleDelete($stockId)` | Clean up on item delete |

---

## Database Schema (Authoritative FA 2.4)

Authoritative schema: `ksf_Infrastructure/docker/fa-alpine/fa_files/sql/en_US-new.sql`
NotrinosERP is a fork — use for inspiration only.

### `stock_master`

| Column | Type | Notes |
|--------|------|-------|
| `stock_id` | varchar(20) | PK |
| `description` | varchar(200) | |  
| `long_description` | text | |
| `category_id` | varchar(6) | FK → `stock_category` |
| `tax_type_id` | smallint | FK → `tax_types` |
| `units` | varchar(20) | UOM |
| `mb_flag` | char(1) | M=manufactured, B=purchased, D=service, V=variation |
| `material_cost` | double | Not `cost` |
| `labour_cost` | double | |
| `overhead_cost` | double | |
| `no_sales` | tinyint(1) | Disallow sales |
| `no_purchases` | tinyint(1) | Disallow purchases |
| `editable` | tinyint(1) | Allow description edit in trans |
| `inactive` | tinyint(1) | Soft delete |

**NOT on stock_master**: `reorder_level` (on `loc_stock`), `min_order_qty`, `lead_time_days`, `cost`, `image`, `status`

### `loc_stock`

| Column | Type | Notes |
|--------|------|-------|
| `loc_code` | varchar(5) | Location code (FK → `locations`) |
| `stock_id` | varchar(20) | FK → `stock_master` |
| `reorder_level` | double | Per-location reorder point |

### `purch_orders`

| Column | Type | Notes |
|--------|------|-------|
| `order_no` | int | PK |
| `supplier_id` | varchar(10) | FK → `suppliers` |
| `ord_date` | date | Order date |
| `reference` | varchar(60) | |
| `delivery_address` | text | |
| `total` | double | |
| `trans_type` | smallint | |
| `curr_code` | char(3) | |

**No `status` column** — PO status is computed from detail line quantities.

### `purch_order_details`

| Column | Type | Notes |
|--------|------|-------|
| `po_detail_item` | int | PK |
| `order_no` | int | FK → `purch_orders` |
| `item_code` | varchar(20) | NOT `stock_id` |
| `description` | text | |
| `quantity_ordered` | double | NOT `quantity` |
| `quantity_received` | double | |
| `unit_price` | double | |
| `std_cost_unit` | double | |

PO status (open/closed):
```php
$open = ($row['quantity_ordered'] - $row['quantity_received']) != 0;
```

### `sales_order_details`

| Column | Type | Notes |
|--------|------|-------|
| `order_no` | int | |
| `stk_code` | varchar(20) | NOT `stock_id` |
| `quantity` | double | |
| `unit_price` | double | |

### GRN Tables

| Table | Key Columns |
|-------|-------------|
| `grn_batch` | `id`, `delivery_date` (actual receipt date), `order_no`, `reference` |
| `grn_items` | `id`, `grn_batch_id` → `grn_batch.id`, `po_detail_item` → `purch_order_details.po_detail_item`, `qty_received` |

### Image Storage

`stock_master` has **no `image` column**. Images are filesystem-only:
```
{company_path}/images/{img_name($stock_id)}.{jpg|png|gif}
```

---

## Hook System

FA's hook system works via `hook_invoke_all()` which calls methods on all loaded hook objects:

```php
// Definition: FA core searches for hooks_$module classes and instantiates them
// Hook object methods are called by hook_invoke_all()

// Common hook methods:
hook_invoke_all('item_display_tab_headers', $tabs, $stockId);
hook_invoke_all('item_display_tab_content', $stockId, $selectedTab);
hook_invoke_all('post_item_write', $itemData, $stockId);
hook_invoke_all('pre_item_delete', $stockId);
```

FA does NOT have a generic filter/action system — that's provided by the `fa-hooks` module (Packagist: `ksfraser/fa-hooks`).

---

## Security Areas

| Constant | Pattern | Example |
|----------|---------|---------|
| `SS_*` | Section constant = `115 << 8` | `SS_PRODUCT_ATTRIBUTES = 115 << 8` |
| `SA_*` | Area = `SS_* | N` | `SA_PRODUCT_ATTRIBUTES = SS_PRODUCT_ATTRIBUTES | 1` |

Installed via `install_access()` in hooks.php which returns `array($security_areas, $security_sections)`.

**Critical**: New `SA_*` constants must not be used in `install_options()` (menu registration) until after the module is installed via Setup → Modules. Use `SA_OPEN` for menus instead, otherwise FA crashes at menu-render time:

```
Undefined index: SA_PRODUCT_ATTRIBUTES in current_user.inc:192
```

`SA_OPEN` (`0`) bypasses access checking and always shows the menu item.

**Access checking pattern**:
```php
private function has_product_attributes_access() {
    global $security_areas;
    // Return true (allow) if neither area is defined yet
    if (!isset($security_areas['SA_PRODUCT_ATTRIBUTES'])
        && !isset($security_areas['SA_FA_ProductAttributes'])) {
        return true;
    }
    // Guard each check individually
    $hasAccess = false;
    if (isset($security_areas['SA_PRODUCT_ATTRIBUTES'])) {
        $hasAccess = $hasAccess || user_check_access('SA_PRODUCT_ATTRIBUTES');
    }
    if (isset($security_areas['SA_FA_ProductAttributes'])) {
        $hasAccess = $hasAccess || user_check_access('SA_FA_ProductAttributes');
    }
    return $hasAccess;
}
```

---

## Module Structure

### FA Module Pattern
```
<FA_ROOT>/modules/<ModuleName>/
├── hooks.php          # Class hooks_<ModuleName> extends hooks
├── public/            # Web-accessible pages
│   └── *.php
├── sql/               # SQL install scripts
├── includes/          # Internal includes
├── composer.json
└── vendor/            # Composer dependencies
```

### Business Logic Package Pattern (no hooks.php)
```
<PACKAGE_NAME>/
├── src/               # Namespaced PHP
│   └── ...
├── tests/
├── composer.json
└── vendor/
```

### Naming Convention
| Suffix | Purpose | Example |
|--------|---------|---------|
| `_FA_` | FA module (has hooks.php + pages/) | `ksf_FA_Calendar` |
| No suffix | Business logic composer package | `fa-product-attributes-core` |

---

## Composer Dependency Management

### Platform Config

`config.platform.php` in `composer.json` controls which PHP version composer resolves against:

```json
"config": {
    "platform": {
        "php": "7.3.21"
    }
}
```

If the platform check fails at runtime in `vendor/composer/platform_check.php`, the actual PHP version doesn't match the resolved versions. Fix: align `platform.php` with the target server's PHP version.

### PHP 7.3 Compatibility

Features NOT available on PHP 7.3:
| Feature | PHP Version | Replacement |
|---------|-------------|-------------|
| Typed properties (`private array $x`) | 7.4+ | Docblock + `private $x` |
| Arrow functions (`fn() =>`) | 7.4+ | Anonymous function `function() { return ...; }` |
| Null coalescing assignment (`??=`) | 7.4+ | `$x = $x ?? val` |
| Spread in arrays (`...$arr`) | 7.4+ | `array_merge` |
| `DateTime::createFromInterface()` | 8.0+ | Clone + setTimezone helper |
| `match` expression | 8.0+ | `switch` statement |
| Named arguments | 8.0+ | Positional args |
| Nullsafe operator (`?->`) | 8.0+ | Intermediate null checks |
| `str_contains()`, `str_starts_with()`, `str_ends_with()` | 8.0+ | `strpos($s, $n) !== false`, `strpos($s, $n) === 0`, `substr($s, -strlen($n)) === $n` |

### ComposerDependencies::ensure()

Pattern for auto-installing vendor dependencies on first activation:
```php
// In hooks.php constructor
$composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
if (file_exists($composerDepsPath)) {
    require_once $composerDepsPath;
    \KsfCommon\Utils\ComposerDependencies::ensure(__DIR__);
}
```

This runs `composer install --no-interaction --prefer-dist` in the module directory if `vendor/autoload.php` is missing. Requires `exec()` to be available.

---

## Audio/Video Items

FA 2.4 does not natively support audio or video items.
- No media columns on `stock_master`
- `mb_flag` only supports: M (manufactured), B (purchased), D (service), V (variation)
- Extensions must use custom tables (e.g. `0_product_media`) and filesystem storage

---

## Deployment

### UAT Bind Points

| Path | Purpose |
|------|---------|
| `~/Documents/<repo>` | Devel tree — development, testing, commits |
| `~/Documents/ksf_Infrastructure/fa_modules/<repo>` | UAT bind point — deployment target |
| `/var/www/html/devel/FrontAccounting/modules/<repo>` | actp/UAT live path |

### Deployment Workflow

1. Develop in devel tree
2. Commit and push to GitHub
3. Copy/sync to Infrastructure bind point:
   ```
   cp -r ~/Documents/<repo>/* ~/Documents/ksf_Infrastructure/fa_modules/<repo>/
   ```
4. On live server: `git pull` in the modules directory
5. `composer install --no-dev --no-interaction --prefer-dist`
6. Activate via Setup → Modules

---

## FA Patches Applied

### items.php Hook Patch

File: `ksf_Infrastructure/fa_modules/inventory/manage/items.php`

Adds four hook calls to vanilla FA's `items.php`:
- `hook_invoke_all('item_display_tab_headers', $tabs, $stockId)` — add custom tabs
- `hook_invoke_all('item_display_tab_content', $stockId, $selectedTab)` — render tab content
- `hook_invoke_all('post_item_write', $itemData, $stockId)` — save custom data
- `hook_invoke_all('pre_item_delete', $stockId)` — clean up custom data
