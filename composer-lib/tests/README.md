# Testing Strategy

This project uses Test-Driven Development (TDD) with PHPUnit for comprehensive test coverage.

## Test Structure

```
tests/
├── Db/                    # Database adapter tests
│   ├── DatabaseAdapterFactoryTest.php
│   └── DbAdapterTestCase.php
├── Dao/                   # Data Access Object tests
├── Actions/              # Business logic action tests
├── UI/                   # User interface tests
└── bootstrap.php         # Test bootstrap
```

## Running Tests

```bash
# From composer-lib directory
./phpunit.phar

# Or if phpunit is installed globally
phpunit

# With coverage
phpunit --coverage-html=coverage
```

## Testing Philosophy

### Why Not Symfony/Doctrine?

While Symfony provides excellent ORM capabilities with Doctrine, we've chosen a lightweight approach for several reasons:

1. **FrontAccounting Integration**: FA has its own database patterns and session management
2. **Minimal Dependencies**: Avoid pulling in heavy frameworks that might conflict
3. **Performance**: Direct SQL queries are faster than ORM overhead for simple operations
4. **Control**: Full control over SQL generation and optimization
5. **Learning**: Educational value in building database abstractions

### Our Testing Approach

1. **Unit Tests**: Test individual classes in isolation using mocks
2. **Integration Tests**: Test database operations with real adapters
3. **Contract Tests**: Ensure all adapters implement interfaces correctly
4. **TDD**: Write tests before implementation where possible

### Database Adapter Testing

Since database adapters interact with external systems, we use:

- **Mocks** for unit tests (test logic without DB calls)
- **Test Databases** for integration tests (verify actual SQL execution)
- **Contract Tests** to ensure all adapters behave consistently

## Test Categories

### DbAdapterInterface Contract Tests
- All adapters return proper types
- Error handling is consistent
- Interface methods are implemented

### DAO Tests
- Business logic is correct
- SQL generation is proper
- Error conditions are handled

### Action Tests
- Form processing works
- Validation is enforced
- Success/error responses are correct

### Integration Tests
- Full request/response cycles
- Database state changes
- End-to-end functionality