# Minimal Changes to items.php for Product Attributes Integration

To integrate Product Attributes into the Items screen with minimal changes, add the following modifications to your FrontAccounting `inventory/items.php` file:

## Required Changes

### 1. Include Hook System (Add near the top of the file, after includes)

```php
// Include Product Attributes hook system
$module_path = $path_to_root . '/modules/FA_ProductAttributes';
if (file_exists($module_path . '/fa_hooks.php')) {
    require_once $module_path . '/fa_hooks.php';
    $hooks = fa_hooks();
}
```

### 2. Add Hook Calls in the Item Display Section

Find the section where item tabs are displayed (usually around line 400-600, look for tab handling code) and add:

```php
// Get tabs from hooks
$item_tabs = isset($hooks) ? $hooks->call_hook('item_display_tabs', [], $stock_id) : [];
```

Then modify the tab display logic to include hooked tabs:

```php
// Display standard tabs
// ... existing tab code ...

// Display hooked tabs
if (!empty($item_tabs)) {
    foreach ($item_tabs as $tab_key => $tab_data) {
        echo '<div id="' . $tab_key . '_tab" class="tab-content">';
        echo $tab_data['content'];
        echo '</div>';
    }
}
```

### 3. Add Hook Calls for Save Operations

Find the item save/update logic (usually in the POST handling section) and add:

```php
// Call pre-save hooks
if (isset($hooks)) {
    $item_data = $hooks->call_hook('pre_item_write', $item_data, $stock_id);
}
```

### 4. Add Hook Calls for Delete Operations

Find the item deletion logic and add:

```php
// Call pre-delete hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_delete', $stock_id);
}
```

## Benefits of This Approach

1. **Minimal Core Changes**: Only ~10 lines added to items.php
2. **Extensible**: Other modules can register hooks for the same points
3. **Non-Intrusive**: Core functionality remains unchanged
4. **Future-Proof**: Easy to add more hooks for additional features (photos, shipping, etc.)

## Hook Points Available

- `item_display_tabs`: Add custom tabs to item display
- `pre_item_write`: Modify item data before saving to database
- `pre_item_delete`: Handle cleanup before item deletion

## Example Integration

After these changes, the Product Attributes module will automatically:
- Add a "Product Attributes" tab showing assigned categories and values
- Display variation counts for each category
- Handle any product-specific attribute data during save/delete operations

This approach enables the WooCommerce-style functionality you requested while keeping core FA files largely untouched.