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

    // Wait for form to be ready using helper
    await waitForFormReady(page, "form");

    // Get selectors using helper functions
    const emailSelector = getEmailInputSelector();
    const passwordSelector = getPasswordInputSelector();

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

    // Get submit button using helper
    const submitButton = await getSubmitButtonInForm(page);
    await submitButton.waitFor({ state: "visible", timeout: 10000 });
    await submitButton.click();

    // Wait for redirect to dashboard
    await page.waitForURL("**/dashboard", { timeout: 15000 });

    // Wait for Vue to hydrate on dashboard
    await page.waitForLoadState("networkidle", { timeout: 30000 });
    await page.waitForTimeout(500);

    // Verify we're logged in (check for dashboard content)
    try {
        await page.waitForSelector(
            '[data-testid="dashboard-stats"], h1:has-text("Overview"), [data-testid="dashboard"]',
            { timeout: 10000 },
        );
    } catch {
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
        // If no logout button found, try POST to /logout
        await page.evaluate(() => {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "/logout";
            document.body.appendChild(form);
            form.submit();
        });
    }

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
