# Remaining Test Fixes - Systematic Approach

## ✅ Completed Fixes

1. **TextInput Component** - Fixed attribute forwarding (`v-bind="attrs"`)
2. **Auth Helpers** - Updated selectors and wait strategies
3. **Selector Helpers** - Created reusable selector functions
4. **Global Setup** - Added server availability check
5. **Auth Tests** - Fixed auth-comprehensive.spec.js and auth.spec.js

## 🔧 Remaining Work

### Phase 1: Fix Common Patterns in All Tests

Create a script or manually update all test files that use:
- `input[name="..."]` → Use `getInputSelector()` helper
- `textarea[name="..."]` → Use `getTextareaSelector()` helper  
- `button[type="submit"]` → Use `getSubmitButtonInForm()` helper
- Missing `waitForLoadState('networkidle')` → Add after `page.goto()`

### Phase 2: Fix Test-Specific Issues

For each test file:
1. Import selector helpers:
```javascript
import { getInputSelector, getTextareaSelector, getSubmitButtonInForm, waitForFormReady } from './helpers/selectors.js';
```

2. Replace input selectors:
```javascript
// Before
await page.fill('input[name="name"]', 'Test');

// After
const nameSelector = getInputSelector('name');
await page.waitForSelector(nameSelector, { state: 'visible', timeout: 15000 });
await page.fill(nameSelector, 'Test');
```

3. Add wait strategies:
```javascript
// After page.goto()
await page.goto('/sites');
await page.waitForLoadState('networkidle', { timeout: 30000 });
await page.waitForTimeout(500); // For Vue hydration
```

4. Fix submit buttons:
```javascript
// Before
await page.click('button[type="submit"]');

// After
const submitButton = await getSubmitButtonInForm(page, 'form'); // or specific form selector
await submitButton.click();
```

### Phase 3: Files to Fix

Priority order:

1. **High Priority** (Core functionality):
   - ✅ `auth.spec.js` - DONE
   - ✅ `auth-comprehensive.spec.js` - DONE
   - ⚠️ `sites.spec.js` - IN PROGRESS
   - ⚠️ `sites-comprehensive.spec.js`
   - ⚠️ `dashboard.spec.js`
   - ⚠️ `dashboard-comprehensive.spec.js`

2. **Medium Priority** (Important features):
   - ⚠️ `clients.spec.js`
   - ⚠️ `tasks.spec.js`
   - ⚠️ `tasks-kanban.spec.js`
   - ⚠️ `alerts.spec.js`
   - ⚠️ `alerts-comprehensive.spec.js`
   - ⚠️ `settings.spec.js`

3. **Low Priority** (Extended features):
   - ⚠️ `comprehensive.spec.js`
   - ⚠️ `additional-pages.spec.js`
   - ⚠️ `navigation-comprehensive.spec.js`
   - ⚠️ `search-comprehensive.spec.js`
   - ⚠️ `notifications.spec.js`
   - ⚠️ `empty-states.spec.js`
   - ⚠️ `error-states.spec.js`
   - ⚠️ `edge-cases.spec.js`
   - ⚠️ `performance.spec.js`
   - ⚠️ `export-reports.spec.js`

### Phase 4: Add Missing Test Scenarios

Check for missing test coverage:
- [ ] Error handling for network failures
- [ ] Loading states for async operations
- [ ] Form validation edge cases
- [ ] Bulk operations error handling
- [ ] Webhook delivery failure scenarios
- [ ] Redis cache invalidation
- [ ] Queue job retry scenarios

### Phase 5: Performance Optimization

1. **Parallel Execution**:
   - Increase workers in playwright.config.js
   - Use `test.describe.serial()` only when needed

2. **Test Isolation**:
   - Use `test.beforeAll()` for shared setup
   - Create test data once, reuse across tests

3. **Reduce Wait Times**:
   - Replace fixed `waitForTimeout()` with proper selectors
   - Use `waitForLoadState('networkidle')` instead of fixed delays

4. **Optimize Selectors**:
   - Use `data-testid` attributes where possible
   - Cache frequently used selectors

## Quick Fix Script Template

```javascript
// Example: Fixing sites.spec.js

import { getInputSelector, getTextareaSelector, getSubmitButtonInForm, waitForFormReady } from './helpers/selectors.js';

// In test:
test('should create new WordPress site', async ({ page }) => {
  await page.click('[data-testid="add-site-button"], a:has-text("Add Site")');
  await page.waitForURL(/\/sites\/create/);
  
  // Wait for form to be ready
  await waitForFormReady(page);
  
  // Use selector helpers
  const nameSelector = getInputSelector('name');
  const urlSelector = getInputSelector('url');
  
  await page.waitForSelector(nameSelector, { state: 'visible', timeout: 15000 });
  await page.fill(nameSelector, 'Test WordPress Site');
  await page.fill(urlSelector, 'https://test-wordpress-site.com');
  
  await page.selectOption('select[name="type"], select[name="platform"]', 'wordpress');
  
  // Submit using helper
  const submitButton = await getSubmitButtonInForm(page, 'form');
  await submitButton.click();
  
  await page.waitForURL(/\/sites\/\d+/, { timeout: 15000 });
  await page.waitForLoadState('networkidle');
  
  await expect(page.locator('text=/Test WordPress Site/i')).toBeVisible();
});
```

## Testing Strategy

1. **Run tests incrementally**:
   ```bash
   npm run test:e2e -- tests/E2E/sites.spec.js
   ```

2. **Check failures**:
   ```bash
   npm run test:e2e -- --reporter=line 2>&1 | grep -A 20 "Error\|Timeout"
   ```

3. **View screenshots**:
   - Check `test-results/` folder for failure screenshots

4. **Debug failing tests**:
   ```bash
   npm run test:e2e -- --debug tests/E2E/sites.spec.js
   ```

## Common Issues & Solutions

### Issue: Timeout waiting for selector
**Solution**: Increase timeout, add `waitForLoadState('networkidle')`

### Issue: Selector not found
**Solution**: Use flexible selectors from helpers, check Vue hydration

### Issue: Form submit button wrong button
**Solution**: Use `getSubmitButtonInForm()` helper

### Issue: Test flaky (sometimes passes, sometimes fails)
**Solution**: Add proper wait strategies, reduce timing dependencies

## Progress Tracking

- [x] Auth tests fixed
- [ ] Sites tests fixed
- [ ] Dashboard tests fixed
- [ ] Clients tests fixed
- [ ] Tasks tests fixed
- [ ] Alerts tests fixed
- [ ] Settings tests fixed
- [ ] Additional pages tests fixed
- [ ] Missing scenarios added
- [ ] Performance optimized

