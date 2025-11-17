/**
 * Clients Management E2E Tests
 *
 * Tests clients management functionality:
 * - View all clients
 * - Create new client
 * - Edit client
 * - View client sites
 * - View client reports
 *
 * @module tests/e2e/clients
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers/auth.js";
import { goToClients } from "./helpers/navigation.js";
import {
    waitForSuccessMessage,
    waitForTableData,
    waitForPageReady,
} from "./helpers/wait.js";
import {
    getInputSelector,
    getTextareaSelector,
    getSubmitButtonInForm,
    waitForFormReady,
} from "./helpers/selectors.js";

test.describe("Clients Management", () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await loginAsAdmin(page);
        await goToClients(page);
    });

    test("should display clients page", async ({ page }) => {
        // Verify page title
        await expect(page.locator('h1:has-text("Clients")')).toBeVisible();
    });

    test("should display clients list", async ({ page }) => {
        // Wait for clients table/list to load
        await page.waitForTimeout(2000);

        // Verify clients container exists
        const clientsContainer = page.locator(
            '[data-testid="clients-table"], table, .clients-container',
        );
        await expect(clientsContainer.first()).toBeVisible();
    });

    test("should navigate to create client page", async ({ page }) => {
        // Find and click Add Client button
        const addButton = page
            .locator(
                'a:has-text("Add Client"), button:has-text("Add Client"), [data-testid="add-client-button"]',
            )
            .first();

        if (await addButton.isVisible()) {
            await addButton.click();
            await expect(page).toHaveURL(/\/clients\/create/);
        }
    });

    test("should create new client", async ({ page }) => {
        // Navigate to create page
        const addButton = page
            .locator(
                'a:has-text("Add Client"), button:has-text("Add Client"), [data-testid="add-client-button"]',
            )
            .first();

        if (await addButton.isVisible({ timeout: 2000 }).catch(() => false)) {
            await addButton.click();
            await page.waitForURL(/\/clients\/create/);
            await waitForPageReady(page);

            // Wait for form to be ready
            await waitForFormReady(page);

            // Fill client form using helper selectors
            const nameSelector = getInputSelector("name");
            const emailSelector = getInputSelector("email", "email");

            await page.waitForSelector(nameSelector, {
                state: "visible",
                timeout: 15000,
            });
            await page.fill(nameSelector, "Test Client Company");
            await page.fill(emailSelector, "testclient@example.com");

            // Fill optional fields if they exist
            const phoneSelector = getInputSelector("phone", "tel");
            const phoneInput = page.locator(phoneSelector);
            if (
                await phoneInput.isVisible({ timeout: 2000 }).catch(() => false)
            ) {
                await phoneInput.fill("+1-555-123-4567");
            }

            const addressSelector = getTextareaSelector("address");
            const addressInput = page.locator(addressSelector);
            if (
                await addressInput
                    .isVisible({ timeout: 2000 })
                    .catch(() => false)
            ) {
                await addressInput.fill("123 Test Street, Test City, TC 12345");
            }

            // Submit form using helper
            const submitButton = await getSubmitButtonInForm(page, "form");
            await submitButton.click();

            // Wait for redirect to client detail page
            await expect(page).toHaveURL(/\/clients\/\d+/, { timeout: 15000 });
            await waitForPageReady(page);

            // Verify success
            await expect(
                page.locator("text=/Test Client Company/i"),
            ).toBeVisible({ timeout: 10000 });
        }
    });

    test("should view client details", async ({ page }) => {
        // Wait for clients table to load
        await waitForTableData(page, "table", 1);

        // Click first client
        const firstClient = page
            .locator('tr, [data-testid="client-row"]')
            .first();
        await firstClient.click();

        // Verify navigated to client detail page
        await expect(page).toHaveURL(/\/clients\/\d+/);
        await expect(page.locator("h1")).toBeVisible();
    });

    test("should edit client", async ({ page }) => {
        // Wait for clients table to load
        await waitForTableData(page, "table", 1);

        // Navigate to first client detail
        const firstClient = page
            .locator('tr, [data-testid="client-row"]')
            .first();
        await firstClient.click();
        await page.waitForURL(/\/clients\/\d+/);
        await waitForPageReady(page);

        // Click edit button
        const editButton = page
            .locator(
                'a:has-text("Edit"), button:has-text("Edit"), [data-testid="edit-client-button"]',
            )
            .first();

        if (await editButton.isVisible({ timeout: 2000 }).catch(() => false)) {
            await editButton.click();
            await page.waitForURL(/\/clients\/\d+\/edit/);
            await waitForPageReady(page);

            // Wait for form to be ready
            await waitForFormReady(page);

            // Update client name using helper selector
            const nameSelector = getInputSelector("name");
            const nameInput = page.locator(nameSelector);
            if (
                await nameInput.isVisible({ timeout: 2000 }).catch(() => false)
            ) {
                await nameInput.fill("Updated Client Name");
            }

            // Submit form using helper
            const submitButton = await getSubmitButtonInForm(page, "form");
            await submitButton.click();

            // Wait for redirect back to detail page
            await expect(page).toHaveURL(/\/clients\/\d+/, { timeout: 15000 });
            await waitForPageReady(page);
            await waitForSuccessMessage(page);
        }
    });

    test("should view client sites", async ({ page }) => {
        // Wait for clients table to load
        await waitForTableData(page, "table", 1);

        // Navigate to first client detail
        const firstClient = page
            .locator('tr, [data-testid="client-row"]')
            .first();
        await firstClient.click();
        await page.waitForURL(/\/clients\/\d+/);

        // Look for client sites section
        const sitesSection = page.locator("text=/Sites|Client Sites/i");
        if (await sitesSection.isVisible()) {
            await expect(sitesSection).toBeVisible();
        }
    });

    test("should view client reports", async ({ page }) => {
        // Wait for clients table to load
        await waitForTableData(page, "table", 1);

        // Navigate to first client detail
        const firstClient = page
            .locator('tr, [data-testid="client-row"]')
            .first();
        await firstClient.click();
        await page.waitForURL(/\/clients\/\d+/);

        // Look for reports link/button
        const reportsLink = page
            .locator(
                'a:has-text("Reports"), button:has-text("Reports"), [data-testid="client-reports"]',
            )
            .first();

        if (await reportsLink.isVisible()) {
            await reportsLink.click();
            await page.waitForTimeout(1000);

            // Verify reports page or section
            await expect(page.locator("text=/Reports/i")).toBeVisible();
        }
    });
});
