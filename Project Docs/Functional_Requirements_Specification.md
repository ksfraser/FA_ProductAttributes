# Functional Requirements Specification (FRS)

## Introduction
This document details the functional behavior of the Product Attributes module for FrontAccounting.

## Functional Requirements Details

### FR1: Items Screen Enhancement
- **Trigger**: User navigates to Inventory > Items and selects a product.
- **Process**:
  1. Display existing product details.
  2. Add "Product Attributes" TAB.
  3. Check if product is parent (parent flag = true); if yes, show TAB with "Create Variations", "Make Inactive", "Reactivate Variations", "Create Missing Variations" buttons.
  4. If not parent, show "Assign Parent" dropdown with sanity check and force option.
  5. "Make Inactive": Deactivate parent, default deactivate 0 stock variations, warn on stock >0.
  6. "Reactivate Variations": Rebuild combinations, activate inactive, prompt for missing.
  7. "Create Missing Variations": Generate missing combinations, create selected.
  8. On TAB click, load associated attributes from DB.
  9. Show list of attributes with add/remove options.
  10. On save, update DB associations.
- **Output**: Updated product with attributes saved.

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
  2. Table includes columns: Category Name, Royal Order (editable input), Actions (Edit/Delete).
  3. Display values in a separate tab/table with columns: Value, Slug, Sort Order, Active, Actions (Edit/Delete).
  4. Display assignments in a separate tab/table with columns: Category, Value, Slug, Sort Order, Actions (Delete).
  5. Edit buttons pre-fill forms with existing data and change button text to "Update".
  6. Delete buttons show confirmation dialogs and prevent deletion if items are in use.
  7. Provide CRUD forms for categories and variables with validation.
- **Output**: Updated categories and variables in DB.

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