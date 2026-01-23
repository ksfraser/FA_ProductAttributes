# Minimal Changes to items.php for Product Attributes Integration

To integrate Product Attributes into the Items screen with minimal changes, add the following modifications to your FrontAccounting `inventory/items.php` file:

## Required Changes

### 1. Include Hook System (Add near the top of the file, after includes)

```php
// Include Product Attributes hook system
$module_path = $path_to_root . '/modules/FA_ProductAttributes';
if (file_exists($module_path . '/hooks.php')) {
    require_once $module_path . '/hooks.php';
    $hooks = new Ksfraser\FA_Hooks\HookManager();
}
```

### 2. Add Hook Calls for Tab Headers

Find where the `$tabs` array is defined (usually near the top of the display section) and replace with:

```php
// Use object-based tab management with hooks
$tabCollection = $hooks->call_hook('item_display_tab_headers', null, $stock_id);
$tabs = $tabCollection ? $tabCollection->toArray() : [];
```

### 3. Replace Tab Content Switch with Generic Hook System

Instead of adding specific cases to the switch statement, replace the entire tab content handling with a generic hook system:

**Find the switch statement that handles tab content and replace it with:**

```php
// Generic hook-based tab content system
$content = $hooks->call_hook('item_display_tab_content', '', $stock_id, $selected_tab);

if (!empty($content)) {
    echo $content;
} else {
    // Fallback to original FA tab handling
    switch ($selected_tab) {
        case 'general':
            // Original general tab content
            break;
        case 'settings':
            // Original settings tab content
            break;
        default:
            // Default fallback
            break;
    }
}
```

**This approach:**
- Uses the new object-based TabCollection system
- Passes the `$selected_tab` variable to hooks
- Allows modules to handle any tab dynamically
- Maintains backward compatibility with existing FA tabs
- Keeps the core switch logic as fallback only

### 4. Add Hook Calls for Save Operations

Find the item save/update logic (usually in the POST handling section) and add:

```php
// Call pre-save hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_write', $item_data, $stock_id);
}
```

### 5. Add Hook Calls for Delete Operations

Find the item deletion logic and add:

```php
// Call pre-delete hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_delete', $stock_id);
}
```

## Benefits of This Approach

1. **Version Agnostic**: fa-hooks system handles tabs array format differences between FA versions
2. **Truly Generic Hook System**: No hardcoded module-specific cases in core FA files
3. **Minimal Core Changes**: Only ~15 lines added to items.php
4. **Proper FA Integration**: Works with FA's existing tab system without modification
5. **Extensible**: Other modules can register hooks for the same points
6. **Non-Intrusive**: Core functionality remains unchanged
7. **SRP Compliant**: Each hook has a single, clear responsibility
8. **Object-Oriented**: Uses type-safe objects instead of raw arrays

## Hook Points Available

- `item_display_tab_headers`: Receives `TabCollection` object, returns modified `TabCollection`
- `item_display_tab_content`: Provide content for specific tabs (receives $stock_id and $selected_tab parameters)
- `pre_item_write`: Modify item data before saving to database
- `pre_item_delete`: Handle cleanup before item deletion

## Extended Architecture Support

The new extended architecture also supports dynamic hook registration for broader module compatibility:

### Dynamic Hook Points

Modules can register their own hook points that other modules can extend:

```php
// Core module registers hook point
$hooks->registerHookPoint('supplier_tabs', 'supplier_module', function($tabs) {
    $tabs->createTab('general', 'General', 'suppliers.php');
    return $tabs->toArray();
});

// Extension module adds to it
$hooks->registerExtension('supplier_tabs', 'product_attributes', function($tabs) {
    $tabs->createTab('attributes', 'Product Attributes', 'attributes.php');
});
```

### Container Classes

The system provides specialized containers for different FA contexts:

- **TabContainer**: For managing tabs across different FA modules
- **MenuContainer**: For managing menu items
- **ArrayContainer**: Generic container for custom data structures

## Object-Based Tab Management

The fa-hooks system uses an object-oriented approach for tab management:

### TabDefinition
Represents a single tab with properties:
```php
$tab = new Ksfraser\FA_Hooks\TabDefinition('product_attributes', 'Product Attributes', $versionAdapter);
$tab->setOptions(['icon' => 'attributes.png']);
$array = $tab->toArray(); // Returns FA-version-appropriate array
```

### TabCollection
Manages multiple tabs:
```php
$collection = new Ksfraser\FA_Hooks\TabCollection($versionAdapter);
$collection->createTab('attributes', 'Attributes', 'attributes.php', ['required' => true]);
$faArray = $collection->toArray(); // Complete tabs array for FA
```

### Module Integration
Modules work with objects, not raw arrays:
```php
public function addTabHeaders(Ksfraser\FA_Hooks\TabCollection $collection, $stock_id) {
    $collection->createTab('my_feature', 'My Feature', 'feature.php');
    return $collection;
}
```

This provides better encapsulation, type safety, and version abstraction.

## Example Integration

After these changes, the Product Attributes module will automatically:
- Add "Product Attributes" to the tab headers array
- Display the appropriate content when the tab is selected (hooks system checks $selected_tab)
- Handle any product-specific attribute data during save/delete operations

## Version Compatibility

The fa-hooks system automatically handles differences in FrontAccounting's internal data structures between versions:

- **FA 2.3.x**: Simple tabs array format (`$tabs['key'] = 'Title'`)
- **FA 2.4.x**: Potentially more complex structures (handled transparently)
- **Future versions**: Automatic adaptation without module changes

Modules use a consistent API regardless of FA version - the hooks system translates between the module interface and FA's internal format.

### Implementation Details

The version abstraction is handled by:

1. **FAVersionAdapter**: Detects FA version and provides version-specific logic
2. **TabManager**: Abstracts tabs array operations across FA versions
3. **HookManager**: Intercepts tab-related hooks and applies version translation
4. **HookRegistry**: Manages dynamic hook points for cross-module integration
5. **ContainerFactory**: Creates appropriate container instances for different contexts

This ensures modules remain compatible as FA evolves, without requiring module updates for each FA version change.

## Extended Architecture Benefits

The new extended architecture provides additional capabilities:

### Cross-Module Integration
- Modules can register their own hook points (e.g., `supplier_tabs`, `customer_tabs`)
- Other modules can extend these hook points seamlessly
- Enables third-party module ecosystems

### Scalable Container System
- **TabContainer**: Specialized for tab management across FA modules
- **MenuContainer**: For menu item management
- **ArrayContainer**: Generic container for custom data structures

### Priority-Based Extensions
- Extensions execute in priority order (lower numbers first)
- Allows fine-grained control over extension execution sequence

### Type Safety
- Object-oriented design with proper type hints
- Compile-time error detection
- Better IDE support and code completion

## Testing

The extended architecture includes comprehensive tests:

```bash
# Run container tests
cd fa-hooks
php test_containers.php

# Run full test suite (when PHPUnit is configured)
php vendor/bin/phpunit tests/
```

All tests validate the object-oriented design, version abstraction, and cross-module integration capabilities.