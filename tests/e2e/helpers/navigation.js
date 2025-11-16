/**
 * Navigation Helper Functions
 * 
 * Provides reusable functions for navigating between pages in E2E tests.
 * 
 * @module helpers/navigation
 */

/**
 * Navigate to a specific route using sidebar navigation
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} routeName - Route name (e.g., 'sites.index', 'dashboard')
 * @returns {Promise<void>}
 */
async function navigateTo(page, routeName) {
  // Map route names to navigation text
  const routeMap = {
    'dashboard': 'Overview',
    'sites.index': 'Sites',
    'clients.index': 'Clients',
    'tasks.index': 'Tasks',
    'alerts.index': 'Alerts',
    'metrics.index': 'Metrics',
    'reports.index': 'Reports',
    'team.index': 'Team',
    'settings.index': 'Settings',
  };
  
  const navText = routeMap[routeName] || routeName;
  
  // Click navigation item
  await page.click(`nav a:has-text("${navText}")`);
  
  // Wait for navigation to complete
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to dashboard
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToDashboard(page) {
  await navigateTo(page, 'dashboard');
  await page.waitForURL('**/dashboard');
}

/**
 * Navigate to sites list
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToSites(page) {
  await navigateTo(page, 'sites.index');
  await page.waitForURL('**/sites');
}

/**
 * Navigate to alerts page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToAlerts(page) {
  await navigateTo(page, 'alerts.index');
  await page.waitForURL('**/alerts');
}

/**
 * Navigate to clients page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToClients(page) {
  await navigateTo(page, 'clients.index');
  await page.waitForURL('**/clients');
}

/**
 * Navigate to tasks page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToTasks(page) {
  await navigateTo(page, 'tasks.index');
  await page.waitForURL('**/tasks');
}

/**
 * Navigate to settings page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToSettings(page) {
  await navigateTo(page, 'settings.index');
  await page.waitForURL('**/settings');
}

module.exports = {
  navigateTo,
  goToDashboard,
  goToSites,
  goToAlerts,
  goToClients,
  goToTasks,
  goToSettings,
};

