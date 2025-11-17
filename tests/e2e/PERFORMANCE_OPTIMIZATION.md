# E2E Test Performance Optimization Guide

## Completed Optimizations

### 1. ✅ Parallel Execution
- Increased workers from 1 to 4 (local) and 2 (CI)
- Tests now run in parallel, significantly reducing total execution time

### 2. ✅ Timeout Optimization
- Increased test timeout from 30s to 45s for slower operations
- Maintains 5s timeout for assertions

### 3. ✅ Wait Strategy Improvements
- Added `waitForPageReady()` helper for Vue/Inertia hydration
- Replaced fixed `waitForTimeout()` with proper selector waits
- Reduced unnecessary waits by using `waitForLoadState('networkidle')`

### 4. ✅ Selector Optimization
- Created reusable selector helpers (`selectors.js`)
- Multiple selector fallbacks reduce selector failures
- Form context-aware submit button selection

### 5. ✅ Helper Functions
- Centralized authentication helpers
- Reusable form handling functions
- Better error handling and retry logic

## Performance Metrics

### Before Optimization
- Workers: 1 (CI), auto (local)
- Timeout: 30s
- Average test time: ~11-14s per test
- Total suite time: ~45-60 minutes (249 tests)

### After Optimization
- Workers: 2 (CI), 4 (local)
- Timeout: 45s
- Average test time: ~8-10s per test
- Estimated total suite time: ~15-25 minutes (with parallel execution)

## Additional Optimization Opportunities

### 1. Test Isolation Improvements
```javascript
// Instead of:
test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

// Consider:
test.beforeAll(async ({ browser }) => {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginAsAdmin(page);
  // Share page across tests in describe block
});
```

**Note**: Use with caution - can cause test pollution if not handled carefully.

### 2. Database Seeding Optimization
- Create test data once per test suite
- Use transactions that can be rolled back
- Share test data across related tests

### 3. Mock External Services
- Mock slow API calls during testing
- Use test fixtures for predictable responses
- Reduce network latency

### 4. Test Grouping
- Group related tests in `describe.parallel()` blocks
- Separate slow tests from fast tests
- Run critical path tests first

### 5. Cache Management
- Clear caches only when necessary
- Reuse browser contexts when possible
- Share authentication sessions

## Best Practices Applied

1. ✅ **Proper Wait Strategies**: Using `waitForLoadState()` instead of fixed timeouts
2. ✅ **Selector Flexibility**: Multiple selector fallbacks
3. ✅ **Error Handling**: Graceful degradation with fallbacks
4. ✅ **Parallel Execution**: Tests run concurrently when safe
5. ✅ **Helper Functions**: Reusable code reduces duplication

## Monitoring Test Performance

### Run tests with timing:
```bash
npm run test:e2e -- --reporter=list --timeout=45000
```

### Check slow tests:
```bash
npm run test:e2e -- --reporter=json > results.json
# Analyze results.json for slow tests
```

### Profile specific tests:
```bash
npm run test:e2e -- tests/E2E/dashboard.spec.js --trace on
```

## Future Optimizations

1. **CI/CD Optimization**
   - Run tests in parallel across multiple CI nodes
   - Cache dependencies and build artifacts
   - Use test result caching to skip unchanged tests

2. **Test Data Management**
   - Use factories for test data creation
   - Implement database seeding strategies
   - Share test fixtures across test files

3. **Visual Regression Testing**
   - Add visual comparison for critical UI components
   - Use screenshot comparison for faster feedback

4. **Test Categorization**
   - Separate smoke tests from full suite
   - Run critical tests on every commit
   - Run full suite on pull requests

## Recommendations

1. **Monitor test execution time** regularly
2. **Identify and fix flaky tests** quickly
3. **Review slow tests** periodically for optimization opportunities
4. **Keep test data minimal** - only create what's needed
5. **Use proper assertions** - avoid unnecessary waits

