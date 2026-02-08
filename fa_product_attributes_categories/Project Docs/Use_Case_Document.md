# Use Case Document - FA_ProductAttributes_Categories Plugin

## Overview
This document describes use cases for the FA_ProductAttributes_Categories plugin, which provides additional hierarchical product categorization capabilities beyond FrontAccounting's native category system. Similar to WooCommerce, this plugin enables flexible product organization through multiple category assignments.

## Categories Plugin Responsibilities
- Additional category hierarchy management separate from FA categories
- Product assignment to multiple categories
- Category-based filtering and search capabilities
- Bulk category operations and assignments
- Category reporting and analytics
- Category hierarchy visualization and navigation

## Use Case: Create Category Hierarchies

### Actors
- Category Manager (Primary Actor)
- Product Catalog Administrator (Supporting Actor)

### Preconditions
- FA_ProductAttributes_Core module is installed and active
- FA_ProductAttributes_Categories plugin is installed and active
- User has appropriate permissions for category management

### Main Flow
1. Category Manager navigates to Product Attributes > Categories interface
2. Creates top-level categories (e.g., "Clothing", "Electronics")
3. Creates subcategories under parent categories (e.g., "Shirts" under "Clothing")
4. Sets category properties (name, description, display order, active status)
5. Establishes parent-child relationships for hierarchy
6. Saves category structure

### Alternative Flows
- **Bulk Category Import**: Manager imports category structure from CSV/Excel
- **Category Reorganization**: Manager moves categories within hierarchy
- **Category Deactivation**: Manager deactivates categories without deleting

### Postconditions
- Hierarchical category structure is established
- Categories are available for product assignment
- Category hierarchy supports unlimited nesting levels
- Categories can be used for filtering and organization

## Use Case: Assign Products to Categories

### Actors
- Product Manager (Primary Actor)
- Category Administrator (Supporting Actor)

### Preconditions
- Product Manager has access to FrontAccounting Items screen
- FA_ProductAttributes core module is installed and active
- FA_ProductAttributes_Categories plugin is installed and active
- Category hierarchies are defined in the system

### Main Flow
1. Product Manager navigates to Inventory > Items and selects a product
2. Clicks on "Categories" TAB (provided by Categories plugin)
3. Views available category hierarchy in tree format
4. Selects one or multiple categories for the product
5. Assigns product to selected categories
6. Saves category assignments
7. Views assigned categories in product details

### Postconditions
- Product is assigned to selected categories
- Category assignments are stored and available for filtering
- Product appears in category-based reports and searches

### Alternative Flows
- **Bulk Assignment**: Manager selects multiple products and assigns categories to all
- **Category Removal**: Manager removes product from specific categories
- **Drag-and-Drop**: Manager uses drag-and-drop interface for category assignment

## Use Case: Filter Products by Categories

### Actors
- Product Manager (Primary Actor)
- Inventory Analyst (Supporting Actor)

### Preconditions
- Product Manager has access to FrontAccounting product listings
- FA_ProductAttributes_Categories plugin is installed and active
- Products are assigned to categories

### Main Flow
1. Product Manager navigates to Inventory > Items listing
2. Accesses category filter interface
3. Views category hierarchy in tree format
4. Selects one or multiple categories for filtering
5. Chooses filter options (include subcategories, match all/any categories)
6. Applies category filter to product listing
7. Views filtered product results
8. Exports or reports on filtered products if needed

### Postconditions
- Product listing shows only products matching category criteria
- Filter state is maintained for session
- Filtered results available for further operations

### Alternative Flows
- **Advanced Filtering**: Manager combines category filters with other criteria
- **Saved Filters**: Manager saves frequently used category filter combinations
- **Category Navigation**: Manager browses products by navigating category tree

### Exceptions
- No products in selected categories: Display empty results with option to broaden filter
- Category access restrictions: Filter only shows categories user can access

### Business Rules
- Subcategory inclusion: Parent category selection includes all child categories
- Multiple category logic: Support AND/OR logic for multiple category selection
- Performance: Efficient filtering even with large product catalogs

## Use Case: Bulk Category Operations

### Actors
- Category Administrator (Primary Actor)
- Product Manager (Supporting Actor)

### Preconditions
- Category Administrator has access to category management interface
- FA_ProductAttributes_Categories plugin is installed and active
- Multiple products exist in the system

### Main Flow
1. Category Administrator navigates to Category Management > Bulk Operations
2. Selects operation type (assign categories, remove categories, move categories)
3. Chooses target products (individual selection, category-based selection, or all products)
4. Selects categories for the operation
5. Configures operation options (replace existing, add to existing, etc.)
6. Previews affected products and categories
7. Confirms and executes bulk operation
8. Views operation results and confirmation

### Postconditions
- Selected products have updated category assignments
- Operation results are logged for audit purposes
- Category assignments are immediately available for filtering

### Alternative Flows
- **Import Categories**: Administrator imports category assignments from CSV
- **Category Migration**: Administrator moves products between category hierarchies
- **Category Cleanup**: Administrator removes unused categories and reassigns products

### Exceptions
- Permission restrictions: Operation blocked for unauthorized categories
- Data conflicts: Validation prevents invalid category assignments
- System limits: Large operations may require batching

## Use Case: Category Reporting and Analytics

### Actors
- Category Administrator (Primary Actor)
- Business Analyst (Supporting Actor)

### Preconditions
- FA_ProductAttributes_Categories plugin is installed and active
- Products are assigned to categories
- User has reporting permissions

### Main Flow
1. Category Administrator navigates to Reports > Category Analytics
2. Selects report type (category usage, product distribution, hierarchy analysis)
3. Chooses date range and filter criteria
4. Generates report showing category statistics
5. Views visualizations of category hierarchies and usage
6. Exports report data for external analysis

### Postconditions
- Category analytics report is generated
- Insights into category usage and effectiveness are available

### Alternative Flows
- **Real-time Dashboard**: Administrator views live category metrics
- **Custom Reports**: Administrator creates custom category-based reports
- **Trend Analysis**: Administrator analyzes category usage over time

### Business Rules
- Reports respect user permissions and data access levels
- Analytics include product counts, category distribution, and usage patterns
- Export formats support common business intelligence tools