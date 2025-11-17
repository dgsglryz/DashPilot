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
 * Optimized version: uses domcontentloaded instead of networkidle for faster execution
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} timeout - Timeout in milliseconds (default: 5000)
 * @returns {Promise<void>}
 */
async function waitForLoadingToComplete(page, timeout = 5000) {
  // Wait for common loading indicators to disappear (with shorter timeout)
  const loadingSelectors = [
    '[data-testid="loading"]',
    '.loading',
    '.spinner',
    '[aria-busy="true"]',
  ];
  
  // Wait for any loading indicators to disappear (parallel check)
  await Promise.allSettled(
    loadingSelectors.map(selector =>
      page.waitForSelector(selector, { state: 'hidden', timeout: 2000 }).catch(() => {
        // Ignore if selector doesn't exist
      })
    )
  );
  
  // Wait for DOM to be ready (faster than networkidle)
  await page.waitForLoadState('domcontentloaded', { timeout });
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

/**
 * Wait for Vue/Inertia page to be fully loaded and hydrated
 * 
 * Optimized version: uses domcontentloaded instead of networkidle for faster execution
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} timeout - Timeout in milliseconds (default: 10000)
 * @returns {Promise<void>}
 */
async function waitForPageReady(page, timeout = 10000) {
  // Wait for DOM to be ready (faster than networkidle)
  await page.waitForLoadState('domcontentloaded', { timeout });
  
  // Wait for Vue/Inertia to hydrate by checking if Inertia is available
  // Use shorter timeout for faster failure detection
  await page.waitForFunction(
    () => {
      // Check if Inertia is loaded (window.Inertia or __INERTIA__)
      return typeof window !== 'undefined' && (
        (window.Inertia !== undefined) ||
        (window.__INERTIA__ !== undefined) ||
        // Fallback: check if page has been hydrated by looking for common Vue/Inertia patterns
        document.querySelector('[data-page]') !== null ||
        document.querySelector('[data-inertia]') !== null
      );
    },
    { timeout: 5000 }
  ).catch(() => {
    // If Inertia check fails, check for sidebar/navigation as fallback
    return page.waitForSelector('aside, nav[role="navigation"]', {
      timeout: 2000,
      state: 'visible',
    }).catch(() => {
      // If that also fails, page might not be fully loaded, but continue anyway
    });
  });
}

/**
 * Wait for debounce/input delay (replaces waitForTimeout for inputs)
 * 
 * Waits for input to be stable and potential debounce to complete.
 * Uses faster wait strategies instead of fixed timeout.
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} timeout - Maximum wait time in milliseconds (default: 500)
 * @returns {Promise<void>}
 */
async function waitForDebounce(page, timeout = 500) {
  // Wait for DOM to be ready (faster than fixed timeout)
  await page.waitForLoadState('domcontentloaded', { timeout });
  
  // Wait a very short time for any pending operations (minimal)
  await new Promise(resolve => setTimeout(resolve, 100));
}

/**
 * Wait for UI update after action (replaces waitForTimeout for UI updates)
 * 
 * Waits for UI to update after an action (click, type, etc.)
 * Uses element visibility checks instead of fixed timeout.
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} selector - Selector to wait for (optional)
 * @param {number} timeout - Maximum wait time in milliseconds (default: 500)
 * @returns {Promise<void>}
 */
async function waitForUIUpdate(page, selector = null, timeout = 500) {
  if (selector) {
    // Wait for specific element to appear/change
    await page.waitForSelector(selector, { 
      state: 'visible', 
      timeout: Math.min(timeout, 3000) 
    }).catch(() => {
      // If selector not found, continue anyway (might not be necessary)
    });
  } else {
    // Wait for DOM to be ready (faster than fixed timeout)
    await page.waitForLoadState('domcontentloaded', { timeout: 200 });
  }
}

/**
 * Fast wait - minimal delay for animations/interactions
 * 
 * Only use when absolutely necessary. Prefer waitForSelector or waitForLoadState.
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} ms - Milliseconds to wait (should be <= 200)
 * @returns {Promise<void>}
 */
async function fastWait(page, ms = 100) {
  // Only allow very short waits (max 200ms)
  const actualMs = Math.min(ms, 200);
  await new Promise(resolve => setTimeout(resolve, actualMs));
}

export {
  waitForToast,
  waitForSuccessMessage,
  waitForErrorMessage,
  waitForLoadingToComplete,
  waitForTableData,
  waitForPageReady,
  waitForDebounce,
  waitForUIUpdate,
  fastWait,
};

