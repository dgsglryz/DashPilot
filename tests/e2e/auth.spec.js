/**
 * Authentication E2E Tests
 *
 * Tests user authentication flows:
 * - Login success
 * - Login failure (invalid credentials)
 * - Logout
 * - Session persistence
 *
 * @module tests/e2e/auth
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin, logout, isLoggedIn } from "./helpers/auth.js";

test.describe("Authentication", () => {
    test.beforeEach(async ({ page }) => {
        // Start from login page for each test
        await page.goto("/login");
    });

    test("should display login page", async ({ page }) => {
        await page.waitForLoadState("networkidle");

        // Verify login form elements - try multiple selectors for Vue/Inertia compatibility
        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await expect(page.locator(emailSelector)).toBeVisible({
            timeout: 15000,
        });
        await expect(page.locator(passwordSelector)).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test("should login successfully with valid credentials", async ({
        page,
    }) => {
        // Login as admin
        await loginAsAdmin(page);

        // Verify redirect to dashboard
        await expect(page).toHaveURL(/\/dashboard/);

        // Verify user is logged in (check for sidebar/logout button)
        const loggedIn = await isLoggedIn(page);
        expect(loggedIn).toBe(true);

        // Verify dashboard content is visible
        await expect(page.locator('h1:has-text("Overview")')).toBeVisible();
    });

    test("should show error message with invalid credentials", async ({
        page,
    }) => {
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });

        // Fill login form with invalid credentials
        await page.fill(emailSelector, "invalid@test.com");
        await page.fill(passwordSelector, "wrongpassword");

        // Submit form
        await page.click('button[type="submit"]');

        // Wait for error message (Laravel validation error)
        await page.waitForSelector(
            "text=/These credentials do not match|Invalid credentials|The provided credentials are incorrect/i",
            { timeout: 10000 },
        );

        // Verify still on login page
        await expect(page).toHaveURL(/\/login/);
    });

    test("should logout successfully", async ({ page }) => {
        // Login first
        await loginAsAdmin(page);

        // Verify logged in
        const loggedInBefore = await isLoggedIn(page);
        expect(loggedInBefore).toBe(true);

        // Logout
        await logout(page);

        // Verify redirect to home/login
        await expect(page).toHaveURL(/\/(login|$)/);

        // Verify logged out (no sidebar/logout button)
        const loggedInAfter = await isLoggedIn(page);
        expect(loggedInAfter).toBe(false);
    });

    test("should redirect to dashboard after login if intended URL is set", async ({
        page,
    }) => {
        // Try to access protected route first
        await page.goto("/sites");

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);

        // Login
        await loginAsAdmin(page);

        // Should redirect to intended URL (sites) or dashboard
        await expect(page).toHaveURL(/\/(sites|dashboard)/);
    });

    test("should persist session across page reloads", async ({
        page,
        context,
    }) => {
        // Login
        await loginAsAdmin(page);

        // Verify logged in
        const loggedInBefore = await isLoggedIn(page);
        expect(loggedInBefore).toBe(true);

        // Reload page
        await page.reload();
        await page.waitForLoadState("networkidle");

        // Verify still logged in
        const loggedInAfter = await isLoggedIn(page);
        expect(loggedInAfter).toBe(true);
    });
});
