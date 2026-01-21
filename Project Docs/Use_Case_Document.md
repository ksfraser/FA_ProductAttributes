# Use Case Document

## Use Case: Create Product Variations for New Product Line

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Master product is created in FA (parent flag = true).
- Attribute categories and values are defined (or will be created).

### Main Flow
1. Product Manager navigates to the Items screen for the master product.
2. On the "Product Attributes" TAB, selects applicable categories (e.g., Size, Color).
3. If categories don't exist, accesses the admin screen (Inventory > Stock > Product Attributes) to create them:
   - Views sortable table of categories (sort by Name or Royal Order).
   - Edits Royal Order values inline or via form.
   - Creates category "Size" with Royal Order 1, values "Small (S)", "Medium (M)", "Large (L)".
   - Creates category "Color" with Royal Order 2, values "Red (RED)", "Blue (BLU)".
4. Returns to the product TAB and attaches categories to the master product.
5. Selects specific values for each category to define variations (e.g., all combinations: S-RED, S-BLU, M-RED, etc.).
6. Clicks "Create Variations" button, with option to check "Copy Sales Pricing" to inherit prices from the master product.
7. System generates child products:
   - Uses "Royal Order of adjectives" for attribute ordering (based on category Royal Order values, e.g., Size=1 before Color=2).
   - Stock_id: Parent stock_id + attribute abbreviations in order (e.g., XYZ-L-RED).
   - Short description: Replace ${ATTRIB_CLASS} placeholders in parent description with long attribute names (e.g., if parent has "Coat - ${Size} ${Color}", variation becomes "Coat - Large Red").
   - If "Copy Sales Pricing" checked, copies all sales prices from master to each variation.
8. Each child product inherits base details from master but has unique stock_id and description, with parent flag set to false.
9. System confirms creation and lists generated variations.
10. Users can manually deactivate any unwanted variations using FA's standard product deactivation features.

### Postconditions
- Child products are created and available in FA inventory.
- Master product remains unchanged.

### Alternative Flows
- If category already exists: Skip creation step.
- If no values selected: Display error "Select at least one value per category".
- If stock_id conflict: Append unique suffix (e.g., XYZ-L-RED-1).

### Exceptions
- Insufficient permissions: Deny access.
- DB error: Rollback and notify user.

## Use Case: Add New Attribute to Existing Product Line and Generate Variations

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Master product exists with existing variations (parent flag = true).
- New attribute category (e.g., Color) is defined or will be created.

### Main Flow
1. Product Manager navigates to the Items screen for the master product.
2. On the "Product Attributes" TAB, views existing associated categories (e.g., Size, Slogan).
3. Adds the new category (e.g., Color) and selects values (e.g., White, Blue).
4. Clicks "Create Variations" button, with option to check "Copy Sales Pricing".
5. System identifies existing variations and generates new combinations including the new attribute.
   - For example, if existing: T-Shirt-S-Quote1, T-Shirt-M-Quote1; new: T-Shirt-S-Quote1-White, T-Shirt-S-Quote1-Blue, etc.
   - Uses "Royal Order of adjectives" for attribute ordering (based on category Royal Order values).
   - Stock_id: Parent stock_id + attribute abbreviations in order (e.g., TSHIRT-S-QUOTE1-WHT).
   - Short description: Replace ${ATTRIB_CLASS} placeholders in parent description with long attribute names (e.g., if parent has "T-Shirt - ${Size} ${Quote} ${Color}", variation becomes "T-Shirt - Small Quote1 White").
   - If "Copy Sales Pricing" checked, copies prices from master or existing variations.
6. Existing variations remain unchanged; new variations are added.
7. System confirms creation and lists all variations (existing + new).
8. Users can manually deactivate any unwanted new variations using FA's standard product deactivation features.

### Postconditions
- New variation products are created and available in FA inventory.
- Master product and existing variations are unaffected.

### Alternative Flows
- If new category already exists: Select from list.
- If no new values selected: Warn user.
- If stock_id conflicts: Append unique suffix.

### Exceptions
- Insufficient permissions: Deny access.
- DB error: Rollback new additions.

### Business Rules
- Royal Order: Attributes ordered by category Royal Order.
- Abbreviations: Short codes for new attributes.
- Inheritance: New variations inherit from master, with parent flag = false.

## Use Case: Deactivate Parent Product and Variations

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Parent product exists with variations (parent flag = true).

### Main Flow
1. Product Manager navigates to the Items screen for the parent product.
2. On the "Product Attributes" TAB, clicks "Make Inactive".
3. System checks variations:
   - Lists variations with stock >0 in a warning.
   - Defaults to deactivate variations with stock =0.
4. User confirms, optionally unchecking variations with stock >0 to keep active.
5. System deactivates parent and selected variations.
6. Confirmation shown.

### Postconditions
- Parent and selected variations are inactive.
- Unselected variations remain active.

### Alternative Flows
- If no variations with stock >0: Deactivate all automatically.
- Cancel: No changes.

### Exceptions
- Insufficient permissions: Deny.

### Business Rules
- Deactivation sets inactive flag in FA.
- Variations with stock >0 can be kept active.

## Use Case: Validate Inactive Parent Products with Active 0-Stock Variations

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Inactive parent products exist.

### Main Flow
1. Product Manager accesses a validation report or tool (e.g., via admin screen or TAB).
2. System scans for inactive parents with variations that are active and have stock =0.
3. Displays list of such inconsistencies.
4. For each, user can choose to deactivate the variation or mark as resolved.
5. System updates accordingly.

### Postconditions
- Inconsistencies resolved.

### Alternative Flows
- Auto-deactivate: Option to automatically deactivate all 0-stock variations for inactive parents.

### Exceptions
- No inconsistencies: Show message.

### Business Rules
- Inactive parents should not have active 0-stock variations.
- Validation ensures data integrity.

## Use Case: Reactivate Product Line

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Inactive parent product exists with associated attributes.

### Main Flow
1. Product Manager navigates to the Items screen for the inactive parent product.
2. Re-activates the parent product via FA's standard process.
3. On the "Product Attributes" TAB, clicks "Reactivate Variations".
4. System rebuilds expected variation combinations based on current attributes.
5. Searches for inactive variations matching the combinations and re-activates them.
6. For missing variations, prompts to create them (links to "Create Missing Variations" use case).
7. User selects which to create/activate.
8. System activates selected existing and creates new ones.
9. Confirmation shown.

### Postconditions
- Parent and variations are active.
- Missing variations created if chosen.

### Alternative Flows
- No inactive variations: Proceed to create missing.
- Cancel: No changes.

### Exceptions
- Insufficient permissions: Deny.

### Business Rules
- Re-activation sets active flag.
- Shares routines with "Create Variations".

## Use Case: Create Missing Variations

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Active parent product with attributes.

### Main Flow
1. Product Manager navigates to the Items screen for the parent product.
2. On the "Product Attributes" TAB, clicks "Create Missing Variations".
3. System generates all possible combinations from attached attributes.
4. Identifies existing variations (active or inactive).
5. Lists missing combinations.
6. User selects which to create.
7. System creates selected variations (inherits from parent, sets parent flag = false).
8. Confirmation shown.

### Postconditions
- Missing variations created.

### Alternative Flows
- All exist: Message "No missing variations".
- Cancel: No changes.

### Exceptions
- DB error: Rollback.

### Business Rules
- Shares generation logic with "Create Variations".
- New variations follow same rules (stock_id, description).

## Use Case: Designate Product as Child of Parent

### Actors
- Product Manager (Primary Actor)

### Preconditions
- Product Manager has access to FrontAccounting.
- Existing products: potential parents (parent flag = true) and child candidates.

### Main Flow
1. Product Manager navigates to the Items screen for the potential child product.
2. On the "Product Attributes" TAB, selects "Assign Parent" dropdown with list of parent products.
3. Clicks "Assign" button.
4. System checks sanity:
   - If child stock_id starts with parent stock_id + separator, no warning.
   - If not, warning of mismatch.
5. If mismatch, "Are you sure?" dialog with "Force" option.
6. On confirm, sets parent_stock_id to selected parent, parent flag to false.
7. To edit, repeat with different parent.

### Postconditions
- Product designated as child of selected parent.

### Alternative Flows
- Cancel assignment: No changes.
- Force: Allows mismatched stock_ids.

### Exceptions
- No parents available: Message.
- Insufficient permissions: Deny.

### Business Rules
- Allow associations even with mismatched roots (e.g., BA-1020-VS child of BA).
- Sanity check with force option for mismatches.

## Use Case: Retroactive Application of Module

### Actors
- Administrator (Primary Actor)

### Preconditions
- Administrator has access to FrontAccounting.
- Existing products in inventory with stock_ids that may follow variation patterns.
- Attribute categories and values are defined.

### Main Flow
1. Administrator navigates to Inventory > Stock > Retroactive Attributes (new menu item).
2. System scans all existing stock_ids in the database.
3. Analyzes patterns based on Royal Order and attribute abbreviations to identify potential variation groups and hierarchies.
4. Displays a list of suggested relationships:
   - Suggested parent creations (e.g., for BM-SG1, BM-SG2, BM-SG3, suggest BM-SG parent).
   - Suggested parent-child associations (e.g., A-B-C as parent for A-B-C-D and A-B-C-E).
5. Administrator reviews the suggestions.
6. For each suggestion, clicks to assign or accesses bulk edit screen.
7. In bulk edit screen, selects multiple suggested child products and chooses a parent to assign them to.
8. For each assignment, system performs sanity checks (e.g., stock_id root matching).
9. If mismatch, shows warning and offers force option with confirmation dialog.
10. On confirmation, updates parent_stock_id and parent flag for assigned products.
11. System confirms assignments and updates the list.

### Postconditions
- Suggested relationships are assigned where accepted.
- Products have updated parent-child links.

### Alternative Flows
- No suggestions found: Display message "No patterns detected".
- Create suggested parent: Option to create the parent product first if it doesn't exist.
- Bulk assign: Select all suggestions for a group and assign at once.

### Exceptions
- Insufficient permissions: Deny access.
- DB error: Rollback and notify.

### Business Rules
- Patterns based on attribute abbreviations and Royal Order sequencing.
- Sanity checks warn on root mismatches but allow force assignment.

## Use Case: Manage Attribute Categories and Values (Admin)

### Actors
- Administrator (Primary Actor)

### Preconditions
- Administrator has access to FrontAccounting.
- Admin permissions for attribute management.

### Main Flow
1. Administrator navigates to Inventory > Stock > Product Attributes.
2. System displays three tabs: Categories, Values, Assignments.
3. On Categories tab:
   - Views sortable table of categories (columns: Name, Royal Order, Actions).
   - Clicks "Add Category" to create new category with name and Royal Order.
   - Clicks Edit link to modify existing category (form pre-fills with current data).
   - Clicks Delete link to remove category:
     - If category is NOT used by any products: Permanently deletes category and all its values
     - If category IS used by products: Deactivates category (soft delete) to preserve references
     - Shows confirmation dialog in both cases with appropriate messaging.
   - Edits Royal Order values inline to change attribute sequencing.
4. On Values tab:
   - Selects category from dropdown to view its values.
   - Views table of values (columns: Value, Slug, Sort Order, Active, Actions).
   - Clicks "Add Value" to create new value for selected category.
   - Clicks Edit link to modify existing value (form pre-fills, updates existing record).
   - Clicks Delete link to remove value:
     - If value is NOT used by any products: Permanently deletes the value
     - If value IS used by products: Deactivates value (soft delete) to preserve references
     - Shows confirmation dialog in both cases with appropriate messaging.
5. On Assignments tab:
   - Selects product by stock_id to view its attribute assignments.
   - Views table of assignments (columns: Category, Value, Slug, Sort Order, Actions).
   - Clicks Delete link to remove assignment from product (confirmation dialog).
6. System validates all operations and prevents invalid deletions.
7. Success/error messages displayed for all operations.

### Postconditions
- Attribute structure updated according to admin actions.
- Data integrity maintained (no orphaned references).

### Alternative Flows
- Edit without changes: Form saves unchanged data.
- Delete blocked: Error message explains why deletion prevented.
- No category selected for values: Prompt to select category.

### Exceptions
- Insufficient permissions: Access denied.
- DB constraint violations: Rollback with error message.
- Invalid data: Validation errors displayed.

### Business Rules
- Categories have unique codes, values unique within categories.
- Royal Order determines attribute sequencing in stock_ids and descriptions.
- Delete operations check usage before deletion:
  - Hard delete (permanent removal) when items are not referenced by products
  - Soft delete (deactivation) when items are referenced by products to preserve data integrity
  - Categories: cascade delete removes category and all its values when safe
- Edit operations update existing records, never create duplicates.