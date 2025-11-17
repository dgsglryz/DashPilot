/**
 * Selector Helper Functions
 * 
 * Provides reusable selector functions for common Vue/Inertia components.
 * These selectors are designed to work with Vue components that may use id, name, or type attributes.
 * 
 * @module helpers/selectors
 */

/**
 * Get selector for email input (tries multiple patterns)
 * 
 * @returns {string} CSS selector string
 */
export function getEmailInputSelector() {
  return 'input#email, input[name="email"], input[type="email"]';
}

/**
 * Get selector for password input (tries multiple patterns)
 * 
 * @returns {string} CSS selector string
 */
export function getPasswordInputSelector() {
  return 'input#password, input[name="password"], input[type="password"]';
}

/**
 * Get selector for input by name/id/type (flexible selector)
 * 
 * @param {string} fieldName - Field name (e.g., 'name', 'url', 'title')
 * @param {string} type - Input type (optional, e.g., 'text', 'email', 'password')
 * @returns {string} CSS selector string
 */
export function getInputSelector(fieldName, type = null) {
  const selectors = [`input#${fieldName}`, `input[name="${fieldName}"]`];
  
  if (type) {
    selectors.push(`input[type="${type}"]`);
  }
  
  // Also try with underscore and dash variants for field names
  selectors.push(`input[name="${fieldName.replace(/_/g, '-')}"]`);
  selectors.push(`input[name="${fieldName.replace(/-/g, '_')}"]`);
  
  return selectors.join(', ');
}

/**
 * Get selector for textarea by name/id
 * 
 * @param {string} fieldName - Field name
 * @returns {string} CSS selector string
 */
export function getTextareaSelector(fieldName) {
  return `textarea#${fieldName}, textarea[name="${fieldName}"]`;
}

/**
 * Get selector for submit button in a specific form
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} formSelector - Form selector (optional, defaults to first form with email input)
 * @returns {Promise<import('@playwright/test').Locator>} Submit button locator
 */
export async function getSubmitButtonInForm(page, formSelector = null) {
  if (formSelector) {
    return page.locator(formSelector).locator('button[type="submit"]').first();
  }
  
  // Default: find form containing email input (for login forms)
  const emailSelector = getEmailInputSelector();
  const form = page.locator('form').filter({ has: page.locator(emailSelector) }).first();
  
  // If that doesn't work, try finding any form
  const formExists = await form.isVisible({ timeout: 1000 }).catch(() => false);
  if (!formExists) {
    // Fallback: find submit button in any form, but prefer forms with inputs
    const allForms = page.locator('form');
    const formCount = await allForms.count();
    
    if (formCount > 0) {
      // Try each form and return the first one with a submit button
      for (let i = 0; i < formCount; i++) {
        const formLocator = allForms.nth(i);
        const hasSubmit = await formLocator.locator('button[type="submit"]').count() > 0;
        if (hasSubmit) {
          return formLocator.locator('button[type="submit"]').first();
        }
      }
    }
    
    // Last resort: any submit button
    return page.locator('button[type="submit"]').first();
  }
  
  return form.locator('button[type="submit"]').first();
}

/**
 * Wait for form to be ready and visible
 * 
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} formSelector - Form selector (optional)
 * @param {number} timeout - Timeout in milliseconds (default: 15000)
 * @returns {Promise<void>}
 */
export async function waitForFormReady(page, formSelector = 'form', timeout = 15000) {
  await page.waitForLoadState('networkidle', { timeout });
  await page.waitForSelector(formSelector, { state: 'visible', timeout });
  // Additional wait for Vue components to render
  await page.waitForTimeout(500);
}

