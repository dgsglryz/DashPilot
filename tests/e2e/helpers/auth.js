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
    await page.goto("/login");

    // Wait for page to load
    await page.waitForLoadState("networkidle", { timeout: 30000 });
    await page.waitForTimeout(1000); // Extra wait for Vue hydration

    // Get selectors using helper functions
    const emailSelector = getEmailInputSelector();
    const passwordSelector = getPasswordInputSelector();

    // Wait for form inputs to be visible
    await page.waitForSelector(emailSelector, {
        state: "visible",
        timeout: 15000,
    });
    await page.waitForSelector(passwordSelector, {
        state: "visible",
        timeout: 15000,
    });

    // Fill login form
    await page.fill(emailSelector, email);
    await page.fill(passwordSelector, password);

    // Wait a bit for form to be ready
    await page.waitForTimeout(300);

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
    await page.waitForURL(/\/(dashboard|sites|alerts|clients|tasks)/, {
        timeout: 15000,
    });

    // Wait for Vue to hydrate on dashboard
    await page.waitForLoadState("networkidle", { timeout: 30000 });
    await page.waitForTimeout(1000); // Extra wait for Vue hydration

    // Verify we're logged in (check for dashboard content)
    try {
        // Try multiple selectors for dashboard content
        const dashboardSelectors = [
            '[data-testid="dashboard-stats"]',
            'h1:has-text("Overview")',
            '[data-testid="dashboard"]',
            'h1:contains("Overview")',
            "text=Overview",
        ];

        let found = false;
        for (const selector of dashboardSelectors) {
            try {
                await page.waitForSelector(selector, { timeout: 5000 });
                found = true;
                break;
            } catch {
                // Continue to next selector
            }
        }

        // Also check if sidebar/navigation exists (indicates logged in)
        if (!found) {
            const hasSidebar = await page
                .locator('aside, nav[role="navigation"]')
                .isVisible()
                .catch(() => false);
            if (hasSidebar) {
                found = true;
            }
        }

        if (!found) {
            // Final fallback: check if we're on dashboard URL
            const url = page.url();
            if (!url.includes("/dashboard") && !url.includes("/login")) {
                throw new Error(
                    "Login failed - not redirected to dashboard. Current URL: " +
                        url,
                );
            }
        }
    } catch (error) {
        // Final fallback: check if we're on dashboard URL
        const url = page.url();
        if (!url.includes("/dashboard") && !url.includes("/login")) {
            throw new Error(
                "Login failed - not redirected to dashboard. Current URL: " +
                    url,
            );
        }
    }
}

/**
 * Logout current user
 *
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @returns {Promise<void>}
 */
async function logout(page) {
    // Wait for page to be ready
    await page.waitForLoadState("networkidle");

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

    // Wait for redirect - logout redirects to home (/)
    await page.waitForURL(/\/(login|$)/, { timeout: 15000 });

    // If on home page, wait a bit for potential redirect to login
    if (page.url().match(/\/$/)) {
        await page.waitForTimeout(1000);
        // Check if redirected to login (some auth middleware might redirect)
        const currentUrl = page.url();
        if (!currentUrl.includes("/login")) {
            // If still on home, try navigating to login to check auth status
            await page.goto("/login", { waitUntil: "networkidle" });
        }
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
