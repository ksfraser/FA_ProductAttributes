# Requirements Traceability Matrix (RTM)

| Requirement ID | Description | Business Need | Design Element | Test Case | Status |
|----------------|-------------|---------------|----------------|-----------|--------|
| BR1 | Add "Product Attributes" TAB to Items screen | Enhance product management | UI modification in items.php | TC1: Verify TAB appears | Pending |
| BR1.1 | Display associated attributes | View current attributes | List component on TAB | TC2: Check attribute list loads | Pending |
| BR1.2 | Add/remove attributes | Modify associations | Dropdown and save button | TC3: Test add/remove functionality | Pending |
| BR1.3 | Show Create Variations button only for parents | Restrict to parent products | Parent flag check in UI | TC4: Test button visibility | Pending |
| BR1.4 | Make Inactive button for parents | Deactivate products safely | Inactive logic with warnings | TC5: Test deactivation | Pending |
| BR1.5 | Reactivate Variations button | Re-activate product line | Rebuild and activate logic | TC6: Test reactivation | Pending |
| BR1.6 | Create Missing Variations button | Fill gaps in variations | Missing combination creation | TC7: Test missing creation | Pending |
| BR1.7 | Assign Parent dropdown for non-parents | Designate child relationships | Dropdown with sanity checks | TC8: Test parent assignment | Pending |
| BR2 | Associate attributes to products | Link attributes to stock_id | Database association table | TC9: Verify DB storage | Pending |
| BR2.1 | Select from predefined list | Data integrity | Validation in UI | TC10: Test invalid selection blocked | Pending |
| BR3 | Create variations via button, using Royal Order for stock_id and description | Generate product variations | Combination generation logic | TC11: Test variation creation | Pending |
| BR3.1 | Inherit base details | Maintain product consistency | Copy logic in DAO | TC12: Verify inherited fields | Pending |
| BR3.2 | Format stock_id with abbreviations | Unique identifiers | String concatenation | TC13: Test stock_id format | Pending |
| BR3.3 | Format description with long names | Descriptive labels | String building | TC14: Test description format | Pending |
| BR3.4 | Copy sales pricing option | Inherit pricing from master | Checkbox and price copy logic | TC15: Test price copying | Pending |
| BR3.5 | Set parent flag to false and parent_stock_id for variations | Distinguish child products | Flag and ID update in creation | TC16: Test flag and ID setting | Pending |
| BR3.6 | Generate new variations for added attributes | Extend existing product lines | Combination logic for new attrs | TC17: Test new variation generation | Pending |
| BR3.7 | Replace ${ATTRIB_CLASS} placeholders in description | Template description support | String replacement logic | TC18: Test placeholder replacement | Pending |
| BR4 | Admin screen for categories/variables | Manage attribute structure | New admin page | TC19: Verify admin menu access | Completed |
| BR4.1 | CRUD for categories, including Royal Order field | Create/edit/delete categories with ordering | Form and DB operations | TC20: Test CRUD operations | Completed |
| BR4.1.1 | Royal Order column for sequencing, with editable UI and sort options | Define attribute order | Integer field in category table, sortable table UI | TC21: Test order sorting and editing | Completed |
| BR4.2 | CRUD for variables | Add values to categories | Hierarchical UI | TC22: Test variable management | Completed |
| BR4.2.1 | Edit operations update existing records | Prevent duplicate creation on edit | ID-based update logic in DAO | TC22a: Test edit updates vs inserts | Completed |
| BR4.2.2 | Delete links use JavaScript onclick handlers | Consistent FA UI patterns | href="javascript:void(0)" with onclick | TC22b: Test delete link functionality | Completed |
| BR4.3 | Validation for usage | Prevent deletion if in use | Check associations | TC23: Test deletion blocked if used | Completed |
| BR4.3.1 | Hard delete when safe | Permanently remove unused items | Delete from DB when not referenced | TC23a: Test hard delete for unused items | Completed |
| BR4.3.2 | Soft delete when in use | Deactivate items referenced by products | Set active=false when in use | TC23b: Test soft delete for used items | Completed |
| BR4.3.3 | Cascade delete for categories | Remove category and all values when safe | Delete category + values when not used | TC23c: Test cascade deletion | Completed |
| BR4.4 | Royal Order Helper utility class | Centralized Royal Order management | RoyalOrderHelper class with SRP | TC47: Test utility functions | Completed |
| BR4.4.1 | Royal Order dropdown with predefined options | Consistent UI for sort order selection | HTML generation with 9 standard options | TC48: Test dropdown generation | Completed |
| BR4.4.2 | Sort order display formatting | Show descriptive labels in tables | "3 - Size" format in category table | TC49: Test label conversion | Completed |
| BR4.4.3 | Description column in categories table | Enhanced category information display | Added Description column to UI | TC50: Test description display | Completed |
| BR4.4.4 | Code (Slug) labeling | Clarify field purpose | Updated labels in UI and forms | TC51: Test label consistency | Completed |
| BR4.5 | Product category assignments | Assign categories to parent products | New AssignmentsTab workflow | TC52: Test category assignment to products | Completed |
| BR4.5.1 | Generate variations from category assignments | Create all value combinations as child products | GenerateVariationsAction | TC53: Test variation generation | Completed |
| BR4.5.2 | Royal Order stock_id generation | Format variation stock_ids by Royal Order | Slug concatenation in order | TC54: Test Royal Order stock_id format | Completed |
| BR4.5.3 | Parent-child product relationships | Set parent_stock_id for variations | Database relationship creation | TC55: Test parent-child linkage | Completed |
| BR1.8 | Product relationship table | Show simple/variable/variation relationships | Table with Type, Parent, Status columns | TC56: Test relationship display | Completed |
| BR1.9 | WooCommerce-style Items screen integration | Assign categories and generate variations from Items screen | UI modifications to items.php | TC57: Test Items screen functionality | Completed |
| BR1.10 | Direct variation generation from Items | Create variations without admin screen | Items screen TAB with generation logic | TC58: Test direct generation | Completed |

| BR6 | Custom pricing per variation | Individual prices | Price update sub-screen | TC24: Test pricing | Pending |
| BR6.1 | Integrate with FA_BulkPriceUpdate module | Use external bulk pricing if available | Check for module and call its function | TC25: Test integration | Pending |
| BR7 | Reporting with attributes | Filtered reports | New/modified reports | TC26: Test reporting | Pending |
| BR7.1 | Validation report for inactive parents | Identify inconsistencies | Report on 0-stock active variations | TC27: Test validation | Pending |
| BR8 | Bulk operations | Edit multiple variations | Bulk edit UI | TC28: Test bulk edits | Pending |
| BR9 | Retroactive application of module | Analyze existing products for relationships | Pattern scanning and suggestion logic | TC29: Test retroactive analysis | Pending |
| BR9.1 | Scan stock_ids for variation patterns | Identify potential groups | Regex or string matching on stock_ids | TC30: Test pattern detection | Pending |
| BR9.2 | Suggest parent creation for groups | Propose new parents | Logic to infer parent stock_id | TC31: Test parent suggestions | Pending |
| BR9.3 | Suggest parent-child associations | Link existing products | Hierarchy detection | TC32: Test association suggestions | Pending |
| BR9.4 | Bulk edit screen for assignments | Assign multiple at once | UI with checkboxes and assign button | TC33: Test bulk assignment | Pending |
| BR9.5 | Sanity checks and force options | Validate assignments | Warning dialogs and confirmations | TC34: Test validation and force | Pending |
| BR10 | API for external integration | REST endpoints for CRUD | External system access | TC35: Test API endpoints | Completed |
| BR10.1 | Authentication and security | API key validation | Secure access | TC36: Test auth mechanisms | Completed |
| NFR1 | Seamless integration | No disruption to FA | Hooks-based implementation | TC37: Test core FA unchanged | Pending |
| NFR2 | Security | Authorized access with greyed UI | Permission checks | TC38: Test unauthorized access denied | Pending |
| NFR3 | Performance | Efficient loading/saving | Optimized queries | TC39: Test load times | Pending |
| NFR4 | Usability | Intuitive UI with tooltips/confirmations | User-friendly elements | TC40: User acceptance testing | Completed |
| NFR5 | Data persistence | Extend DB schema with parent_stock_id | New tables in schema.sql | TC41: Verify DB schema | Completed |
| NFR5.1 | Data integrity via Make Inactive | Prevent orphans | Deactivate with warnings | TC42: Test deactivation | Pending |
| NFR6 | Compatibility | FA 2.3.22 and PHP 7.3 | Code compatibility | TC43: Test on specified versions | Pending |
| NFR7 | Code Quality | SOLID principles, DI, SRP | Interfaces, traits, polymorphism, RoyalOrderHelper | TC44: Test adherence | Completed |
| NFR8 | Testing | Unit tests for all code, edge cases | PHPUnit framework, 73 tests, 241 assertions | TC45: Test coverage metrics | Completed |
| NFR9 | Documentation | PHPDoc, UML diagrams | ERD, Message Flow, flowcharts | TC46: Verify completeness | Pending |

## Notes
- Requirement IDs correspond to sections in BRD.
- Test Cases to be defined in detail during testing phase.
- Status: Pending until implementation begins.