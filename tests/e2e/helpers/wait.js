/**
 * Wait Helper Functions
 * 
 * Provides reusable wait functions for common UI elements in E2E tests.
 * 
 * @module helpers/wait
 */

/**
 * Wait for toast notification to appear
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} message - Expected toast message (optional)
 * @param {number} timeout - Timeout in milliseconds (default: 5000)
 * @returns {Promise<void>}
 */
async function waitForToast(page, message = null, timeout = 5000) {
  const toastSelector = '[data-testid="toast"], .toast, [role="alert"]';
  
  if (message) {
    await page.waitForSelector(`${toastSelector}:has-text("${message}")`, { timeout });
  } else {
    await page.waitForSelector(toastSelector, { timeout });
  }
}

/**
 * Wait for success message
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} message - Expected success message (optional)
 * @returns {Promise<void>}
 */
async function waitForSuccessMessage(page, message = null) {
  const successSelector = '[data-testid="success-toast"], .toast-success, [role="alert"]:has-text("success")';
  
  if (message) {
    await page.waitForSelector(`${successSelector}:has-text("${message}")`, { timeout: 5000 });
  } else {
    await page.waitForSelector(successSelector, { timeout: 5000 });
  }
}

/**
 * Wait for error message
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} message - Expected error message (optional)
 * @returns {Promise<void>}
 */
async function waitForErrorMessage(page, message = null) {
  const errorSelector = '[data-testid="error-toast"], .toast-error, [role="alert"]:has-text("error")';
  
  if (message) {
    await page.waitForSelector(`${errorSelector}:has-text("${message}")`, { timeout: 5000 });
  } else {
    await page.waitForSelector(errorSelector, { timeout: 5000 });
  }
}

/**
 * Wait for loading spinner to disappear
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} timeout - Timeout in milliseconds (default: 10000)
 * @returns {Promise<void>}
 */
async function waitForLoadingToComplete(page, timeout = 10000) {
  // Wait for common loading indicators to disappear
  const loadingSelectors = [
    '[data-testid="loading"]',
    '.loading',
    '.spinner',
    '[aria-busy="true"]',
  ];
  
  for (const selector of loadingSelectors) {
    try {
      await page.waitForSelector(selector, { state: 'hidden', timeout: 2000 });
    } catch {
      // Ignore if selector doesn't exist
    }
  }
  
  // Wait for network to be idle
  await page.waitForLoadState('networkidle', { timeout });
}

/**
 * Wait for table to load with data
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} tableSelector - Table selector (default: 'table')
 * @param {number} minRows - Minimum number of rows expected (default: 1)
 * @returns {Promise<void>}
 */
async function waitForTableData(page, tableSelector = 'table', minRows = 1) {
  await page.waitForSelector(tableSelector, { state: 'visible' });
  
  // Wait for at least minRows data rows (excluding header)
  await page.waitForFunction(
    ({ selector, min }) => {
      const table = document.querySelector(selector);
      if (!table) return false;
      const rows = table.querySelectorAll('tbody tr');
      return rows.length >= min;
    },
    { selector: tableSelector, min: minRows },
    { timeout: 10000 }
  );
}

export {
  waitForToast,
  waitForSuccessMessage,
  waitForErrorMessage,
  waitForLoadingToComplete,
  waitForTableData,
};

