# Orphaned Functional Requirements

This document contains functional requirements that have been moved from the Core module specification because they belong to plugins that have not yet been defined or specified.

## Attribute Category Management (Unspecified Plugin)
- Create, edit, and delete attribute categories (Color, Size, Material, etc.)
- Define category properties including display names and sort orders
- Manage category activation/deactivation status
- Support for category grouping and organization

## Attribute Value Management (Unspecified Plugin)
- Add, modify, and remove values within categories
- Support for value slugs for programmatic access
- Value sorting and ordering within categories
- Bulk value operations and imports

## Product Assignment Interface (Unspecified Plugin)
- Assign attributes to individual products through FA's item editing interface
- Support for multiple attribute assignments per product
- Visual attribute selection with category grouping
- Validation of attribute combinations and constraints

## Product Attribute Assignment (Unspecified Plugin)
- **Trigger**: User navigates to Inventory > Items and selects a product.
- **Process**:
  1. Display existing product details.
  2. Add "Product Attributes" TAB via hook system.
  3. Show generic attribute assignment interface.
  4. Allow selection of attribute categories and values.
  5. Display assigned attributes in table format.
  6. Show "Variations" column indicating combinatorial possibilities.
- **Output**: Attributes associated with product, available for plugin extensions.

## Attribute Association (Unspecified Plugin)
- **Trigger**: User on Product Attributes TAB.
- **Process**:
  1. Fetch available categories and values from admin-managed data.
  2. Allow selection via dropdowns.
  3. Validate selections against existing data.
  4. Save to product_attributes table.
- **Output**: Attributes linked to product.

## Admin Screen for Attribute Management (Unspecified Plugin)
- **Trigger**: User navigates to Inventory > Stock > Product Attributes.
- **Process**:
  1. Display categories in a sortable table (by Name or Royal Order).
  2. Table includes columns: Code (Slug), Label, Description, Sort (Royal Order), Active, Actions (Edit/Delete).
  3. Sort order displays as "3 - Size" format using Royal Order text labels.
  4. Display values in a separate tab/table with columns: Value, Slug, Sort Order, Active, Actions (Edit/Delete).
  5. Display assignments in a separate tab/table with columns: Category, Value, Slug, Sort Order, Actions (Delete).
  6. Edit buttons pre-fill forms with existing data and change button text to "Update". Edit operations update existing records rather than creating duplicates.
  7. Delete buttons show confirmation dialogs and perform different actions based on usage:
     - If the item is NOT in use by products: Permanently delete from database
     - If the item IS in use by products: Deactivate (soft delete) to preserve data integrity
     - For categories: When hard deleting, all related values are also deleted
     - Delete links use GET requests with confirmation dialogs.
  8. Provide CRUD forms for categories and variables with validation.
  9. Royal Order dropdown provides predefined options (Quantity, Opinion, Size, Age, Shape, Color, Proper adjective, Material, Purpose).
- **Output**: Updated categories and variables in DB.