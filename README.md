# FA_ProductAttributes

FrontAccounting-compatible Product Attributes module.

## Goal
Maintain a canonical, ordered dictionary of product attributes (the “royal order of adjectives”) and (optionally) attach them to products.

- Level 1: attribute categories (e.g. `color`, `size_alpha`, `size_numeric`)
- Level 2: attribute values/adjectives (e.g. `red`, `xl`, `34`)

## Admin UI
- Standalone: `public/index.php`
- FrontAccounting wrapper: `product_attributes_admin.php`

## Dev
Install composer dependencies in `composer-lib`:

- `cd composer-lib`
- `composer install`

Note: Composer may prompt for a GitHub token due to API rate limits; a read-only token for public repos is sufficient.

## Development Branches

- `main`: Current stable development branch
- `db-adapters-split-experiment`: Attempted refactoring to extract generic DB adapters into separate `ksf_ModulesDAO` library. Currently not working due to dependency management issues. See branch for details.

