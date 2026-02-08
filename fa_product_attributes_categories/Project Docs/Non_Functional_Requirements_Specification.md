# Non-Functional Requirements Specification (NFRS)

## Performance
- Attribute loading: <2 seconds for products with up to 10 attributes.
- Saving: <1 second.
- Concurrent users: Support up to 50 simultaneous accesses.

## Security
- Access control: Use FA's permission system (e.g., SA_ITEM, SA_STOCK).
- Data validation: Prevent SQL injection via prepared statements.
- Audit: Log changes to attributes.

## Usability
- UI consistency: Match FA's design (e.g., use FA's CSS classes).
- Accessibility: Support keyboard navigation.
- Help: Tooltips and inline help text.

## Reliability
- Availability: 99% uptime.
- Error recovery: Graceful handling of DB connection issues.

## Compatibility
- FrontAccounting: 2.3.22
- PHP: 7.3
- Browsers: IE11+, Chrome, Firefox.

## Maintainability
- Code structure: Modular, with comments.
- Documentation: Inline code docs.

## Scalability
- DB: Handle up to 10,000 products with attributes.
- Attributes: Up to 100 categories, 500 values per category.

## Portability
- OS: Windows/Linux (as per FA).
- DB: MySQL (via PDO).