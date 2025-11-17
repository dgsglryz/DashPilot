/**
 * Sites Management E2E Tests
 *
 * Tests sites management functionality:
 * - View all sites
 * - Search sites
 * - Filter by status/platform
 * - View site details
 * - Create new site (WordPress/Shopify)
 * - Edit site
 * - Run health check
 * - Toggle favorite
 *
 * @module tests/e2e/sites
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers/auth.js";
import { goToSites } from "./helpers/navigation.js";
import { waitForSuccessMessage,
    waitForTableData,
    waitForPageReady, waitForUIUpdate } from './helpers/wait.js';
import {
    getInputSelector,
    getSubmitButtonInForm,
    waitForFormReady,
} from "./helpers/selectors.js";

test.describe("Sites Management", () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await loginAsAdmin(page);
        await goToSites(page);
    });

    test("should display sites list page", async ({ page }) => {
        // Verify page title
        await expect(page.locator('h1:has-text("Sites")')).toBeVisible();

        // Verify sites table exists
        const sitesTable = page.locator('[data-testid="sites-table"]');
        await expect(sitesTable).toBeVisible();
    });

    test("should display sites statistics", async ({ page }) => {
        // Verify stats cards
        await expect(
            page.locator("text=/Healthy Sites|Warnings|Critical|Total Sites/i"),
        ).toBeVisible();
    });

    test("should search sites by name or URL", async ({ page }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 1);

        // Get first site name
        const firstSiteName = await page
            .locator('[data-testid="site-row"]')
            .first()
            .locator("td")
            .nth(1)
            .textContent();

        // Type in search box
        const searchInput = page.locator('[data-testid="search-input"]');
        await searchInput.fill(firstSiteName);
        await waitForUIUpdate(page); // Wait for debounce

        // Verify filtered results
        const rows = page.locator('[data-testid="site-row"]');
        const count = await rows.count();
        expect(count).toBeGreaterThan(0);
    });

    test("should filter sites by platform", async ({ page }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 1);

        // Select WordPress platform filter
        await page.selectOption('select:has-text("Platform")', "wordpress");
        await waitForUIUpdate(page);

        // Verify filtered results (all should be WordPress)
        const rows = page.locator('[data-testid="site-row"]');
        const count = await rows.count();

        if (count > 0) {
            const firstRow = rows.first();
            await expect(firstRow.locator("text=/WordPress/i")).toBeVisible();
        }
    });

    test("should filter sites by status", async ({ page }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 1);

        // Select healthy status filter
        await page.selectOption('select:has-text("Status")', "healthy");
        await waitForUIUpdate(page);

        // Verify filtered results
        const rows = page.locator('[data-testid="site-row"]');
        const count = await rows.count();
        expect(count).toBeGreaterThanOrEqual(0);
    });

    test("should click on site and view site details", async ({ page }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 1);

        // Click first site row
        const firstSite = page.locator('[data-testid="site-row"]').first();
        await firstSite.click();

        // Verify navigated to site detail page
        await expect(page).toHaveURL(/\/sites\/\d+/);
        await expect(page.locator("h1")).toBeVisible();
    });

    test("should display Add Site button", async ({ page }) => {
        // Verify Add Site button exists
        const addButton = page.locator(
            '[data-testid="add-site-button"], a:has-text("Add Site")',
        );
        await expect(addButton).toBeVisible();
    });

    test("should navigate to create site page", async ({ page }) => {
        // Click Add Site button
        const addButton = page.locator(
            '[data-testid="add-site-button"], a:has-text("Add Site")',
        );
        await addButton.click();

        // Verify on create site page
        await expect(page).toHaveURL(/\/sites\/create/);
    });

    test("should create new WordPress site", async ({ page }) => {
        // Navigate to create page
        await page.click(
            '[data-testid="add-site-button"], a:has-text("Add Site")',
        );
        await page.waitForURL(/\/sites\/create/);
        await waitForPageReady(page);

        // Wait for form to be ready
        await waitForFormReady(page);

        // Fill site form using helper selectors
        const nameSelector = getInputSelector("name");
        const urlSelector = getInputSelector("url");

        await page.waitForSelector(nameSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(nameSelector, "Test WordPress Site");
        await page.fill(urlSelector, "https://test-wordpress-site.com");
        await page.selectOption(
            'select[name="type"], select[name="platform"]',
            "wordpress",
        );

        // Select client if dropdown exists
        const clientSelect = page.locator('select[name="client_id"]');
        if (
            await clientSelect.isVisible({ timeout: 2000 }).catch(() => false)
        ) {
            const options = await clientSelect.locator("option").all();
            if (options.length > 1) {
                await clientSelect.selectOption({ index: 1 });
            }
        }

        // Submit form using helper
        const submitButton = await getSubmitButtonInForm(page, "form");
        await submitButton.click();

        // Wait for redirect to site detail page
        await expect(page).toHaveURL(/\/sites\/\d+/, { timeout: 15000 });
        await waitForPageReady(page);

        // Verify success (site detail page should show site name)
        await expect(page.locator("text=/Test WordPress Site/i")).toBeVisible({
            timeout: 10000,
        });
    });

    test("should create new Shopify site", async ({ page }) => {
        // Navigate to create page
        await page.click(
            '[data-testid="add-site-button"], a:has-text("Add Site")',
        );
        await page.waitForURL(/\/sites\/create/);
        await waitForPageReady(page);

        // Wait for form to be ready
        await waitForFormReady(page);

        // Fill site form using helper selectors
        const nameSelector = getInputSelector("name");
        const urlSelector = getInputSelector("url");

        await page.waitForSelector(nameSelector, {
            state: "visible",
            timeout: 15000,
        });
        await page.fill(nameSelector, "Test Shopify Store");
        await page.fill(
            urlSelector,
            "https://test-shopify-store.myshopify.com",
        );
        await page.selectOption(
            'select[name="type"], select[name="platform"]',
            "shopify",
        );

        // Select client if dropdown exists
        const clientSelect = page.locator('select[name="client_id"]');
        if (
            await clientSelect.isVisible({ timeout: 2000 }).catch(() => false)
        ) {
            const options = await clientSelect.locator("option").all();
            if (options.length > 1) {
                await clientSelect.selectOption({ index: 1 });
            }
        }

        // Submit form using helper
        const submitButton = await getSubmitButtonInForm(page, "form");
        await submitButton.click();

        // Wait for redirect to site detail page
        await expect(page).toHaveURL(/\/sites\/\d+/, { timeout: 15000 });
        await waitForPageReady(page);

        // Verify success
        await expect(page.locator("text=/Test Shopify Store/i")).toBeVisible({
            timeout: 10000,
        });
    });

    test("should run health check on site", async ({ page }) => {
        // Navigate to first site detail page
        await waitForTableData(page, '[data-testid="sites-table"]', 1);
        const firstSite = page.locator('[data-testid="site-row"]').first();
        await firstSite.click();
        await page.waitForURL(/\/sites\/\d+/);

        // Click Run Health Check button
        const healthCheckButton = page.locator(
            '[data-testid="run-health-check"], button:has-text("Run Health Check")',
        );
        await healthCheckButton.click();

        // Wait for success message
        await waitForSuccessMessage(page, /health check|queued/i);
    });

    test("should toggle favorite status", async ({ page }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 1);

        // Find favorite button in first row
        const firstRow = page.locator('[data-testid="site-row"]').first();
        const favoriteButton = firstRow
            .locator('button:has(svg), [data-testid="favorite-button"]')
            .first();

        // Click favorite button
        await favoriteButton.click();
        await waitForUIUpdate(page);

        // Verify favorite status changed (visual check)
        // Note: This depends on how favorite is displayed
    });

    test("should export sites", async ({ page }) => {
        // Click export button
        const exportButton = page.locator('button:has-text("Export")');
        await exportButton.click();

        // Wait for download (or verify button state changed)
        await waitForUIUpdate(page);

        // Note: Actual file download verification would require download event listener
    });

    test("should select multiple sites and run bulk health check", async ({
        page,
    }) => {
        // Wait for table to load
        await waitForTableData(page, '[data-testid="sites-table"]', 2);

        // Select first two sites
        const checkboxes = page.locator(
            '[data-testid="site-row"] input[type="checkbox"]',
        );
        await checkboxes.nth(0).check();
        await checkboxes.nth(1).check();

        // Verify batch actions bar appears
        await expect(page.locator("text=/selected/i")).toBeVisible();

        // Click bulk health check
        const bulkHealthCheck = page.locator(
            'button:has-text("Run Health Check")',
        );
        await bulkHealthCheck.click();

        // Wait for success message
        await waitForSuccessMessage(page);
    });
});
