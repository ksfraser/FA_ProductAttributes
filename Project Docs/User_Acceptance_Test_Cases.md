# User Acceptance Testing (UAT) Test Cases - FA_ProductAttributes Core Module

## Overview

This document provides comprehensive User Acceptance Testing scenarios for the FA_ProductAttributes core module, which provides generic attribute infrastructure. Variation-specific test cases are documented separately in the FA_ProductAttributes_Variations plugin UAT documentation.

## Core Module Testing Scope

**Included in Core UAT:**
- Attribute category and value management
- Generic product attribute assignment
- Royal Order sorting functionality
- Hook extension system validation
- Plugin compatibility testing

**Excluded from Core UAT (Plugin-Specific):**
- Product variation generation
- Parent-child relationship management
- Retroactive pattern analysis
- Variation UI extensions

## Test Case Structure

Each test case includes:
- **Test Case ID**: Unique identifier (prefixed with CORE-)
- **Title**: Descriptive test name
- **Priority**: Critical, High, Medium, Low
- **Preconditions**: Required setup
- **Test Steps**: Step-by-step instructions
- **Expected Results**: What should happen
- **Pass/Fail Criteria**: How to determine success
- **Test Data**: Sample data to use
- **Notes**: Additional context or edge cases

## Test Environment Setup

### Prerequisites
- FrontAccounting 2.3.22 installed and running
- FA_ProductAttributes core module activated
- Test user with admin permissions
- Sample inventory data created
- Browser: Chrome/Firefox latest versions
- **Note**: Plugins should be tested separately after core validation

### Test Data Setup
Run the following SQL to create test data:
```sql
-- Insert test categories
INSERT INTO product_attribute_categories (code, label, description, sort_order, active) VALUES
('SIZE', 'Size', 'Product size variations', 1, 1),
('COLOR', 'Color', 'Product color variations', 2, 1),
('MATERIAL', 'Material', 'Product material variations', 3, 1);

-- Insert test values
INSERT INTO product_attribute_values (category_id, value, slug, sort_order, active) VALUES
(1, 'Small', 'S', 1, 1),
(1, 'Medium', 'M', 2, 1),
(1, 'Large', 'L', 3, 1),
(2, 'Red', 'RED', 1, 1),
(2, 'Blue', 'BLU', 2, 1),
(3, 'Cotton', 'COT', 1, 1),
(3, 'Polyester', 'POL', 2, 1);

-- Create test parent product
INSERT INTO stock_master (stock_id, description, category_id, taxable, mb_flag, inventory_account, cogs_account, adjustment_account, sales_account, inactive) VALUES
('TEST-TSHIRT', 'Test T-Shirt ${Size} ${Color}', 1, 0, 'B', 1, 1, 1, 1, 0);
```

---

## Admin Interface Test Cases

### TC-UAT-ADMIN-001: Create New Attribute Category
**Priority**: Critical  
**Preconditions**: Admin user logged in, on Product Attributes admin page  

**Test Steps**:
1. Navigate to Inventory → Stock → Product Attributes
2. Click on "Categories" tab
3. Click "Add Category" button
4. Enter "Fabric" in Name field
5. Select "4 - Material" from Royal Order dropdown
6. Enter "Fabric type variations" in Description
7. Click "Create Category" button

**Expected Results**:
- Success message: "Category 'Fabric' created successfully"
- Category appears in the sortable table
- Royal Order displays as "4 - Material"
- Category is active (no strikethrough)

**Pass/Fail Criteria**:
- PASS: Category created and visible in table with correct Royal Order
- FAIL: Error message or category not created

**Test Data**: Name: "Fabric", Royal Order: "4 - Material"

---

### TC-UAT-ADMIN-002: Edit Existing Category
**Priority**: High  
**Preconditions**: Category "Fabric" exists from TC-UAT-ADMIN-001  

**Test Steps**:
1. On Categories tab, click "Edit" link next to "Fabric" category
2. Change Description to "Different fabric materials"
3. Change Royal Order to "3 - Shape"
4. Click "Update Category" button

**Expected Results**:
- Success message: "Category 'Fabric' updated successfully"
- Description and Royal Order updated in table
- Royal Order displays as "3 - Shape"

**Pass/Fail Criteria**:
- PASS: Category updated with new description and Royal Order
- FAIL: Changes not saved or error occurs

---

### TC-UAT-ADMIN-003: Delete Unused Category (Hard Delete)
**Priority**: High  
**Preconditions**: Category "Fabric" exists and is not assigned to any products  

**Test Steps**:
1. On Categories tab, click "Delete" link next to "Fabric" category
2. Confirm deletion in popup dialog

**Expected Results**:
- Success message: "Category 'Fabric' deleted successfully"
- Category removed from table permanently

**Pass/Fail Criteria**:
- PASS: Category completely removed from system
- FAIL: Category still exists or error occurs

---

### TC-UAT-ADMIN-004: Attempt Delete Used Category (Soft Delete)
**Priority**: High  
**Preconditions**: Category "Color" is assigned to products  

**Test Steps**:
1. On Categories tab, click "Delete" link next to "Color" category
2. Confirm deletion in popup dialog

**Expected Results**:
- Success message: "Category 'Color' deactivated (soft delete)"
- Category shows as inactive (strikethrough) but remains in table

**Pass/Fail Criteria**:
- PASS: Category deactivated but preserved for data integrity
- FAIL: Category hard deleted or error occurs

---

### TC-UAT-ADMIN-005: Add Values to Category
**Priority**: Critical  
**Preconditions**: "Size" category exists  

**Test Steps**:
1. Click on "Values" tab
2. Select "Size" from category dropdown
3. Click "Add Value" button
4. Enter "Extra Large" in Value field
5. Enter "XL" in Slug field
6. Set Sort Order to 4
7. Click "Create Value" button

**Expected Results**:
- Success message: "Value 'Extra Large' created successfully"
- Value appears in table with correct slug and sort order

**Pass/Fail Criteria**:
- PASS: Value created and visible in table
- FAIL: Error message or value not created

---

### TC-UAT-ADMIN-006: Edit Attribute Value
**Priority**: High  
**Preconditions**: "Extra Large" value exists in Size category  

**Test Steps**:
1. On Values tab, click "Edit" link next to "Extra Large"
2. Change Value to "Extra Large (XL)"
3. Change Slug to "X-LARGE"
4. Click "Update Value" button

**Expected Results**:
- Success message: "Value 'Extra Large (XL)' updated successfully"
- Value updated in table with new display text and slug

**Pass/Fail Criteria**:
- PASS: Value updated correctly
- FAIL: Changes not saved

---

### TC-UAT-ADMIN-007: Delete Unused Value (Hard Delete)
**Priority**: High  
**Preconditions**: "Extra Large (XL)" value exists and not used by products  

**Test Steps**:
1. On Values tab, click "Delete" link next to "Extra Large (XL)"
2. Confirm deletion

**Expected Results**:
- Success message: "Value 'Extra Large (XL)' deleted successfully"
- Value removed from table

**Pass/Fail Criteria**:
- PASS: Value completely removed
- FAIL: Value still exists

---

### TC-UAT-ADMIN-008: Load Product for Assignments
**Priority**: Critical  
**Preconditions**: Parent product "TEST-TSHIRT" exists  

**Test Steps**:
1. Click on "Assignments" tab
2. Enter "TEST-TSHIRT" in Stock ID field
3. Click "Load Product" button

**Expected Results**:
- Product details displayed
- Current assignments table shows (initially empty)
- "Add Category Assignment" form appears

**Pass/Fail Criteria**:
- PASS: Product loaded and assignment interface displayed
- FAIL: Error loading product

---

### TC-UAT-ADMIN-009: Add Category Assignment to Product
**Priority**: Critical  
**Preconditions**: Product "TEST-TSHIRT" loaded in Assignments tab  

**Test Steps**:
1. In "Add Category Assignment" section, check boxes for "Size" and "Color"
2. Click "Add Assignments" button

**Expected Results**:
- Success message: "Successfully added 2 category assignments"
- Categories appear in current assignments table

**Pass/Fail Criteria**:
- PASS: Assignments added and visible
- FAIL: Assignments not added

---

### TC-UAT-ADMIN-010: Remove Category Assignment
**Priority**: High  
**Preconditions**: "Size" category assigned to TEST-TSHIRT  

**Test Steps**:
1. In assignments table, click "Remove" link next to "Size"
2. Confirm removal in popup

**Expected Results**:
- Success message: "Category assignment removed successfully"
- Size category no longer in assignments table

**Pass/Fail Criteria**:
- PASS: Assignment removed
- FAIL: Assignment still exists

---

## Items Screen Integration Test Cases

### TC-UAT-ITEMS-001: View Product Attributes Tab (Parent Product)
**Priority**: Critical  
**Preconditions**: Parent product "TEST-TSHIRT" exists with category assignments  

**Test Steps**:
1. Navigate to Inventory → Items
2. Search for and select "TEST-TSHIRT"
3. Click on "Product Attributes" tab

**Expected Results**:
- Tab displays parent product interface
- Shows assigned categories (Size, Color)
- Displays buttons: "Create Variations", "Make Inactive", "Reactivate Variations", "Create Missing Variations"
- Shows variations table (initially empty)

**Pass/Fail Criteria**:
- PASS: Parent interface displayed with correct controls
- FAIL: Wrong interface or missing elements

---

### TC-UAT-ITEMS-002: Create Product Variations
**Priority**: Critical  
**Preconditions**: TEST-TSHIRT has Size and Color categories assigned  

**Test Steps**:
1. On Product Attributes tab for TEST-TSHIRT
2. Click "Create Variations" button
3. Check "Copy Sales Pricing" checkbox
4. Click "Generate Variations" button

**Expected Results**:
- Success message: "Successfully created 6 variations"
- Variations table shows all combinations:
  - TEST-TSHIRT-S-RED
  - TEST-TSHIRT-S-BLU
  - TEST-TSHIRT-M-RED
  - TEST-TSHIRT-M-BLU
  - TEST-TSHIRT-L-RED
  - TEST-TSHIRT-L-BLU
- Each variation has description with placeholders replaced

**Pass/Fail Criteria**:
- PASS: All 6 variations created with correct stock IDs and descriptions
- FAIL: Variations not created or incorrect

---

### TC-UAT-ITEMS-003: View Product Attributes Tab (Variation Product)
**Priority**: Critical  
**Preconditions**: Variation "TEST-TSHIRT-S-RED" exists  

**Test Steps**:
1. Navigate to Inventory → Items
2. Search for and select "TEST-TSHIRT-S-RED"
3. Click on "Product Attributes" tab

**Expected Results**:
- Tab displays variation product interface
- Shows "Assign Parent" dropdown
- Shows parent-child relationship information
- No variation creation buttons

**Pass/Fail Criteria**:
- PASS: Variation interface displayed correctly
- FAIL: Wrong interface shown

---

### TC-UAT-ITEMS-004: Assign Parent to Variation
**Priority**: High  
**Preconditions**: Variation "TEST-TSHIRT-S-RED" exists, parent "TEST-TSHIRT" exists  

**Test Steps**:
1. On Product Attributes tab for TEST-TSHIRT-S-RED
2. Select "TEST-TSHIRT" from "Assign Parent" dropdown
3. Click "Assign Parent" button

**Expected Results**:
- Success message: "Successfully assigned parent 'TEST-TSHIRT'"
- Parent relationship confirmed

**Pass/Fail Criteria**:
- PASS: Parent assigned correctly
- FAIL: Assignment fails

---

### TC-UAT-ITEMS-005: Make Parent Inactive with Variations
**Priority**: Critical  
**Preconditions**: TEST-TSHIRT has active variations, some with stock > 0  

**Test Steps**:
1. On Product Attributes tab for TEST-TSHIRT
2. Click "Make Inactive" button
3. Review warning about variations with stock > 0
4. Uncheck one variation to keep it active
5. Click "Confirm Deactivation" button

**Expected Results**:
- Success message: "Parent and selected variations deactivated"
- Parent marked inactive
- Selected variations deactivated, unchecked variation remains active

**Pass/Fail Criteria**:
- PASS: Correct variations deactivated according to selections
- FAIL: Wrong variations deactivated

---

### TC-UAT-ITEMS-006: Reactivate Variations
**Priority**: High  
**Preconditions**: TEST-TSHIRT is inactive, has inactive variations  

**Test Steps**:
1. Reactivate TEST-TSHIRT via standard FA process
2. On Product Attributes tab, click "Reactivate Variations"
3. Click "Reactivate All" button

**Expected Results**:
- Success message: "Successfully reactivated X variations"
- All variations marked active

**Pass/Fail Criteria**:
- PASS: Variations reactivated
- FAIL: Variations remain inactive

---

### TC-UAT-ITEMS-007: Create Missing Variations
**Priority**: Medium  
**Preconditions**: TEST-TSHIRT has categories but some combinations missing  

**Test Steps**:
1. On Product Attributes tab for TEST-TSHIRT
2. Click "Create Missing Variations" button
3. Select missing combinations to create
4. Click "Create Selected" button

**Expected Results**:
- Success message: "Successfully created X missing variations"
- Missing variations added to inventory

**Pass/Fail Criteria**:
- PASS: Missing variations created
- FAIL: Variations not created

---

## End-to-End Workflow Test Cases

### TC-UAT-E2E-001: Complete Product Variation Creation Cycle
**Priority**: Critical  
**Preconditions**: Clean database, admin access  

**Test Steps**:
1. Create categories "Size" and "Color" via admin interface
2. Add values to each category
3. Create parent product "TSHIRT" with description "T-Shirt ${Size} ${Color}"
4. Assign categories to parent product
5. Generate variations from Items screen
6. Verify variations created with correct stock IDs and descriptions
7. Check that variations inherit parent properties
8. Test deactivation/reactivation workflow

**Expected Results**:
- Complete workflow executes without errors
- All variations created correctly
- Parent-child relationships maintained
- Deactivation/reactivation works properly

**Pass/Fail Criteria**:
- PASS: Full workflow completes successfully
- FAIL: Any step fails or produces incorrect results

---

### TC-UAT-E2E-002: Add New Attribute to Existing Variations
**Priority**: High  
**Preconditions**: TSHIRT with Size/Color variations exists  

**Test Steps**:
1. Create new category "Material" with values "Cotton", "Polyester"
2. Assign "Material" category to TSHIRT parent
3. Generate new variations (should create 12 new combinations)
4. Verify existing variations remain unchanged
5. Check new variations have correct stock IDs (TSHIRT-S-RED-COT, etc.)

**Expected Results**:
- 12 new variations created (3 sizes × 2 colors × 2 materials)
- Existing 6 variations unchanged
- All variations have correct Royal Order sequencing

**Pass/Fail Criteria**:
- PASS: New variations created without affecting existing ones
- FAIL: Existing variations modified or new ones incorrect

---

## Error Handling and Edge Cases

### TC-UAT-ERROR-001: Attempt Create Duplicate Category
**Priority**: Medium  
**Preconditions**: Category "Size" exists  

**Test Steps**:
1. Try to create another category named "Size"
2. Click "Create Category" button

**Expected Results**:
- Error message: "Category with this name already exists"
- Category not created

**Pass/Fail Criteria**:
- PASS: Duplicate prevented with clear error
- FAIL: Duplicate created or unclear error

---

### TC-UAT-ERROR-002: Delete Category with Dependencies
**Priority**: High  
**Preconditions**: Category "Size" assigned to products  

**Test Steps**:
1. Attempt to delete "Size" category
2. Confirm deletion

**Expected Results**:
- Warning: "Category is in use by products. Deactivate instead?"
- Category soft deleted (deactivated)

**Pass/Fail Criteria**:
- PASS: Soft delete performed to preserve data integrity
- FAIL: Hard delete allowed or error

---

### TC-UAT-ERROR-003: Generate Variations Without Categories
**Priority**: Medium  
**Preconditions**: Parent product without category assignments  

**Test Steps**:
1. On Product Attributes tab, click "Create Variations"

**Expected Results**:
- Error message: "No categories assigned to this product"
- No variations created

**Pass/Fail Criteria**:
- PASS: Error caught and user informed
- FAIL: System error or unexpected behavior

---

## UI/UX Test Cases

### TC-UAT-UI-001: Responsive Design Check
**Priority**: Medium  
**Preconditions**: Admin interface loaded  

**Test Steps**:
1. Resize browser window to tablet size (768px)
2. Resize to mobile size (375px)
3. Check all tabs, tables, and forms display properly

**Expected Results**:
- Interface adapts to different screen sizes
- Tables scroll horizontally on small screens
- Buttons and forms remain accessible

**Pass/Fail Criteria**:
- PASS: Interface usable on all screen sizes
- FAIL: Elements overlap or become unusable

---

### TC-UAT-UI-002: Keyboard Navigation
**Priority**: Low  
**Preconditions**: Admin interface loaded  

**Test Steps**:
1. Use Tab key to navigate through form fields
2. Use Enter to submit forms
3. Use Space to check/uncheck boxes
4. Verify focus indicators are visible

**Expected Results**:
- All interactive elements keyboard accessible
- Logical tab order
- Clear focus indicators

**Pass/Fail Criteria**:
- PASS: Full keyboard navigation support
- FAIL: Keyboard traps or missing focus indicators

---

## Performance Test Cases

### TC-UAT-PERF-001: Large Dataset Handling
**Priority**: Medium  
**Preconditions**: Database with 100+ products and categories  

**Test Steps**:
1. Load admin interface with many categories
2. Load assignments tab for product with many assignments
3. Generate variations for product with 3 categories × 5 values each

**Expected Results**:
- Interface loads within 3 seconds
- Tables display without performance issues
- Variation generation completes within 10 seconds

**Pass/Fail Criteria**:
- PASS: Operations complete within acceptable time limits
- FAIL: Slow loading or timeouts

---

## Categories Plugin Test Cases

### TC-UAT-CAT-001: Create Product Category Hierarchy
**Priority**: Critical  
**Preconditions**: Admin user logged in, Categories plugin active  

**Test Steps**:
1. Navigate to Inventory → Stock → Product Categories
2. Click "Add Category" button
3. Enter "Electronics" as category name
4. Leave parent category empty (top-level)
5. Click "Create Category"
6. Create subcategory "Smartphones" under "Electronics"
7. Create subcategory "Laptops" under "Electronics"

**Expected Results**:
- Top-level "Electronics" category created
- "Smartphones" and "Laptops" appear as subcategories
- Hierarchical tree displays correctly

**Pass/Fail Criteria**:
- PASS: Category hierarchy created and displayed properly
- FAIL: Hierarchy not maintained or display issues

**Test Data**: Electronics > Smartphones, Electronics > Laptops

---

### TC-UAT-CAT-002: Assign Product to Categories
**Priority**: Critical  
**Preconditions**: Product exists, categories created, Categories plugin active  

**Test Steps**:
1. Navigate to Inventory → Items and select a product
2. Click "Product Categories" TAB
3. Expand category tree and select "Electronics > Smartphones"
4. Also select "Electronics > Laptops"
5. Click "Save Categories"

**Expected Results**:
- Success message: "Categories assigned successfully"
- Product shows assigned categories in details
- Categories appear in product listings

**Pass/Fail Criteria**:
- PASS: Product assigned to multiple categories
- FAIL: Assignment fails or not saved

**Test Data**: Product: TEST-PHONE, Categories: Smartphones, Laptops

---

### TC-UAT-CAT-003: Filter Products by Category
**Priority**: High  
**Preconditions**: Products assigned to categories, Categories plugin active  

**Test Steps**:
1. Navigate to Inventory → Items
2. Click category filter dropdown
3. Select "Electronics" category
4. Check "Include subcategories"
5. Apply filter

**Expected Results**:
- Product list shows only items in Electronics category
- Subcategories (Smartphones, Laptops) included
- Filter can be cleared

**Pass/Fail Criteria**:
- PASS: Correct products displayed based on category filter
- FAIL: Wrong products shown or filter not working

---

### TC-UAT-CAT-004: Bulk Category Assignment
**Priority**: Medium  
**Preconditions**: Multiple products exist, categories created  

**Test Steps**:
1. Navigate to Inventory → Stock → Product Categories
2. Click "Bulk Assign" button
3. Select multiple products from list
4. Choose "Electronics > Smartphones" category
5. Click "Assign to Selected"

**Expected Results**:
- Success message with count of assignments
- All selected products assigned to category
- Assignments visible in individual product details

**Pass/Fail Criteria**:
- PASS: Bulk assignment completes successfully
- FAIL: Partial failures or errors

---

### TC-UAT-CAT-005: Category-Based Reporting
**Priority**: Medium  
**Preconditions**: Products assigned to categories, reporting enabled  

**Test Steps**:
1. Navigate to Reports → Inventory → Category Report
2. Select "Electronics" from category filter
3. Generate report

**Expected Results**:
- Report shows products grouped by category
- Subtotals for each category level
- Export options available

**Pass/Fail Criteria**:
- PASS: Report generates with correct category groupings
- FAIL: Incorrect groupings or missing data

---

## Test Execution Summary Template

| Test Case ID | Status | Tester | Date | Notes |
|-------------|--------|--------|------|-------|
| TC-UAT-ADMIN-001 | ☐ Pass ☐ Fail ☐ Blocked | | | |
| ... | ... | ... | ... | ... |

## Test Environment Information
- **Browser**: 
- **OS**: 
- **FA Version**: 
- **PHP Version**: 
- **Database**: 

## Defect Reporting Template

| Defect ID | Test Case | Severity | Description | Steps to Reproduce | Expected | Actual | Screenshot |
|-----------|-----------|----------|-------------|-------------------|-----------|--------|------------|
| DEF-001 | TC-UAT-ADMIN-001 | Critical | | | | | |

## UAT Completion Criteria

- [ ] All Critical priority test cases pass
- [ ] All High priority test cases pass
- [ ] No Critical or High severity defects open
- [ ] Performance requirements met
- [ ] UI/UX requirements satisfied
- [ ] Cross-browser compatibility verified
- [ ] User sign-off obtained

## Sign-off

**Tested By**: _______________________ **Date**: ____________

**Approved By**: ____________________ **Date**: ____________

**Comments**: _______________________________________________________________
___________________________________________________________________________</content>
<parameter name="filePath">c:\Users\prote\Documents\FA_ProductAttributes\Project Docs\User_Acceptance_Test_Cases.md