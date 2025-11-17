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
  
  // Try multiple selectors for navigation items
  const navSelectors = [
    `nav a:has-text("${navText}")`,
    `aside a:has-text("${navText}")`,
    `a[href*="${routeName}"]:has-text("${navText}")`,
    `[role="navigation"] a:has-text("${navText}")`,
  ];
  
  let clicked = false;
  for (const selector of navSelectors) {
    try {
      const navItem = page.locator(selector);
      if (await navItem.isVisible({ timeout: 2000 })) {
        await navItem.click();
        clicked = true;
        break;
      }
    } catch {
      // Continue to next selector
    }
  }
  
  if (!clicked) {
    // Fallback: try direct navigation via URL
    const routeUrlMap = {
      'dashboard': '/dashboard',
      'sites.index': '/sites',
      'clients.index': '/clients',
      'tasks.index': '/tasks',
      'alerts.index': '/alerts',
      'metrics.index': '/metrics',
      'reports.index': '/reports',
      'team.index': '/team',
      'settings.index': '/settings',
    };
    const url = routeUrlMap[routeName] || `/${routeName}`;
    await page.goto(url);
  }
  
  // Wait for navigation to complete (faster than networkidle)
  await page.waitForLoadState('domcontentloaded');
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
  await page.waitForURL('**/sites', { timeout: 15000 }).catch(() => {
    // If URL doesn't match, check if we're on sites page by checking for sites table
    return page.waitForSelector('[data-testid="sites-table"], h1:has-text("Sites")', { timeout: 10000 });
  });
}

/**
 * Navigate to alerts page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToAlerts(page) {
  await navigateTo(page, 'alerts.index');
  await page.waitForURL('**/alerts', { timeout: 15000 }).catch(() => {
    return page.waitForSelector('[data-testid="alerts-table"], h1:has-text("Alerts")', { timeout: 10000 });
  });
}

/**
 * Navigate to clients page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToClients(page) {
  await navigateTo(page, 'clients.index');
  await page.waitForURL('**/clients', { timeout: 15000 }).catch(() => {
    return page.waitForSelector('[data-testid="clients-table"], h1:has-text("Clients")', { timeout: 10000 });
  });
}

/**
 * Navigate to tasks page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToTasks(page) {
  await navigateTo(page, 'tasks.index');
  await page.waitForURL('**/tasks', { timeout: 15000 }).catch(() => {
    return page.waitForSelector('[data-testid="tasks-kanban"], h1:has-text("Tasks")', { timeout: 10000 });
  });
}

/**
 * Navigate to settings page
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function goToSettings(page) {
  await navigateTo(page, 'settings.index');
  await page.waitForURL('**/settings', { timeout: 15000 }).catch(() => {
    return page.waitForSelector('h1:has-text("Settings"), [data-testid="settings-page"]', { timeout: 10000 });
  });
}

export {
  navigateTo,
  goToDashboard,
  goToSites,
  goToAlerts,
  goToClients,
  goToTasks,
  goToSettings,
};

