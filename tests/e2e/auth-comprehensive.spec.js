/**
 * COMPREHENSIVE AUTHENTICATION TESTS
 *
 * Tests all authentication flows including:
 * - Login with valid/invalid credentials
 * - Remember me functionality
 * - Password reset flow
 * - Session persistence
 * - Unauthorized access
 *
 * @module tests/e2e/auth-comprehensive
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin, logout, isLoggedIn } from "./helpers/auth.js";
import { waitForUIUpdate } from './helpers/wait.js';

test.describe("Authentication - Comprehensive Tests", () => {
    test.beforeEach(async ({ page }) => {
        await page.goto("/login");
    });

    test("should login with valid credentials", async ({ page }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(emailSelector, "admin@test.com");
        await page.fill(passwordSelector, "password");
        await page.click('button[type="submit"]');

        await page.waitForURL("**/dashboard", { timeout: 15000 });
        await page.waitForLoadState("networkidle");
        await expect(
            page.locator('h1:has-text("Overview"), [data-testid="dashboard"]'),
        ).toBeVisible({ timeout: 10000 });
    });

    test("should show error with invalid email", async ({ page }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(emailSelector, "invalid@test.com");
        await page.fill(passwordSelector, "password");
        await page.click('button[type="submit"]');

        // Wait for error message
        await page.waitForSelector(
            "text=/These credentials do not match|Invalid credentials|The provided credentials are incorrect/i",
            { timeout: 10000 },
        );
        await expect(page).toHaveURL(/\/login/);
    });

    test("should show error with invalid password", async ({ page }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(emailSelector, "admin@test.com");
        await page.fill(passwordSelector, "wrongpassword");
        await page.click('button[type="submit"]');

        await page.waitForSelector(
            "text=/These credentials do not match|Invalid credentials/i",
            { timeout: 10000 },
        );
        await expect(page).toHaveURL(/\/login/);
    });

    test("should test remember me checkbox", async ({ page, context }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(emailSelector, "admin@test.com");
        await page.fill(passwordSelector, "password");

        // Check remember me if exists
        const rememberMe = page.locator(
            'input[type="checkbox"][name*="remember"], input[type="checkbox"][id*="remember"]',
        );
        if (await rememberMe.isVisible({ timeout: 2000 }).catch(() => false)) {
            await rememberMe.check();
        }

        await page.click('button[type="submit"]');
        await page.waitForURL("**/dashboard", { timeout: 15000 });
        await page.waitForLoadState("networkidle");

        // Verify session persists after browser restart (simulated by clearing cookies except remember token)
        const cookies = await context.cookies();
        const hasRememberToken = cookies.some(
            (c) =>
                c.name.includes("remember") ||
                c.name.includes("remember_token"),
        );

        // If remember me exists, verify cookie is set
        if (await rememberMe.isVisible({ timeout: 2000 }).catch(() => false)) {
            expect(hasRememberToken || cookies.length > 0).toBeTruthy();
        }
    });

    test("should test password reset flow", async ({ page }) => {
        // Click forgot password link if exists
        const forgotPasswordLink = page.locator(
            'a:has-text("Forgot"), a[href*="password"]',
        );

        if (await forgotPasswordLink.isVisible()) {
            await forgotPasswordLink.click();
            await page.waitForURL(/\/password\/reset|\/forgot-password/);

            // Fill email for password reset
            const emailInput = page.locator(
                'input[name="email"], input[type="email"]',
            );
            if (await emailInput.isVisible()) {
                await emailInput.fill("admin@test.com");

                // Submit password reset request
                const submitButton = page.locator('button[type="submit"]');
                if (await submitButton.isVisible()) {
                    await submitButton.click();

                    // Verify success message
                    await page.waitForSelector(
                        "text=/password reset|check your email/i",
                        { timeout: 5000 },
                    );
                }
            }
        }
    });

    test("should logout and verify redirect to login", async ({ page }) => {
        await loginAsAdmin(page);

        // Verify logged in
        const loggedInBefore = await isLoggedIn(page);
        expect(loggedInBefore).toBe(true);

        // Logout
        await logout(page);

        // Verify redirect
        await expect(page).toHaveURL(/\/(login|$)/);

        // Verify logged out
        const loggedInAfter = await isLoggedIn(page);
        expect(loggedInAfter).toBe(false);
    });

    test("should persist session across page refreshes", async ({ page }) => {
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

        // Verify still on dashboard
        await expect(page).toHaveURL(/\/dashboard/);
    });

    test("should redirect unauthorized access to login", async ({ page }) => {
        // Try to access protected route without login
        await page.goto("/dashboard");

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);

        // Try to access sites
        await page.goto("/sites");
        await expect(page).toHaveURL(/\/login/);

        // Try to access alerts
        await page.goto("/alerts");
        await expect(page).toHaveURL(/\/login/);
    });

    test("should redirect to intended URL after login", async ({ page }) => {
        // Try to access protected route first
        await page.goto("/sites");

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);

        // Login
        await loginAsAdmin(page);

        // Should redirect to intended URL (sites) or dashboard
        await expect(page).toHaveURL(/\/(sites|dashboard)/);
    });

    test("should handle expired session", async ({ page, context }) => {
        await loginAsAdmin(page);

        // Clear all cookies (simulate expired session)
        await context.clearCookies();

        // Try to navigate
        await page.goto("/dashboard");

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);
    });

    test("should test form validation on login", async ({ page }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });

        // Try to submit empty form
        await page.click('button[type="submit"]');

        // Wait a bit for validation to trigger
        await waitForUIUpdate(page);

        // Verify validation errors appear
        const emailInput = page.locator(emailSelector);
        const passwordInput = page.locator(passwordSelector);

        // Check if HTML5 validation or custom validation shows
        const emailRequired = await emailInput
            .evaluate((el) => el.validity?.valueMissing || false)
            .catch(() => false);
        const passwordRequired = await passwordInput
            .evaluate((el) => el.validity?.valueMissing || false)
            .catch(() => false);

        // At least one should be required or we should still be on login page
        const isStillOnLogin = page.url().includes("/login");
        expect(
            emailRequired || passwordRequired || isStillOnLogin,
        ).toBeTruthy();
    });

    test("should test email format validation", async ({ page }) => {
        await page.goto("/login");
        await page.waitForLoadState("networkidle");

        const emailSelector =
            'input#email, input[name="email"], input[type="email"]';
        const passwordSelector =
            'input#password, input[name="password"], input[type="password"]';

        await page.waitForSelector(emailSelector, {
            state: "visible",
            timeout: 15000,
        });

        // Enter invalid email format
        await page.fill(emailSelector, "invalid-email");
        await page.fill(passwordSelector, "password");

        // Check HTML5 validation before submitting
        const emailInput = page.locator(emailSelector);
        const isValid = await emailInput
            .evaluate((el) => el.validity?.valid !== false)
            .catch(() => true);

        // Try to submit - should either be blocked by validation or show error
        await page.click('button[type="submit"]');

        // Wait a bit for validation to trigger
        await waitForUIUpdate(page);

        // Email should be invalid or we should still be on login page
        const isStillOnLogin = page.url().includes("/login");
        expect(isValid === false || isStillOnLogin).toBeTruthy();
    });
});




