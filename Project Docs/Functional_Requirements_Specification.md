# Functional Requirements Specification (FRS)

## Introduction
This document details the functional behavior of the Product Attributes module for FrontAccounting.

## Functional Requirements Details

### FR1: Items Screen Enhancement
- **Trigger**: User navigates to Inventory > Items and selects a product.
- **Process**:
  1. Display existing product details.
  2. Add "Product Attributes" TAB.
  3. Check if product is parent (parent flag = true); if yes, show TAB with:
     - Table showing all child variations with their attributes
     - "Create Variations", "Make Inactive", "Reactivate Variations", "Create Missing Variations" buttons
  4. If not parent, show:
     - "Assign Parent" dropdown with sanity check and force option
     - Table showing parent-child relationships if applicable
  5. "Make Inactive": Deactivate parent, default deactivate 0 stock variations, warn on stock >0.
  6. "Reactivate Variations": Rebuild combinations, activate inactive, prompt for missing.
  7. "Create Missing Variations": Generate missing combinations, create selected.
  8. On TAB click, load associated attributes from DB.
  9. Show list of attributes with add/remove options.
  10. Allow assignment of categories to the product (similar to WooCommerce variable products).
  11. Generate variations directly from the Items screen.
- **Output**: Updated product with attributes saved and variations created.

### FR1.1: Product Relationship Table
- **Trigger**: User views product lists or searches for products.
- **Process**:
  1. Display table showing product relationships with columns:
     - Stock ID, Description, Type (Simple/Parent/Variation), Parent Stock ID, Status
  2. Type indicators: Simple (no parent, no children), Parent (has children), Variation (has parent)
  3. Filter options to show only parents, only variations, or all products
  4. Quick actions: Navigate to parent, view all variations, etc.
  5. Visual hierarchy showing parent-child relationships
- **Output**: Clear view of product relationships and hierarchy.

### FR2: Attribute Association
- **Trigger**: User on Product Attributes TAB.
- **Process**:
  1. Fetch available categories and values from admin-managed data.
  2. Allow selection via dropdowns.
  3. Validate selections against existing data.
  4. Save to product_attributes table.
- **Output**: Attributes linked to product.

### FR3: Variation Product Creation
- **Trigger**: User (on parent product) attaches categories/values and clicks "Create Variations" on TAB.
- **Process**:
  1. Generate all combinations of selected attribute values, including new ones for existing product lines.
  2. Identify existing variations to avoid duplicates.
  3. Check "Copy Sales Pricing" option; if yes, retrieve and copy prices from master.
  4. For each new combination, create product:
     - Stock_id: Parent + abbreviations in Royal Order (e.g., XYZ-L-RED).
     - Description: Replace ${ATTRIB_CLASS} placeholders in parent description with long attribute names (e.g., "Coat - ${Size} ${Color}" becomes "Coat - Large Red").
     - Copy other fields from master, including prices if checked.
     - Set parent flag to false, parent_stock_id to master's stock_id.
  5. Save to DB.
  6. Display list of created variations.
- **Output**: New child products created with optional price copying.

### FR4: Admin Screen for Attribute Management
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

### FR4.2: Product Category Assignments and Variation Generation
- **Trigger**: User navigates to Inventory > Stock > Product Attributes > Assignments tab.
- **Process**:
  1. Enter parent product stock_id and click "Load Product".
  2. View currently assigned categories in a table with columns: Category, Code, Description, Sort Order (Royal Order), Actions.
  3. Add categories to the parent product using the "Add Category Assignment" form (only shows unassigned categories).
  4. Remove category assignments using the "Remove" links with confirmation.
  5. Click "Generate Variations" button to create all combinations of values from assigned categories.
  6. System generates child products with:
     - Stock_id: Parent + attribute slugs in Royal Order (e.g., TSHIRT-S-RED).
     - Description: Parent description with attribute values appended.
     - Parent relationship: Set parent_stock_id to parent product.
     - All other fields copied from parent.
  7. Skip creation if variation already exists.
  8. Display count of created variations.
- **Output**: Category assignments saved and/or child variation products created.
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

### FR4.1: Royal Order Helper Utility
- **Trigger**: System needs to display or validate Royal Order information.
- **Process**:
  1. Provide centralized Royal Order options and labels.
  2. Generate HTML dropdowns with proper formatting.
  3. Validate sort order values (1-9 range).
  4. Convert numeric sort orders to descriptive labels (e.g., 3 → "Size").
  5. Follow Single Responsibility Principle with dedicated utility class.
- **Output**: Consistent Royal Order handling across the application.

### FR5: Inventory and Stock Management (Already Supported by FA)
- Variations, as individual products, have independent stock levels via FA's stock_id.
- No additional FR needed.

### FR6: Sales and Pricing
- Sub-screen for updating variation prices.
- Options: Update all if matching, force update with list, update matching with differ list.
- Check if FA_BulkPriceUpdate module is installed; if yes, use its bulk update function (pass array of stock_ids, price book, price value) for price setting.
- If not installed, implement internal bulk update logic.
- Variations appear in sales interfaces.

### FR7: Reporting and Analytics
- Create new reports with attribute filters.
- Modify existing FA reports to support attribute-based filtering where applicable.
- Validation report for inactive parents with active 0-stock variations.

### FR7: Reporting and Analytics
- Create new reports with attribute filters.
- Modify existing FA reports to support attribute-based filtering where applicable.

### FR8: Bulk Operations
- UI for editing multiple variations at once.

### FR9: Retroactive Application of Module
- **Trigger**: User accesses a new screen or button (e.g., under Inventory > Stock > Retroactive Attributes).
- **Process**:
  1. Scan all existing stock_ids in the database.
  2. Analyze patterns based on Royal Order and attribute abbreviations to identify potential variation groups.
  3. For groups like BM-SG1, BM-SG2, BM-SG3, suggest creating a parent BM-SG if it doesn't exist.
  4. For hierarchies like A-B-C (potential parent) and A-B-C-D, A-B-C-E (potential children), suggest associations.
  5. Display suggested relationships in a list or table, with options to review and assign.
  6. Provide a bulk edit screen where user can select multiple suggested child products and assign them to a parent at once.
  7. For each assignment, perform sanity checks (e.g., stock_id root matching), show warnings, and allow force with confirmation.
  8. On assignment, update parent_stock_id and parent flag accordingly.
- **Output**: Assigned parent-child relationships, with confirmation of changes.

### FR10: API for External Integration
- **Trigger**: External system makes API calls to manage attributes.
- **Process**:
  1. Provide endpoints for GET/POST/PUT/DELETE on categories, values, product associations.
  2. Validate requests and permissions.
  3. Return JSON responses.
- **Output**: Updated data or queried information.

## Technical Implementation Guidelines
- **Compatibility**: FrontAccounting 2.3.22 on PHP 7.3.
- **Code Quality**: Follow SOLID principles (SRP, OCP, LSP, ISP, DIP) with DI. Use interfaces for contracts, parent classes/traits for DRY. Minimize If/Switch by using polymorphic SRP classes (Fowler).
- **Testing**: Unit tests for all code covering edge cases. UAT test cases designed alongside UI.
- **Documentation**: PHPDoc blocks/tags. UML diagrams: ERD, Message Flow, Logic Flowcharts.

## Data Flow
- User Input → Validation → DB Update → Confirmation.

## Interfaces
- UI: HTML forms integrated into FA.
- DB: New tables: attribute_categories, attribute_values, product_attributes.

## Error Handling
- Invalid inputs: Display error messages.
- DB failures: Rollback and notify user.