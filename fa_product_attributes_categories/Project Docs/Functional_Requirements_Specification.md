# Functional Requirements Specification (FRS) - FA_ProductAttributes_Categories

## Introduction
This document details the functional behavior of the FA_ProductAttributes_Categories plugin, which provides additional hierarchical product categorization capabilities beyond FrontAccounting's native category system.

## System Purpose
The Categories plugin enables administrators to create and manage additional product categorization hierarchies that complement FA's existing category structure, providing enhanced product organization and filtering capabilities.

## Core Functionality

### Additional Category Management
- Create and manage additional category hierarchies separate from FA's native categories
- Support for multi-level category trees with unlimited depth
- Category naming, descriptions, and metadata management
- Category activation/deactivation controls

### Product Category Assignment
- Assign products to multiple additional categories
- Bulk category assignment operations for efficiency
- Category assignment validation and conflict resolution
- Visual category assignment interface with drag-and-drop support

### Category-Based Filtering
- Enhanced product search and filtering by additional categories
- Category-based product reporting and analytics
- Integration with FA's existing search and filter systems
- Category hierarchy navigation and browsing

### Category Hierarchy Operations
- Parent-child category relationships with inheritance
- Category reordering and restructuring capabilities
- Bulk category operations (move, copy, delete)
- Category hierarchy visualization and management tools

### Integration Features
- Seamless integration with FA's native category system
- Automatic category application during product creation
- Category-based filtering in product lists and reports
- Export/import capabilities for category structures
- API support for external category management systems
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

### FR4.4: Bulk Category Operations
- **Trigger**: User needs to assign categories to multiple products.
- **Process**:
  1. Select multiple products for category assignment.
  2. Choose categories to assign or remove.
  3. Apply bulk operations with confirmation.
  4. Support category assignment rules and validation.
  5. Display operation results and affected products.
- **Output**: Multiple products updated with category assignments.

### FR4.5: Category Reporting and Analytics
- **Trigger**: User needs category-based insights.
- **Process**:
  1. Generate reports on category usage and distribution.
  2. Show product counts per category.
  3. Display category hierarchy visualizations.
  4. Export category data for external analysis.
  5. Track category assignment changes over time.
- **Output**: Category analytics and reporting data.

## Technical Implementation Guidelines
- **Compatibility**: FrontAccounting 2.3.22 on PHP 7.3.
- **Code Quality**: Follow SOLID principles with dependency injection.
- **Testing**: Unit tests covering category operations and validation.
- **Documentation**: PHPDoc blocks and clear code structure.

## Data Flow
- User Input → Validation → Category Assignment → DB Update → Confirmation.

## Interfaces
- UI: Category management forms integrated into FA.
- DB: New tables: product_categories, category_hierarchy, product_category_assignments.

## Error Handling
- Invalid category operations: Display error messages.
- DB failures: Rollback and notify user.