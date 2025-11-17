/**
 * Authentication Helper Functions
 *
 * Provides reusable functions for login/logout operations in E2E tests.
 *
 * @module helpers/auth
 */

import {
    getEmailInputSelector,
    getPasswordInputSelector,
    getSubmitButtonInForm,
    waitForFormReady,
} from "./selectors.js";

/**
 * Login as admin user
 *
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {Object} credentials - Login credentials
 * @param {string} credentials.email - User email (default: admin@test.com)
 * @param {string} credentials.password - User password (default: password)
 * @returns {Promise<void>}
 */
async function loginAsAdmin(
    page,
    { email = "admin@test.com", password = "password" } = {},
) {
    // Retry mechanism for login (max 3 attempts)
    let attempts = 0;
    const maxAttempts = 3;
    
    while (attempts < maxAttempts) {
        try {
            // Try to navigate to dashboard first - if storage state exists, we'll be redirected there
            await page.goto("/dashboard", { waitUntil: "domcontentloaded", timeout: 20000 });

            // Check if we're already authenticated (have sidebar/navigation)
            try {
                await page.waitForSelector('aside, nav[role="navigation"]', {
                    state: 'visible',
                    timeout: 3000,
                });
                // Already logged in via storage state, return early
                return;
            } catch {
                // Not authenticated, need to login - redirect to login page
                await page.goto("/login", { waitUntil: "domcontentloaded", timeout: 20000 });
            }
            break; // Success, exit retry loop
        } catch (error) {
            attempts++;
            if (attempts >= maxAttempts) {
                throw new Error(`Login navigation failed after ${maxAttempts} attempts: ${error.message}`);
            }
            // Wait a bit before retry (exponential backoff)
            await new Promise(resolve => setTimeout(resolve, 1000 * attempts));
        }
    }

    // Wait for Vue/Inertia to hydrate - check if input with id="email" exists
    // This ensures Vue components are rendered before trying to find them
    // Retry with multiple selectors
    try {
        await page.waitForSelector('input#email', {
            state: "visible",
            timeout: 10000,
        });
    } catch {
        // Fallback: try alternative selectors
        await page.waitForSelector('input[type="email"], input[name="email"]', {
            state: "visible",
            timeout: 10000,
        });
    }

    // Get selectors using helper functions
    const emailSelector = getEmailInputSelector();
    const passwordSelector = getPasswordInputSelector();

    // Wait for form inputs to be visible (reduced timeout)
    await page.waitForSelector(emailSelector, {
        state: "visible",
        timeout: 5000,
    });
    await page.waitForSelector(passwordSelector, {
        state: "visible",
        timeout: 5000,
    });

    // Fill login form
    await page.fill(emailSelector, email);
    await page.fill(passwordSelector, password);

    // Wait for form to be ready (check if button is enabled)
    await page.waitForSelector('button[type="submit"]:not([disabled])', {
        timeout: 5000,
    }).catch(() => {
        // Button might not have disabled state, continue
    });

    // Try multiple ways to find and click submit button
    const submitSelectors = [
        'button[type="submit"]',
        'form button[type="submit"]',
        'button:has-text("Log in")',
        'button:has-text("Login")',
        '[type="submit"]',
    ];

    let submitted = false;
    for (const selector of submitSelectors) {
        try {
            const button = page.locator(selector).first();
            if (await button.isVisible({ timeout: 2000 })) {
                await button.click();
                submitted = true;
                break;
            }
        } catch {
            // Continue to next selector
        }
    }

    if (!submitted) {
        // Fallback: try helper function
        try {
            const submitButton = await getSubmitButtonInForm(page);
            await submitButton.waitFor({ state: "visible", timeout: 5000 });
            await submitButton.click();
        } catch (error) {
            throw new Error(`Could not find submit button: ${error.message}`);
        }
    }

    // Wait for redirect - could be dashboard or intended URL
    // Use more flexible approach: wait for URL change OR sidebar to appear
    try {
        await page.waitForURL(/\/(dashboard|sites|alerts|clients|tasks)/, {
            timeout: 15000,
        });
    } catch {
        // If URL doesn't match, check if we're logged in by looking for sidebar
        // This handles cases where redirect goes to a different URL
        try {
            await page.waitForSelector('aside, nav[role="navigation"]', {
                timeout: 10000,
                state: 'visible',
            });
        } catch {
            // Final check: make sure we're not on login page
            const url = page.url();
            if (url.includes("/login")) {
                throw new Error(
                    "Login failed - still on login page. Current URL: " + url,
                );
            }
            // If we're not on login page, assume login succeeded
        }
    }

    // Wait for DOM to be ready (faster than networkidle)
    await page.waitForLoadState("domcontentloaded");

    // Verify we're logged in (check for sidebar/navigation - fastest check)
    try {
        // Quick check: sidebar/navigation exists (indicates logged in)
        await page.waitForSelector('aside, nav[role="navigation"]', {
            timeout: 5000,
            state: 'visible',
        });
    } catch (error) {
        // Fallback: check if we're not on login page
        const url = page.url();
        if (url.includes("/login")) {
            throw new Error(
                "Login failed - still on login page. Current URL: " + url,
            );
        }
        // If not on login page, assume login succeeded (might be on a different page)
    }
}

/**
 * Logout current user
 *
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function logout(page) {
    // Wait for DOM to be ready (faster than networkidle)
    await page.waitForLoadState("domcontentloaded");

    // Try multiple logout button selectors
    const logoutSelectors = [
        'a[href="/logout"]',
        'button:has-text("Logout")',
        'button:has-text("Log out")',
        '[data-testid="logout-button"]',
        'form[action*="logout"] button[type="submit"]',
    ];

    let loggedOut = false;
    for (const selector of logoutSelectors) {
        try {
            const logoutButton = page.locator(selector);
            if (await logoutButton.isVisible({ timeout: 2000 })) {
                await logoutButton.click();
                loggedOut = true;
                break;
            }
        } catch {
            // Continue to next selector
        }
    }

    if (!loggedOut) {
        // If no logout button found, try POST to /logout with CSRF token
        try {
            // Get CSRF token from meta tag or cookie
            const csrfToken = await page.evaluate(() => {
                // Try meta tag first
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) return meta.content;

                // Try to get from cookies
                const cookies = document.cookie.split(";");
                for (const cookie of cookies) {
                    const [name, value] = cookie.trim().split("=");
                    if (name === "XSRF-TOKEN" || name === "_token") {
                        return decodeURIComponent(value);
                    }
                }
                return null;
            });

            // Create and submit form with CSRF token
            await page.evaluate((token) => {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "/logout";

                if (token) {
                    const csrfInput = document.createElement("input");
                    csrfInput.type = "hidden";
                    csrfInput.name = "_token";
                    csrfInput.value = token;
                    form.appendChild(csrfInput);
                }

                document.body.appendChild(form);
                form.submit();
            }, csrfToken);

            loggedOut = true;
        } catch (error) {
            console.warn(
                "Could not logout via form submission:",
                error.message,
            );
        }
    }

    // Wait for redirect - logout redirects to home or login (reduced timeout)
    await page.waitForURL(/\/(login|$)/, { timeout: 10000 });

    // If on home page, check if redirected to login
    if (page.url().match(/\/$/)) {
        // Wait for potential redirect to login (some auth middleware might redirect)
        await page.waitForURL(/\/(login|$)/, { timeout: 3000 }).catch(() => {
            // If no redirect, navigate to login to check auth status
            return page.goto("/login", { waitUntil: "domcontentloaded" });
        });
    }
}

/**
 * Check if user is logged in
 *
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<boolean>}
 */
async function isLoggedIn(page) {
    try {
        // Wait a bit for page to load
        await page.waitForLoadState("domcontentloaded");

        // Check for authenticated user indicator (sidebar, user menu, etc.)
        const hasSidebar = await page
            .locator('aside, nav[role="navigation"]')
            .isVisible()
            .catch(() => false);
        const hasLogoutButton = await page
            .locator(
                'a[href="/logout"], button:has-text("Logout"), button:has-text("Log out")',
            )
            .isVisible()
            .catch(() => false);
        const isOnLoginPage = page.url().includes("/login");

        return (hasSidebar || hasLogoutButton) && !isOnLoginPage;
    } catch {
        return false;
    }
}

export { loginAsAdmin, logout, isLoggedIn };
