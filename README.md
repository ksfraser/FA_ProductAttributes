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
- **Table Actions**: Each table row includes Edit and Delete buttons with confirmation dialogs
- **Validation**: Prevents deletion of items currently in use by products
- **Form Pre-filling**: Edit buttons load existing data into forms for easy modification
- **Data Integrity**: Edit operations properly update existing records instead of creating duplicates
- **UI Consistency**: Delete actions use JavaScript links consistent with FrontAccounting patterns

### API
RESTful API endpoints for external integration:
- `GET/POST/PUT/DELETE /api/categories` - Manage categories
- `GET/POST/PUT/DELETE /api/categories/{id}/values` - Manage values
- `GET/POST/PUT/DELETE /api/products/{stockId}/assignments` - Manage product assignments

All endpoints return JSON responses with proper error handling.

## Dev
Install composer dependencies in `composer-lib`:

- `cd composer-lib`
- `composer install`

Note: Composer may prompt for a GitHub token due to API rate limits; a read-only token for public repos is sufficient.

## Development Branches

- `main`: Current stable development branch
- `db-adapters-split-experiment`: Attempted refactoring to extract generic DB adapters into separate `ksf_ModulesDAO` library. Currently not working due to dependency management issues. See branch for details.

