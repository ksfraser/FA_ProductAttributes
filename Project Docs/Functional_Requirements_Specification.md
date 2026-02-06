# Functional Requirements Specification (FRS)

## Introduction
This document details the functional behavior of the FA_ProductAttributes system, consisting of a core module providing generic attribute infrastructure and a plugin architecture for extensible functionality.

## System Components

### Core Module (FA_ProductAttributes)
**Purpose**: Provides generic attribute management infrastructure and extension points for plugins.

**Core Functionality:**
- Attribute category and value management
- Product-to-attribute assignment system
- Generic attribute UI components
- Hook-based extension system
- Product type infrastructure

### Plugin System
**Purpose**: Enables domain-specific attribute functionality through plugins.

**Extension Mechanism:**
- Plugins register extensions to core hook points
- Core provides generic UI that plugins can extend
- Plugins can add domain-specific services and UI
- Clean separation of concerns between core and plugins

## Functional Requirements Details

### FR1: Core Attribute Management
- **Trigger**: Administrator accesses Product Attributes admin interface.
- **Process**:
  1. Display attribute categories and values management interface.
  2. Allow creation, editing, and deletion of categories.
  3. Allow creation, editing, and deletion of values within categories.
  4. Maintain Royal Order sorting for consistent attribute display.
- **Output**: Updated attribute structure available for product assignments.

### FR2: Product Attribute Assignment (Core)
- **Trigger**: User navigates to Inventory > Items and selects a product.
- **Process**:
  1. Display existing product details.
  2. Add "Product Attributes" TAB via hook system.
  3. Show generic attribute assignment interface.
  4. Allow selection of attribute categories and values.
  5. Display assigned attributes in table format.
  6. Show "Variations" column indicating combinatorial possibilities.
- **Output**: Attributes associated with product, available for plugin extensions.

### FR3: Plugin Extension System
- **Trigger**: Plugin modules are activated.
- **Process**:
  1. Plugins register extensions to core hook points.
  2. Core module loads and executes plugin extensions.
  3. Plugins can add UI elements, save handlers, and business logic.
  4. Extension execution follows priority-based ordering.
- **Output**: Extended functionality without modifying core code.

### FR4: Product Type Infrastructure
- **Trigger**: Products are managed through the system.
- **Process**:
  1. Support classification of products as Simple, Variable, or Variation.
  2. Maintain parent-child relationships for variation products.
  3. Provide infrastructure for plugins to manage product types.
- **Output**: Consistent product type management across core and plugins.

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

### FR4: Product Category Management
- **Trigger**: User needs to organize products into hierarchical categories.
- **Process**:
  1. Create and manage category hierarchies (parent-child relationships).
  2. Assign products to one or multiple categories.
  3. Support category-based filtering and organization.
  4. Provide bulk category assignment operations.
  5. Display category assignments in product listings.
  6. Allow category-based reporting and analytics.
- **Output**: Products organized by categories with hierarchical structure.

### FR4.1: Category Hierarchy Management
- **Trigger**: Administrator needs to define product categories.
- **Process**:
  1. Create top-level categories (e.g., Clothing, Electronics).
  2. Create subcategories under parent categories (e.g., Shirts under Clothing).
  3. Support unlimited nesting levels.
  4. Maintain category sort order and display preferences.
  5. Provide category activation/deactivation.
- **Output**: Hierarchical category structure for product organization.

### FR4.2: Product Category Assignments
- **Trigger**: User assigns categories to products.
- **Process**:
  1. Display available categories in hierarchical tree view.
  2. Allow multiple category selection per product.
  3. Support drag-and-drop category assignment.
  4. Validate category assignments against business rules.
  5. Display assigned categories in product details.
- **Output**: Products linked to appropriate categories.

### FR4.3: Category-Based Filtering
- **Trigger**: User needs to filter products by category.
- **Process**:
  1. Display category tree for filtering.
  2. Support single or multiple category selection.
  3. Include subcategories in parent category filters.
  4. Apply filters to product listings and reports.
  5. Maintain filter state across sessions.
- **Output**: Filtered product views based on category selection.

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
- **Scope Clarification**: Individual item pricing is handled by FA core (out of scope). Variation-based pricing rules are in scope.
- **Trigger**: User needs to apply pricing rules based on variation attributes.
- **Process**:
  1. Define pricing rules per attribute value (e.g., "XXL size: +$2.00", "RED color: +25%").
  2. Support fixed amount adjustments ($X), percentage adjustments (Y%), or combined (X + Y%).
  3. Apply rules automatically when generating variations.
  4. Allow manual override of calculated prices.
  5. Display price calculations with rule breakdowns.
- **Output**: Variations created with appropriate pricing based on attribute rules.

### FR7: Bulk Operations (Core Module)
- **Scope**: Core module provides bulk operations framework. Plugins extend with domain-specific rules.
- **Trigger**: User needs to apply changes to multiple related products simultaneously.
- **Process**:
  1. Select parent product and operation type (pricing, attributes, etc.).
  2. Choose target variations (all, filtered by attributes, etc.).
  3. Apply bulk changes:
     - Price adjustments (fixed amount, percentage, or combined)
     - Attribute assignments/removals
     - Status changes (active/inactive)
     - Category assignments
  4. Plugins can extend with custom bulk operations and validation rules.
  5. Preview changes before applying.
  6. Show confirmation with affected products count.
- **Output**: Bulk changes applied to selected variations with plugin-specific business rules enforced.

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