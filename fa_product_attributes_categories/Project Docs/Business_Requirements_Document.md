# Business Requirements Document (BRD) - FA_ProductAttributes_Categories

## Overview
The FA_ProductAttributes_Categories plugin provides enhanced product categorization functionality beyond FrontAccounting's built-in category system. Similar to WooCommerce, this plugin allows administrators to create hierarchical category structures and assign multiple categories to products, enabling more flexible product organization and filtering capabilities.

## Business Objectives
- Create additional category hierarchies beyond FA's main category system
- Support parent-child category relationships for complex product organization
- Enable multiple category assignments per product
- Provide enhanced product filtering and search capabilities
- Maintain compatibility with existing FA category system
- Support category-based reporting and analytics

## Stakeholders
- **Product Managers**: Organizing complex product catalogs with multiple categorization schemes
- **E-commerce Managers**: Creating category structures for online sales platforms
- **Inventory Managers**: Using categories for warehouse organization and picking
- **Marketing Teams**: Category-based product grouping for promotions and campaigns
- **System Integrators**: Syncing category data with external e-commerce platforms

## System Architecture

### Categories Plugin Responsibilities
**Core Functionality:**
- Hierarchical category definition with unlimited nesting levels
- Multiple category assignment to individual products
- Category management interface integrated with product details
- Category-based product filtering and search
- Bulk category assignment operations
- Category hierarchy visualization and management

**Integration Points:**
- Extends core product interface with categories tab
- Integrates with FA's existing category system (read-only compatibility)
- Provides category data for external system integrations
- Supports category-based reporting and analytics

**Database Extensions:**
- Adds hierarchical category tables with parent-child relationships
- Creates product-to-category assignment tables
- Supports efficient category-based queries and filtering
- Maintains category metadata and display properties
- `product_categories`: Hierarchical category definitions
- `product_category_hierarchy`: Parent-child category relationships
- `product_category_assignments`: Links products to categories

### Plugin Architecture
**Extension Points:**
- `categories_tab_content`: Plugins can add UI to categories tab
- `categories_save`: Plugins can handle category save operations
- `product_type_management`: Plugins can extend product type functionality

**Current Plugins:**
- **FA_ProductAttributes_Variations**: Adds WooCommerce-style product variations
  - Parent-child product relationships
  - Automatic variation generation
  - Royal Order attribute sequencing
  - Retroactive pattern analysis for existing products
- **FA_ProductAttributes_Categories**: Adds product categorization functionality
  - Hierarchical category structures
  - Product-to-category assignments
  - Category-based organization and filtering
  - Bulk category operations

## Functional Requirements

### Category Management Interface
- Admin screen for creating and managing hierarchical categories outside FA's main category system
- Support for unlimited nesting levels with parent-child category relationships
- Category properties: name, description, display order, active status, parent category
- Visual tree interface for managing category hierarchies
- Bulk operations: activate/deactivate categories, reorder siblings, move categories between parents
- Category validation to prevent circular references and orphaned subcategories

### Product Category Assignment Interface
- New "Categories" tab on the Product Attributes interface
- Display both FA native categories (read-only) and plugin categories (editable)
- Multi-select interface for assigning multiple plugin categories to products
- Drag-and-drop or checkbox interface for category assignment
- Category assignment history and audit trail
- Bulk category assignment for multiple products simultaneously

### Category Hierarchy Features
- Parent-child category relationships with unlimited depth
- Category inheritance for organizational purposes (not attributes)
- Breadcrumb navigation showing full category path
- Category-based product counting and statistics display
- Support for category-specific metadata and properties

### Integration with FA Categories
- Read-only display of FA's native category assignments
- Ability to assign both FA categories and plugin categories to the same product
- Category data synchronization for reporting purposes
- Compatibility with existing FA category-based features

### Search and Filtering Capabilities
- Enhanced product search with multi-category filtering
- Support for AND/OR logic in category-based searches
- Category hierarchy-aware filtering (include subcategories option)
- Saved filter sets for common category combinations
- Category-based product export capabilities

### Reporting and Analytics
- Category distribution reports showing product counts per category
- Category hierarchy visualization and analysis
- Cross-category product analysis and overlap reporting
- Category-based sales and inventory performance metrics
- Custom reporting for category-specific business intelligence

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