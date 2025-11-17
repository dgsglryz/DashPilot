/**
 * Fast Wait Helper Functions
 * 
 * Optimized wait functions that use proper Playwright waits instead of arbitrary timeouts.
 * These functions are faster and more reliable than waitForTimeout.
 * 
 * @module helpers/fast-wait
 */

/**
 * Wait for element to be visible (replaces waitForTimeout + selector check)
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} selector - Element selector
 * @param {number} timeout - Timeout in milliseconds (default: 5000)
 * @returns {Promise<void>}
 */
async function waitForVisible(page, selector, timeout = 5000) {
  await page.waitForSelector(selector, { state: 'visible', timeout });
}

/**
 * Wait for element to be hidden (replaces waitForTimeout + hidden check)
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} selector - Element selector
 * @param {number} timeout - Timeout in milliseconds (default: 5000)
 * @returns {Promise<void>}
 */
async function waitForHidden(page, selector, timeout = 5000) {
  await page.waitForSelector(selector, { state: 'hidden', timeout });
}

/**
 * Wait for network to be idle (replaces waitForTimeout after actions)
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {number} timeout - Timeout in milliseconds (default: 5000)
 * @returns {Promise<void>}
 */
async function waitForNetworkIdle(page, timeout = 5000) {
  await page.waitForLoadState('networkidle', { timeout });
}

/**
 * Wait for DOM to be ready (faster than networkidle)
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function waitForDOMReady(page) {
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Wait for debounced input (replaces waitForTimeout after typing)
 * Uses requestAnimationFrame to wait for next frame
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function waitForDebounce(page) {
  await page.evaluate(() => new Promise(resolve => requestAnimationFrame(resolve)));
}

/**
 * Wait for form submission to complete
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} formSelector - Form selector (optional)
 * @returns {Promise<void>}
 */
async function waitForFormSubmit(page, formSelector = 'form') {
  // Wait for form to disappear or page to navigate
  try {
    await page.waitForSelector(formSelector, { state: 'hidden', timeout: 3000 });
  } catch {
    // Form might still be visible, wait for URL change instead
    await page.waitForLoadState('domcontentloaded');
  }
}

/**
 * Fast wait for table data (optimized version)
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} tableSelector - Table selector
 * @param {number} minRows - Minimum rows expected
 * @returns {Promise<void>}
 */
async function fastWaitForTableData(page, tableSelector, minRows = 1) {
  await page.waitForSelector(tableSelector, { state: 'visible', timeout: 5000 });
  
  // Use waitForFunction for faster detection
  await page.waitForFunction(
    ({ selector, min }) => {
      const table = document.querySelector(selector);
      if (!table) return false;
      const rows = table.querySelectorAll('tbody tr');
      return rows.length >= min;
    },
    { selector: tableSelector, min: minRows },
    { timeout: 8000 }
  );
}

export {
  waitForVisible,
  waitForHidden,
  waitForNetworkIdle,
  waitForDOMReady,
  waitForDebounce,
  waitForFormSubmit,
  fastWaitForTableData,
};

