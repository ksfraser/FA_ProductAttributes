# FA_ProductAttributes Plugin Development Guide

## Overview

The FA_ProductAttributes module supports plugin-based extensions that can add new tabs to the items.php interface. Plugins can extend product attribute functionality without modifying core code.

## Plugin Architecture

Plugins are loaded dynamically using the PluginLoader system and can hook into various extension points:

- **Tab Headers**: Add new tabs to the items interface
- **Tab Content**: Provide content for plugin tabs
- **Save/Delete Operations**: Extend save and delete functionality
- **Action Handling**: Handle custom form actions

## Plugin Structure

Create your plugin as a PHP class that implements the required hooks. Plugins should be placed in the `plugins/` directory and follow PSR-4 autoloading conventions.

### Basic Plugin Class Structure

```php
<?php

namespace YourPluginNamespace;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class YourPlugin
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    // Hook implementations go here
}
```

## Adding Tab Headers

### Method 1: Using the item_display_tab_headers Hook

Implement the `item_display_tab_headers` hook in your plugin to add new tabs:

```php
/**
 * FA hook: Add plugin tabs to the items interface
 * @param array $tabs Current tab collection
 * @return array Modified tab collection
 */
public function item_display_tab_headers($tabs)
{
    // Get current stock_id (available in $_POST or $_GET)
    $stock_id = $_POST['stock_id'] ?? '';

    // Add your plugin tab
    // Format: array(title, stock_id_or_null)
    // Use null to disable tab if user lacks access
    $tabs['product_attributes_your_plugin'] = array(
        _('Your Plugin Name'),
        user_check_access('SA_PRODUCTATTRIBUTES') ? $stock_id : null
    );

    return $tabs;
}
```

### Tab Naming Convention

- **Prefix**: All plugin tabs must start with `product_attributes_`
- **Suffix**: Use descriptive names like `dimensions`, `variations`, `specifications`
- **Examples**:
  - `product_attributes_dimensions`
  - `product_attributes_variations`
  - `product_attributes_specifications`

### Access Control

Always check for `SA_PRODUCTATTRIBUTES` access before enabling tabs:

```php
$tabs['product_attributes_your_plugin'] = array(
    _('Your Plugin'),
    user_check_access('SA_PRODUCTATTRIBUTES') ? $stock_id : null
);
```

## Providing Tab Content

### Method 1: Using the attributes_tab_content Filter Hook

The recommended approach is to use the `attributes_tab_content` filter:

```php
/**
 * Provide content for plugin tabs
 * @param string $content Current content (usually empty for plugin tabs)
 * @param string $stock_id The current item stock ID
 * @param string $selected_tab The selected tab identifier
 * @return string HTML content for the tab
 */
public function get_tab_content($content, $stock_id, $selected_tab)
{
    // Only handle our specific tab
    if ($selected_tab !== 'product_attributes_your_plugin') {
        return $content; // Return unchanged
    }

    // Generate your tab content
    $output = '<h3>' . _('Your Plugin Interface') . '</h3>';

    // Add your form fields, tables, etc.
    $output .= $this->render_plugin_interface($stock_id);

    return $output;
}
```

### Method 2: Dedicated UI Class

Create a dedicated UI class that implements a `render()` method:

```php
<?php

namespace YourPluginNamespace\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class YourPluginTab
{
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function render()
    {
        // Output your HTML directly
        echo '<h3>' . _('Your Plugin Interface') . '</h3>';

        // Add form, table, etc.
        start_table(TABLESTYLE2);
        // ... your UI code
        end_table();
    }
}
```

The TabDispatcher will automatically instantiate `YourPluginTab` class when the `product_attributes_your_plugin` tab is selected.

### Content Rendering Best Practices

1. **Use FA UI Functions**: Use FA's UI functions like `start_table()`, `start_form()`, etc.
2. **Handle Stock ID**: Always check for valid `$stock_id` before rendering
3. **Error Handling**: Wrap rendering in try-catch blocks
4. **Internationalization**: Use `_()` function for translatable strings
5. **Security**: Validate all input and escape output

## Handling Save/Delete Operations

### Extending Save Operations

```php
/**
 * Handle plugin data during item save
 * @param array $item_data The item data being saved
 * @param string $stock_id The item stock ID
 * @return array Modified item data
 */
public function handle_save($item_data, $stock_id)
{
    // Save your plugin-specific data
    $this->save_plugin_data($stock_id, $_POST);

    return $item_data; // Return potentially modified data
}
```

### Extending Delete Operations

```php
/**
 * Handle plugin cleanup during item deletion
 * @param string $stock_id The item stock ID being deleted
 */
public function handle_delete($stock_id)
{
    // Clean up your plugin data
    $this->delete_plugin_data($stock_id);
}
```

## Action Handling

### Custom Form Actions

```php
/**
 * Handle custom plugin actions
 * @param string $action The action identifier
 * @param array $request_data POST/GET data
 * @return string|null Success message or null if not handled
 */
public function handle_action($action, $request_data)
{
    switch ($action) {
        case 'your_plugin_action':
            // Handle your custom action
            $this->process_custom_action($request_data);
            return _('Action completed successfully');

        default:
            return null; // Not handled by this plugin
    }
}
```

## Plugin Registration

### Method 1: Hook Registration

Register your plugin hooks in your plugin's main file:

```php
// In your plugin's main hooks file
function register_plugin_hooks()
{
    global $path_to_root;
    require_once $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';

    $hooks = fa_hooks();

    // Register hook handlers
    $hooks->add_filter('attributes_tab_content', 'your_plugin_get_tab_content');
    $hooks->add_action('attributes_save', 'your_plugin_handle_save');
    $hooks->add_action('attributes_delete', 'your_plugin_handle_delete');
    $hooks->add_filter('fa_product_attributes_handle_action', 'your_plugin_handle_action');
}

// Register during module initialization
register_plugin_hooks();
```

### Method 2: Class-based Registration

If your plugin is a class, register methods directly:

```php
$plugin = new YourPlugin($dao);

// Register hooks
$hooks->add_filter('attributes_tab_content', [$plugin, 'get_tab_content']);
$hooks->add_action('attributes_save', [$plugin, 'handle_save']);
$hooks->add_action('attributes_delete', [$plugin, 'handle_delete']);
```

## Complete Plugin Example

```php
<?php

namespace ExamplePlugin;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class DimensionsPlugin
{
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Add dimensions tab to items interface
     */
    public function item_display_tab_headers($tabs)
    {
        $stock_id = $_POST['stock_id'] ?? '';
        $tabs['product_attributes_dimensions'] = array(
            _('Dimensions'),
            user_check_access('SA_PRODUCTATTRIBUTES') ? $stock_id : null
        );
        return $tabs;
    }

    /**
     * Provide dimensions tab content
     */
    public function get_tab_content($content, $stock_id, $selected_tab)
    {
        if ($selected_tab !== 'product_attributes_dimensions') {
            return $content;
        }

        $output = '<h3>' . _('Product Dimensions') . '</h3>';

        start_form();
        start_table(TABLESTYLE2);

        // Length field
        text_row(_('Length'), 'length', '', 10, 10);

        // Width field
        text_row(_('Width'), 'width', '', 10, 10);

        // Height field
        text_row(_('Height'), 'height', '', 10, 10);

        // Unit field
        text_row(_('Unit'), 'unit', 'cm', 10, 10);

        end_table(1);

        // Submit button
        submit_center('save_dimensions', _('Save Dimensions'));

        end_form();

        return $output;
    }

    /**
     * Save dimensions data
     */
    public function handle_save($item_data, $stock_id)
    {
        if (isset($_POST['length'])) {
            // Save dimensions to your custom table
            $this->save_dimensions($stock_id, $_POST);
        }
        return $item_data;
    }

    /**
     * Clean up dimensions on delete
     */
    public function handle_delete($stock_id)
    {
        $this->delete_dimensions($stock_id);
    }

    // Helper methods...
    private function save_dimensions($stock_id, $data) { /* ... */ }
    private function delete_dimensions($stock_id) { /* ... */ }
}
```

## Plugin Loading

Plugins are automatically loaded when the module initializes. Ensure your plugin:

1. Follows PSR-4 naming conventions
2. Is placed in the correct directory structure
3. Has proper autoloading configuration
4. Registers its hooks during initialization

## Testing Your Plugin

1. **Install** your plugin in the FA modules directory
2. **Activate** the plugin through FA's module management
3. **Test tab visibility** in items.php
4. **Test tab content** by clicking your tab
5. **Test save/delete** operations
6. **Verify access control** with different user roles

## Troubleshooting

### Tab Not Appearing
- Check that `item_display_tab_headers` returns the tab array
- Verify access control allows the tab
- Ensure tab name starts with `product_attributes_`

### Content Not Displaying
- Check that `attributes_tab_content` filter returns content
- Verify the `$selected_tab` parameter matches your tab name
- Check for PHP errors in your content generation

### Actions Not Working
- Ensure action handlers are properly registered
- Check that form data is being passed correctly
- Verify database operations complete successfully

## Advanced Topics

### Database Schema
Plugins can define their own database tables. Create migration scripts and register them in your plugin's activation hook.

### Internationalization
Use FA's `_()` function for all user-facing strings. Create `.po` files for translations.

### JavaScript Integration
Include custom JavaScript files and register them with FA's script loading system.

### API Integration
Plugins can integrate with external APIs for additional functionality.

---

This guide covers the core concepts for developing FA_ProductAttributes plugins. For more advanced features, refer to the FA core documentation and the module's source code.</content>
<parameter name="filePath">c:\Users\prote\Documents\software-devel\FA_ProductAttributes\Project Docs\Plugin_Development_Guide.md