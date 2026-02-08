# FA_ProductAttributes

A core Product Attributes module for FrontAccounting that provides generic attribute infrastructure and enables extensible attribute-based functionality through plugins.

## Repository Structure & Inter-relationships

This repository contains a modular FA Product Attributes system designed for scalable development and deployment:

### Core Components

- **`FA_ProductAttributes`** (this repo): Shared composer library containing core attribute infrastructure
  - Generic attribute categories, values, and assignments
  - Database schema management
  - Common services and utilities
  - Used by all FA modules requiring attribute functionality

- **`FA_ProductAttributes_Core`**: Main FA module providing the admin interface
  - Requires: `ksfraser/fa-product-attributes` (shared library)
  - Provides: Web-based attribute management interface
  - Integrates with FA's item management system

- **`FA_ProductAttributes_Variations`**: Plugin module for product variations
  - Requires: `ksfraser/fa-product-attributes` (shared library)
  - Depends on: `FA_ProductAttributes_Core` (for loading order)
  - Provides: Parent-child product relationships, variation generation

- **`FA_ProductAttributes_Categories`**: Plugin module for category-based attributes
  - Requires: `ksfraser/fa-product-attributes` (shared library)  
  - Depends on: `FA_ProductAttributes_Core` (for loading order)
  - Provides: Category-level attribute inheritance

### Deployment Architecture

```
FrontAccounting modules/
├── FA_ProductAttributes_Core/     # Main module (loads first)
│   ├── vendor/
│   │   └── ksfraser/fa-product-attributes/  # Shared library
│   └── _init/config                        # FA module config
├── FA_ProductAttributes_Variations/        # Plugin module
│   ├── vendor/
│   │   └── ksfraser/fa-product-attributes/  # Shared library
│   └── _init/config                        # Depends on Core
└── FA_ProductAttributes_Categories/        # Plugin module
    ├── vendor/
    │   └── ksfraser/fa-product-attributes/  # Shared library
    └── _init/config                        # Depends on Core
```

### Design Principles

- **Shared Library**: Common code lives in `ksfraser/fa-product-attributes` composer package
- **Module Independence**: Each FA module is self-contained with its own vendor dependencies
- **Loading Order**: Core module loads first, plugins depend on it for proper initialization
- **Plugin Architecture**: Clean separation between generic attributes (core) and domain-specific features (plugins)

See `Project Docs/` for detailed business requirements, functional specifications, and architecture documentation.

See `AGENTS-TECH.md` for technical implementation details and agent-specific requirements.

### Core Module Responsibilities

The FA_ProductAttributes core module provides:

- **Attribute Categories & Values**: Generic management of attribute categories and their values
- **Assignment System**: Linking products to attributes (both individual and category-level)
- **STOCK Application Integration**: Hooks into FA's items.php to add attribute functionality
- **Extension Points**: Hook system allowing plugins to extend functionality

### Plugin Architecture

The module supports plugins that can add specific attribute functionality:

- **FA_ProductAttributes_Variations**: Adds WooCommerce-style product variations
  - Parent-child product relationships
  - Automatic variation generation from attribute combinations
  - Retroactive pattern analysis for existing products
  - Royal Order attribute sequencing
  - Variation-based pricing rules (fixed, percentage, combined adjustments)
  - Bulk operations for variation management
- **Future Plugins**: Product dimensions, tags, custom attributes, etc.

### Recent Changes (v1.x)

- **Plugin Architecture Refactoring**: Moved variation-specific services to FA_ProductAttributes_Variations plugin
- **Service Separation**: RetroactiveApplicationService moved from core to variations plugin
- **Clean Separation**: Core module provides generic attributes, plugins add domain-specific functionality
- **Hook Extensions**: Plugin system uses fa-hooks for clean integration

### Database Schema

The core module manages the foundational attribute structure:

- `product_attribute_categories`: Attribute categories (Color, Size, etc.)
- `product_attribute_values`: Values within categories (Red, Blue, XL, etc.)

Plugins can extend the core with additional tables and functionality as needed.
## Extended Hook System Architecture

The Product Attributes module now includes an extended hook system that enables scalable module development and cross-module integration for FrontAccounting.

### Key Components

1. **Container Classes**: Generic containers for managing different types of FA data structures
   - `ArrayContainer`: Abstract base for array-based data
   - `TabContainer`: Specialized for tab management
   - `MenuContainer`: Specialized for menu items

2. **Hook Registry**: Dynamic hook point registration system
   - Modules can register their own hook points
   - Other modules can extend these hook points
   - Priority-based execution control

3. **Version Abstraction**: Automatic handling of FA version differences
   - Transparent adaptation between FA 2.3.x and 2.4.x+
   - Future-proof compatibility

4. **Factory Pattern**: Centralized container creation
   - `ContainerFactory` creates appropriate containers by context
   - Type-safe object instantiation

### Cross-Module Integration

The extended architecture enables modules to integrate with each other:

```php
// Supplier module registers hook point
$hooks->registerHookPoint('supplier_tabs', 'supplier_module', function($tabs) {
    $tabs->createTab('general', 'General', 'suppliers.php');
    return $tabs->toArray();
});

// Product Attributes extends supplier tabs
$hooks->registerExtension('supplier_tabs', 'product_attributes', function($tabs) {
    $tabs->createTab('attributes', 'Product Attributes', 'supplier_attributes.php');
}, 10);
```

### Benefits

- **Scalability**: Easy to add new modules and extensions
- **Version Agnostic**: Automatic FA version compatibility
- **Decoupling**: Modules don't need to know about each other
- **Type Safety**: Object-oriented design with proper validation
- **Extensible**: Supports any FA module (Suppliers, Customers, Inventory, etc.)
## Installation

### Prerequisites

- FrontAccounting 2.3.22 or later
- PHP 7.3+
- MySQL 5.7+ or MariaDB 10.0+
- Composer (for dependency management)

### Step 1: Install FA-Hooks Module (Required)

The Product Attributes module requires the FA-Hooks system for integration:

```bash
# Install FA-Hooks as a separate module (provides generic hook system for all modules)
cd /path/to/frontaccounting/modules
git clone https://github.com/ksfraser/FA_Hooks.git 0fa-hooks
cd 0fa-hooks
composer install  # Run this BEFORE activating in FA

# Activate 0FA-Hooks in FA (Setup → Install/Update Modules)
```

**Note**: FA-Hooks should be installed once and provides the hook system for ALL your FA modules. The directory is named `0fa-hooks` to ensure it loads first alphabetically.

**Production Deployment**: For production environments, install FA-Hooks as a separate module (not as a submodule). The Product Attributes module will automatically check for this dependency during installation.

### Step 2: Download the Product Attributes Module

```bash
# Clone the repository into your FA modules directory
cd /path/to/frontaccounting/modules
git clone https://github.com/ksfraser/FA_ProductAttributes.git FA_ProductAttributes

# Or download the ZIP and extract to modules/FA_ProductAttributes
```

### Step 3: Install Dependencies

```bash
cd /path/to/frontaccounting/modules/FA_ProductAttributes
composer install
```

### Step 4: Database Setup

The module will automatically create its database schema during installation, but you can also run the SQL manually:

```bash
mysql -u your_username -p your_database < sql/schema.sql
mysql -u your_username -p your_database < sql/seed.sql
```

### Step 5: Activate the Module

1. Log into FrontAccounting as an administrator
2. Go to **Setup → Install/Update Modules**
3. Find "Product Attributes" in the module list
4. Click **Activate**

### Step 6: Integrate with Items Screen

To enable product attributes in the Items screen, you need to make minimal changes to `inventory/items.php`. Add the following code:

#### Add Hook System Include (near the top of items.php, after other includes)

```php
// Include generic FA-Hooks system (installed as separate module)
$hooks_path = $path_to_root . '/modules/fa-hooks/src/Ksfraser/FA_Hooks/HookManager.php';
if (file_exists($hooks_path)) {
    require_once $hooks_path;
    $hooks = new Ksfraser\FA_Hooks\HookManager();
}
```

**Alternative**: If you prefer to load hooks via the Product Attributes module:

```php
// Include via Product Attributes module (includes FA-Hooks as dependency)
$module_path = $path_to_root . '/modules/FA_ProductAttributes';
if (file_exists($module_path . '/fa_hooks.php')) {
    require_once $module_path . '/fa_hooks.php';
    $hooks = fa_hooks(); // Returns global hook manager instance
}
```

#### Replace Tab Display Logic (in the item display section)

Find where tabs are displayed and replace the tabs array definition with:

```php
// Use object-based tab management with hooks
$tabCollection = $hooks->call_hook('item_display_tab_headers', null, $stock_id);
$tabs = $tabCollection ? $tabCollection->toArray() : [];
```

#### Replace Tab Content Switch (in the tab content display section)

Replace the tab content switch statement with:

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

#### Add Save Operation Hooks (in the POST handling section)

```php
// Call pre-save hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_write', $item_data, $stock_id);
}
```

#### Add Delete Operation Hooks (in the delete handling section)

```php
// Call pre-delete hooks
if (isset($hooks)) {
    $hooks->call_hook('pre_item_delete', $stock_id);
}
```

## Plugin Architecture

The FA_ProductAttributes core module provides extension points for plugins to add specific attribute functionality:

### Available Plugins

- **FA_ProductAttributes_Variations**: Adds WooCommerce-style product variations with parent-child relationships, pricing rules, and bulk operations
- **Future Plugins**: Product dimensions, tags, custom attributes, etc.

### Installing Plugins

1. Install the FA_ProductAttributes core module first
2. Install desired plugins as separate FA modules
3. Plugins automatically extend the core functionality through hooks

### Developing Plugins

Plugins can extend the core module by:

- Registering extensions to `attributes_tab_content` hook to add UI
- Registering extensions to `attributes_save` hook to handle data saving
- Registering extensions to `attributes_delete` hook to handle cleanup

Example plugin structure:
```
FA_ProductAttributes_PluginName/
├── composer.json (depends on FA_ProductAttributes)
├── hooks.php (extends core hooks)
├── src/Ksfraser/FA_ProductAttributes_PluginName/
└── README.md
```

## Usage

### Admin Interface Tabs

The Product Attributes module provides a comprehensive admin interface accessible via **Inventory → Product Attributes** with four main tabs:

#### Categories Tab
1. Go to **Inventory → Product Attributes → Categories**
2. Create attribute categories (Color, Size, Material, etc.)
3. Set display order and activation status

#### Values Tab
1. Go to **Inventory → Product Attributes → Values**
2. Add values to categories (Red, Blue, Green for Color)
3. Set sort order for consistent display

#### Assignments Tab
1. Go to **Inventory → Product Attributes → Assignments**
2. Assign entire categories to products (all sizes, all colors)
3. Create child products (variations) from parent products
4. Individual assignments override category assignments

#### Product Types Tab
1. Go to **Inventory → Product Attributes → Product Types**
2. Manage product type classifications (Simple, Variable, Variation)
3. Set up parent-child relationships for variations
4. Convert between product types while maintaining relationships

### Product Integration

#### Assigning to Products

1. Open any item in **Inventory → Items**
2. Click the **Product Attributes** tab
3. Assign categories and specific values to the product
4. The system will show possible variation counts

#### Creating Variations

1. In the **Assignments** tab, select a Variable product
2. Assign attribute categories to the product
3. Click **"Create Child Product"** to generate variations
4. Variations inherit category assignments from their parent

## API Reference

### Hook Points

- `item_display_tab_headers`: Receives `TabCollection` object, returns modified `TabCollection`
- `item_display_tab_content`: Provide content for specific tabs (receives $stock_id and $selected_tab parameters)
- `pre_item_write`: Modify item data before saving to database
- `pre_item_delete`: Handle cleanup before item deletion

### Extended Hook System

The module now supports the extended hook architecture for cross-module integration:

#### Dynamic Hook Registration

Modules can register their own hook points:

```php
// Register a hook point for other modules to extend
$hooks->registerHookPoint('supplier_tabs', 'supplier_module', function($tabs) {
    $tabs->createTab('general', 'General', 'suppliers.php');
    return $tabs->toArray();
}, ['description' => 'Supplier detail tabs']);
```

#### Hook Extensions

Other modules can extend registered hook points:

```php
// Extend supplier tabs with product attributes
$hooks->registerExtension('supplier_tabs', 'product_attributes', function($tabs) {
    $tabs->createTab('attributes', 'Product Attributes', 'supplier_attributes.php');
}, 10);
```

#### Container Classes

- **TabContainer**: Manages tabs for items, suppliers, customers, etc.
- **MenuContainer**: Manages menu items
- **ArrayContainer**: Generic container for custom data structures

### RESTful API Endpoints

The module provides a complete RESTful API for external integrations:

#### Categories API
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create new category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

#### Values API
- `GET /api/values` - List all values
- `GET /api/values?category_id={id}` - List values for category
- `POST /api/values` - Create new value
- `PUT /api/values/{id}` - Update value
- `DELETE /api/values/{id}` - Delete value

#### Assignments API
- `GET /api/assignments/{stock_id}` - Get assignments for product
- `POST /api/assignments` - Create assignment
- `DELETE /api/assignments/{id}` - Delete assignment

#### Product Types API
- `GET /api/product-types` - List all products with types
- `POST /api/product-types/update` - Update product types

### Classes

- `ProductAttributesDao`: Data access layer with 20+ methods
- `ActionHandler`: Business logic dispatcher for admin operations
- `ProductAttributesService`: Main service for UI and data operations
- Various Action classes: CreateChildAction, UpdateProductTypesAction, etc.
- UI Tab classes: CategoriesTab, ValuesTab, AssignmentsTab, ProductTypesTab

#### Extended Hook System Classes

- `HookManager`: Main hook system manager with extended capabilities
- `HookRegistry`: Dynamic hook point registration and extension management
- `FAVersionAdapter`: Version abstraction for different FA versions
- `TabDefinition`: Object representation of individual tabs
- `TabCollection`: Collection of tabs with version-aware toArray()
- `TabContainer`: Specialized container for tab management
- `MenuContainer`: Specialized container for menu management
- `ArrayContainer`: Abstract base class for array-based data structures
- `ContainerFactory`: Factory for creating appropriate container instances

## Development

### Testing

The module includes comprehensive test coverage with 140+ unit tests for the main module and additional tests for the extended hook system:

```bash
# Run main module tests
cd composer-lib
php vendor/bin/phpunit tests/

# Run extended hook system tests
cd ../fa-hooks
php test_containers.php  # Manual validation tests

# Run specific test suite
php vendor/bin/phpunit tests/ProductAttributesDaoTest.php

# Run with coverage report
php vendor/bin/phpunit tests/ --coverage-html coverage/
```

### Test Structure

- **Unit Tests**: Individual class testing with mocks
- **Integration Tests**: Database and service layer testing
- **Action Tests**: Admin operation testing
- **API Tests**: RESTful endpoint testing
- **Hook System Tests**: Extended architecture validation (ContainerTest.php)

### Test Results

✅ **All tests pass** - The extended hook system has been validated with:
- Container class functionality (ArrayContainer, TabContainer, MenuContainer)
- Hook registry dynamic registration and extensions
- Version abstraction across FA versions
- Object-based tab management
- Cross-module integration patterns

### Code Quality

- **PSR-4 Autoloading**: Standard PHP namespace structure
- **SOLID Principles**: Single Responsibility, Open/Closed, etc.
- **Dependency Injection**: Clean architecture with DI container
- **Comprehensive Error Handling**: Proper exception management
- **Input Validation**: All user inputs validated and sanitized
- **Object-Oriented Design**: Type-safe containers and version abstraction

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