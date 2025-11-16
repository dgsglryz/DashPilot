/**
 * Authentication Helper Functions
 * 
 * Provides reusable functions for login/logout operations in E2E tests.
 * 
 * @module helpers/auth
 */

/**
 * Login as admin user
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {Object} credentials - Login credentials
 * @param {string} credentials.email - User email (default: admin@test.com)
 * @param {string} credentials.password - User password (default: password)
 * @returns {Promise<void>}
 */
async function loginAsAdmin(page, { email = 'admin@test.com', password = 'password' } = {}) {
  await page.goto('/login');
  
  // Wait for login form
  await page.waitForSelector('input[name="email"]', { state: 'visible' });
  
  // Fill login form
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  
  // Submit form
  await page.click('button[type="submit"]');
  
  // Wait for redirect to dashboard
  await page.waitForURL('**/dashboard', { timeout: 10000 });
  
  // Verify we're logged in (check for dashboard content)
  await page.waitForSelector('[data-testid="dashboard-stats"]', { timeout: 5000 }).catch(() => {
    // Fallback: check for any dashboard element
    return page.waitForSelector('h1:has-text("Overview")', { timeout: 5000 });
  });
}

/**
 * Logout current user
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function logout(page) {
  // Click logout button (in sidebar)
  await page.click('a[href="/logout"]');
  
  // Wait for redirect to home/login
  await page.waitForURL(/\/(login|$)/, { timeout: 10000 });
}

/**
 * Check if user is logged in
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<boolean>}
 */
async function isLoggedIn(page) {
  try {
    // Check for authenticated user indicator (sidebar, user menu, etc.)
    const hasSidebar = await page.locator('aside').isVisible().catch(() => false);
    const hasLogoutButton = await page.locator('a[href="/logout"]').isVisible().catch(() => false);
    return hasSidebar || hasLogoutButton;
  } catch {
    return false;
  }
}

module.exports = {
  loginAsAdmin,
  logout,
  isLoggedIn,
};

