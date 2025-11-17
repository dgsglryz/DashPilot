/**
 * Shared Authentication Context
 * 
 * Provides reusable authentication context for E2E tests to avoid
 * repeated logins. Saves significant time by reusing sessions.
 * 
 * @module helpers/auth-context
 */

import { chromium } from '@playwright/test';
import { loginAsAdmin } from './auth.js';

/**
 * Create a new browser context with authenticated session
 * 
 * This function creates a browser context, logs in once, and saves
 * the storage state. This state can be reused across multiple tests
 * to avoid repeated logins.
 * 
 * @param {Object} options - Options for context creation
 * @param {string} options.email - User email (default: admin@test.com)
 * @param {string} options.password - User password (default: password)
 * @param {string} options.baseURL - Base URL (default: http://localhost:8000)
 * @returns {Promise<{ browser: Browser, context: BrowserContext, page: Page, storageState: string }>}
 */
export async function createAuthenticatedContext({ 
    email = 'admin@test.com', 
    password = 'password',
    baseURL = 'http://localhost:8000'
} = {}) {
    const browser = await chromium.launch();
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();

    // Login once
    await loginAsAdmin(page, { email, password });

    // Save storage state (includes cookies, localStorage, sessionStorage)
    const storageState = await context.storageState();

    // Close temporary context (we'll create new ones with this state)
    await context.close();
    await browser.close();

    return { storageState };
}

/**
 * Get or create authenticated storage state
 * 
 * This function caches the storage state to avoid recreating it
 * for every test run. Uses in-memory cache for the test run duration.
 * 
 * @param {Object} options - Options for storage state creation
 * @returns {Promise<string>} - Storage state path or object
 */
let cachedStorageState = null;

export async function getAuthenticatedStorageState(options = {}) {
    if (cachedStorageState) {
        return cachedStorageState;
    }

    const { storageState } = await createAuthenticatedContext(options);
    cachedStorageState = storageState;
    
    return storageState;
}

/**
 * Clear cached storage state
 * 
 * Useful when you need to force a fresh login.
 */
export function clearAuthenticatedStorageState() {
    cachedStorageState = null;
}

