# Business Requirements Document (BRD)

## Overview
The module will extend FrontAccounting to support product attributes and variations, enabling users to associate attributes with products, create variation products via cloning, and manage attribute categories and values through an admin interface.

## Business Objectives
- Enhance product management in FrontAccounting by adding attribute support.
- Enable creation of product variations to handle complex inventory scenarios (e.g., different sizes/colors).
- Provide administrative tools for maintaining attribute categories and values.
- Ensure seamless integration with existing FA workflows.

## Stakeholders
- Inventory Managers: Need to associate attributes and create variations.
- Administrators: Manage attribute categories and values.
- End Users: Interact with enhanced product screens.

## Functional Requirements

1. **Items Screen Enhancement**
   - Add a new TAB labeled "Product Attributes" to the existing FrontAccounting Items screen (accessible via Inventory > Items).
   - This TAB will allow users to view and manage attributes associated with the selected product.
   - Functionality on the TAB:
     - Display a list of currently associated attributes for the product.
     - Allow adding/removing attributes from predefined categories (e.g., color, size).
     - For parent products (parent flag = true), include:
       - "Create Variations" button to generate child products.
       - "Make Inactive" button: Deactivates the parent and, by default, deactivates child variations with 0 stock. Shows warning list of variations with stock >0, but allows deactivation of 0 stock items.       - "Reactivate Variations" button: For inactive parents, rebuilds and re-activates existing variations, with option to create missing ones.
       - "Create Missing Variations" button: Generates and creates any missing variation combinations.
     - For non-parent products, include "Assign Parent" dropdown to designate as child of a parent product, with sanity checking (warning for mismatched stock_id roots) and force option after "are you sure" confirmation.
     - Support saving changes to persist attribute associations with the product.

2. **Attribute Association**
   - For each product, users must be able to associate specific attributes from available categories.
   - Attributes are selected from a predefined list managed in the admin screen (see below).
   - Ensure data integrity: Only valid attributes from existing categories can be associated.
   - Store associations in the database, linked to the product's stock_id.

3. **Variation Product Creation**
   - Provide a "Create Variations" feature on the Product Attributes TAB (only visible for parent products).
   - After attaching categories and selecting values, user clicks button to generate child products.
   - System creates variations using all combinations of selected attribute values, including new attributes added to existing product lines.
   - Include a "Copy Sales Pricing" checkbox: If checked, copies all sales prices from the master product to each variation.
   - Use "Royal Order of adjectives" for attribute sequencing (e.g., Size before Color).
   - Stock_id format: Parent stock_id + attribute abbreviations in order (e.g., XYZ-L-RED).
   - Short description: Replace ${ATTRIB_CLASS} placeholders in parent description with corresponding long attribute names (e.g., if parent has "Coat - ${Size} ${Color}", variation becomes "Coat - Large Red").
   - Each variation inherits base product details but has unique stock_id and description, with parent flag set to false and parent_stock_id set to master's stock_id.
   - Users can manually deactivate unwanted variations after creation if certain combinations are not needed.
   - Ensure cloned products are fully functional as standalone items in FrontAccounting.

4. **Admin Screen for Attribute Management**
   - Add a new submenu item under Inventory > Stock, titled "Product Attributes" or "Attribute Categories".
   - This admin screen will manage:
     - **Categories**: Create, edit, delete variable categories (e.g., "Color", "Size").
       - Include a "Royal Order" column/field: An integer value to define the sequencing order for attributes in variations (e.g., Size=1, Color=2).
       - Allow overriding the default order if needed.
       - UI: Table view with columns for Category Name, Royal Order (editable input field), and Actions (Edit/Delete).
       - Viewing: Sortable table with options to sort by Name or Royal Order (ascending/descending).
     - **Variables**: For each category, add, edit, delete specific values (e.g., "Red", "Large").
   - Use a hierarchical interface: List categories (sortable by Royal Order), and under each, list associated variables.
   - Include validation to prevent deletion of categories/variables if they are in use by products.
   - Persist data in dedicated database tables (e.g., attribute_categories and attribute_values).

5. **Inventory and Stock Management** (Already Supported by FA)
   - Variations, as individual products, inherit FA's independent stock levels per stock_id.
   - No additional development needed; ensure variations are created as separate products.

6. **Sales and Pricing**
   - FA already supports price books per product; variations inherit this.
   - Add an "Update Price for All Variations" sub-screen on the Product Attributes TAB (parent products only).
   - Use Cases:
     - If all variations have same prices as parent, update all from parent.
     - For variations with different prices (e.g., size-based), provide "Force Update" option with confirmation list of affected products.
     - "Update Matching Prices" option: Update only variations with prices matching parent, list differing variations.
   - If FA_BulkPriceUpdate module is installed, leverage its bulk update functionality for setting prices on multiple variations (accepts array of stock_ids, price book, and price value).

7. **Reporting and Analytics**
   - Since attributes are new to FA, existing reports lack filters.
   - Add new custom reports or modify key existing reports (e.g., Inventory Report, Sales Report) to include attribute filters (e.g., filter by Color or Size).
   - Ensure variations are listed with their attributes in report outputs.
   - Include validation report: Identify inactive parents with active 0-stock variations for cleanup.

8. **Bulk Operations**
   - Allow bulk editing of prices, stock, or attributes for multiple variations.

9. **Retroactive Application of Module**
   - Provide functionality to analyze existing products and suggest parent-child relationships based on stock_id patterns.
   - Scan all stock_ids for groups that match variation creation rules (using Royal Order and attribute abbreviations).
   - Suggest creation of parent products for identified variation groups (e.g., if BM-SG1, BM-SG2, BM-SG3 exist, suggest BM-SG as parent).
   - Suggest parent-child associations for existing products where patterns indicate hierarchy (e.g., A-B-C as parent for A-B-C-D and A-B-C-E).
   - Include a bulk edit screen to review suggested assignments and assign multiple child products to parents at once.
   - Ensure sanity checks and confirmation dialogs for assignments.

10. **API for External Integration**
   - Provide REST API endpoints for CRUD operations on attribute categories, values, and product associations.
   - Allow external systems to query and manage product attributes programmatically.
   - Ensure API follows FA's security model and authentication.

## Non-Functional Requirements
- **Integration**: Seamlessly integrate with FrontAccounting's existing UI and database schema without disrupting core functionality.
- **Security**: Ensure only authorized users (e.g., with appropriate permissions) can access and modify attributes and variations. Grey out buttons for unauthorized users, similar to FA's unavailable menus. Honor FA's system preference for showing/hiding unavailable choices.
- **Performance**: Attribute loading and saving should be efficient, even for products with multiple attributes.
- **Usability**: Intuitive UI elements (e.g., dropdowns for attribute selection, confirmation dialogs for cloning). Include tooltips for buttons and fields, and confirmation dialogs for destructive actions (e.g., deactivation, creation).
- **Data Persistence**: Use the existing FrontAccounting database structure; extend with new tables if necessary (as per the provided schema.sql). Add parent_stock_id field to track parent-child relationships; products with parent flag = true are variable masters, false are simple or variations.
- **Data Integrity**: "Make Inactive" button for parents: Deactivates parent and, by default, deactivates variations with 0 stock. Warning list for variations with stock >0, but allows deactivation of 0 stock items.
- **Compatibility**: 
  - FrontAccounting version: 2.3.22
  - PHP version: 7.3
- **Code Quality**: Adhere to SOLID principles (Single Responsibility, Open-Closed, Liskov Substitution, Interface Segregation, Dependency Inversion), including Dependency Injection (DI) and Single Responsibility Principle (SRP). Use interfaces (contracts) where appropriate, parent classes or traits for DRY (Don't Repeat Yourself). Avoid If/Switch statements where possible by using SRP classes as described by Fowler.
- **Testing**: Write unit tests for ALL code, aiming to cover all edge cases. Design UAT (User Acceptance Testing) test cases since UI is being designed concurrently.
- **Documentation**: Include PHPDoc tags/blocks for all code. Provide UML diagrams including ERD (Entity-Relationship Diagram), Message Flow, flow charts (logic), etc.

## Assumptions
- FrontAccounting version compatibility: Confirmed as 2.3.22.
- PHP version: 7.3 (ensure code uses compatible syntax and features).
- Database: Leverage existing PDO/MySQL setup; new tables will be defined in schema.sql.
- Products have a "parent" flag: Boolean field indicating if a product is a master/parent (true) or a variation/child (false). Only parent products display the "Create Variations" button.
- User Roles: Admin access for category management; standard users for product attribute association.
- Technical Standards: Code follows SOLID principles with DI and SRP; comprehensive unit testing; PHPDoc documentation; UML diagrams (ERD, Message Flow, flowcharts); UAT test cases designed with UI.

## Constraints
- Must not alter core FA files directly; use hooks and extensions.
- Database changes limited to new tables.

## Acceptance Criteria
- All functional requirements implemented and tested.
- No performance degradation in FA.
- Admin and user interfaces intuitive and error-free.

## Approval
[To be signed off by stakeholders]