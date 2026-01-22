# FA_ProductAttributes

A comprehensive Product Attributes module for FrontAccounting that enables WooCommerce-style product variations through category-based attribute assignments.

## Features

- **Category-based Attributes**: Organize product attributes into logical categories (Color, Size, Material, etc.)
- **Product Variations**: Automatically generate all possible product combinations
- **Flexible Assignment**: Assign attributes at individual product level or category level
- **Royal Order Sorting**: Maintain consistent attribute display order
- **Hook-based Integration**: Minimal changes to core FrontAccounting files
- **Admin Interface**: Complete web-based management interface

## Installation

### Prerequisites

- FrontAccounting 2.3.22 or later
- PHP 7.3+
- MySQL 5.7+ or MariaDB 10.0+
- Composer (for dependency management)

### Step 1: Download the Module

Since this is a GitHub repository (not available on Packagist), you need to download it manually:

```bash
# Clone the repository into your FA modules directory
cd /path/to/frontaccounting/modules
git clone https://github.com/yourusername/FA_ProductAttributes.git FA_ProductAttributes

# Or download the ZIP and extract to modules/FA_ProductAttributes
```

### Step 2: Install Dependencies

```bash
cd /path/to/frontaccounting/modules/FA_ProductAttributes
composer install
```

### Step 3: Database Setup

The module will automatically create its database schema during installation, but you can also run the SQL manually:

```bash
mysql -u your_username -p your_database < sql/schema.sql
mysql -u your_username -p your_database < sql/seed.sql
```

### Step 4: Activate the Module

1. Log into FrontAccounting as an administrator
2. Go to **Setup → Install/Update Modules**
3. Find "Product Attributes" in the module list
4. Click **Activate**

### Step 5: Integrate with Items Screen

To enable product attributes in the Items screen, you need to make minimal changes to `inventory/items.php`. Add the following code:

#### Add Hook System Include (near the top of items.php, after other includes)

```php
// Include Product Attributes hook system
$module_path = $path_to_root . '/modules/FA_ProductAttributes';
if (file_exists($module_path . '/fa_hooks.php')) {
    require_once $module_path . '/fa_hooks.php';
    $hooks = fa_hooks();
}
```

#### Add Tab Display Support (in the item display section)

Find where tabs are displayed and add:

```php
// Get tabs from hooks
$item_tabs = isset($hooks) ? $hooks->call_hook('item_display_tabs', [], $stock_id) : [];

// Display hooked tabs
if (!empty($item_tabs)) {
    foreach ($item_tabs as $tab_key => $tab_data) {
        echo '<div id="' . $tab_key . '_tab" class="tab-content">';
        echo $tab_data['content'];
        echo '</div>';
    }
}
```

#### Add Save Operation Hooks (in the POST handling section)

```php
// Call pre-save hooks
if (isset($hooks)) {
    $item_data = $hooks->call_hook('pre_item_write', $item_data, $stock_id);
}
```

#### Add Delete Operation Hooks (in the delete handling section)

```php
// Call pre-delete hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_delete', $stock_id);
}
```

## Usage

### Managing Categories

1. Go to **Inventory → Product Attributes → Categories**
2. Create attribute categories (Color, Size, Material, etc.)
3. Set display order and activation status

### Managing Values

1. Go to **Inventory → Product Attributes → Values**
2. Add values to categories (Red, Blue, Green for Color)
3. Set sort order for consistent display

### Assigning to Products

1. Open any item in **Inventory → Items**
2. Click the **Product Attributes** tab
3. Assign categories and specific values to the product
4. The system will show possible variation counts

### Category-Level Assignments

1. Go to **Inventory → Product Attributes → Category Assignments**
2. Assign entire categories to products (all sizes, all colors)
3. Individual assignments override category assignments

## API Reference

### Hook Points

- `item_display_tabs`: Add custom tabs to item display
- `pre_item_write`: Modify item data before saving
- `pre_item_delete`: Handle cleanup before deletion

### Classes

- `ProductAttributesDao`: Data access layer
- `ActionHandler`: Business logic dispatcher
- Various Action classes for specific operations

## Development

```bash
# Run tests
cd composer-lib
php phpunit.phar

# Run specific test suite
php phpunit.phar tests/ProductAttributesDaoTest.php
```

## Troubleshooting

### Module Not Appearing

- Ensure the module directory is `modules/FA_ProductAttributes`
- Check file permissions
- Verify composer dependencies are installed

### Database Errors

- Check database connection settings
- Ensure user has CREATE TABLE permissions
- Run schema.sql manually if needed

### Items Integration Not Working

- Verify hooks.php changes are correct
- Check that fa_hooks.php is included
- Ensure $stock_id is available in the scope

## License

MIT License - see LICENSE file for details.