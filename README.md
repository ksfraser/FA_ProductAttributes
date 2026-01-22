# FA_ProductAttributes

FrontAccounting-compatible Product Attributes module.

## Goal
Maintain a canonical, ordered dictionary of product attributes (the “royal order of adjectives”) and (optionally) attach them to products.

- Level 1: attribute categories (e.g. `color`, `size_alpha`, `size_numeric`)
- Level 2: attribute values/adjectives (e.g. `red`, `xl`, `34`)

## Admin UI
- Standalone: `public/index.php`
- FrontAccounting wrapper: `product_attributes_admin.php`

### Features
- **Categories Management**: Create, edit, delete attribute categories with Royal Order sequencing
- **Values Management**: Add, edit, delete attribute values within categories
- **Assignments Management**: Associate attributes with products
- **Table Actions**: Each table row includes Edit and Delete buttons with confirmation dialogs- **Intelligent Deletion**: 
  - Permanently delete unused categories/values when safe
  - Deactivate items in use to preserve data integrity
  - Cascade delete removes categories and all related values- **Validation**: Prevents deletion of items currently in use by products
- **Form Pre-filling**: Edit buttons load existing data into forms for easy modification
- **Data Integrity**: Edit operations properly update existing records instead of creating duplicates
- **UI Consistency**: Delete actions use JavaScript links consistent with FrontAccounting patterns

### API
RESTful API endpoints for external integration:
- `GET/POST/PUT/DELETE /api/categories` - Manage categories
- `GET/POST/PUT/DELETE /api/categories/{id}/values` - Manage values
- `GET/POST/PUT/DELETE /api/products/{stockId}/assignments` - Manage product assignments

All endpoints return JSON responses with proper error handling.

## Requirements

### Business Requirements

#### BR1: Items Screen Integration
**Status: IMPLEMENTED** ✅
- Product Attributes tab added to Items screen via hook system
- Shows assigned categories, values, and variation counts
- Minimal changes to core `items.php` (only ~10 lines)
- Extensible hook system for future enhancements

#### BR1.1: Product Relationship Table
**Status: PENDING** ⏳
- Need to implement product-to-attribute relationship storage
- Will enable individual product attribute assignments beyond category-level
- Required for full WooCommerce-style functionality

#### BR2: Royal Order Dictionary
**Status: IMPLEMENTED** ✅
- Maintain canonical ordering of product attributes
- Categories and values maintain proper sequence
- RoyalOrderHelper utility class enforces ordering rules

#### BR3: Admin Interface
**Status: IMPLEMENTED** ✅
- Standalone admin interface for attribute management
- Integrated into FA's stock management section
- Full CRUD operations for categories, values, and assignments

#### BR4: Category-to-Product Assignments
**Status: IMPLEMENTED** ✅
- Assign attribute categories to products
- Automatic variation generation based on category assignments
- Variation counts displayed in Items screen

#### BR4.5: Variation Generation
**Status: IMPLEMENTED** ✅
- Generate product variations from assigned attribute categories
- Combinatorial logic for multiple attribute categories
- Variation management through dedicated action handler

### Technical Requirements

#### TR1: Hook System Architecture
**Status: IMPLEMENTED** ✅
- Lightweight hook system similar to WordPress/SuiteCRM
- Minimal core file modifications
- Extensible for other modules and future features
- Hook points: `item_display_tabs`, `pre_item_write`, `pre_item_delete`

#### TR2: SOLID Principles
**Status: IMPLEMENTED** ✅
- Single Responsibility: RoyalOrderHelper, HookManager, etc.
- Dependency Inversion: Interface-based database adapters
- Comprehensive unit test coverage (73 tests, 241 assertions)

#### TR3: PSR-4 Autoloading
**Status: IMPLEMENTED** ✅
- Proper namespace structure
- Composer-based autoloading
- Modular architecture with clear separation of concerns

#### TR4: Database Schema
**Status: IMPLEMENTED** ✅
- Programmatic schema management via SchemaManager
- Tables: categories, values, assignments
- Foreign key relationships and constraints
- Migration-safe updates

### Integration Points

#### IP1: FrontAccounting Items Screen
- **Method**: Hook system integration
- **Changes Required**: ~10 lines in `items.php`
- **Benefits**: Non-intrusive, extensible, future-proof

#### IP2: FrontAccounting Module System
- **Method**: Standard FA hooks class extension
- **Features**: Automatic installation, database schema creation, security integration

### Future Extensibility

The hook system enables easy addition of:
- Product photos management
- Shipping attributes
- Custom product fields
- Third-party integrations
- Additional admin screens

All while maintaining minimal core file modifications.

