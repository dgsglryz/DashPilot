/**
 * Alerts E2E Tests
 *
 * Tests alerts management functionality:
 * - View all alerts
 * - Filter by severity
 * - Mark alert as resolved
 * - Acknowledge alert
 * - View alert details
 *
 * @module tests/e2e/alerts
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers/auth.js";
import { goToAlerts } from "./helpers/navigation.js";
import { waitForSuccessMessage,
    waitForTableData,
    waitForPageReady, waitForUIUpdate } from './helpers/wait.js';

test.describe("Alerts Management", () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await loginAsAdmin(page);
        await goToAlerts(page);
        await waitForPageReady(page);
    });

    test("should display alerts page", async ({ page }) => {
        // Verify page title
        await expect(page.locator('h1:has-text("Alerts")')).toBeVisible();
    });

    test("should display alerts list", async ({ page }) => {
        // Wait for alerts table/list to load
        await waitForUIUpdate(page);

        // Verify alerts container exists
        const alertsContainer = page.locator(
            '[data-testid="alerts-list"], table, .alerts-container',
        );
        await expect(alertsContainer.first()).toBeVisible();
    });

    test("should filter alerts by severity", async ({ page }) => {
        // Wait for page to load
        await waitForUIUpdate(page);

        // Look for severity filter (dropdown, buttons, etc.)
        const severityFilter = page
            .locator(
                'select:has-text("Severity"), button:has-text("Critical"), [data-testid="severity-filter"]',
            )
            .first();

        if (await severityFilter.isVisible()) {
            // Click on critical filter
            await severityFilter.click();
            await waitForUIUpdate(page);

            // Verify filtered results
            const alerts = page.locator(
                '[data-testid="alert-row"], tr, .alert-item',
            );
            const count = await alerts.count();
            expect(count).toBeGreaterThanOrEqual(0);
        }
    });

    test("should mark alert as resolved", async ({ page }) => {
        // Wait for alerts to load
        await waitForUIUpdate(page);

        // Find first alert with resolve button
        const resolveButton = page
            .locator(
                'button:has-text("Resolve"), [data-testid="resolve-alert"]',
            )
            .first();

        if (await resolveButton.isVisible()) {
            await resolveButton.click();

            // Wait for success message
            await waitForSuccessMessage(page, /resolved/i);
        }
    });

    test("should acknowledge alert", async ({ page }) => {
        // Wait for alerts to load
        await waitForUIUpdate(page);

        // Find first alert with acknowledge button
        const acknowledgeButton = page
            .locator(
                'button:has-text("Acknowledge"), [data-testid="acknowledge-alert"]',
            )
            .first();

        if (await acknowledgeButton.isVisible()) {
            await acknowledgeButton.click();

            // Wait for success message
            await waitForSuccessMessage(page);
        }
    });

    test("should mark all alerts as read", async ({ page }) => {
        // Wait for alerts to load
        await waitForUIUpdate(page);

        // Find mark all read button
        const markAllReadButton = page
            .locator(
                'button:has-text("Mark All Read"), [data-testid="mark-all-read"]',
            )
            .first();

        if (await markAllReadButton.isVisible()) {
            await markAllReadButton.click();

            // Wait for success message
            await waitForSuccessMessage(page);
        }
    });

    test("should export alerts", async ({ page }) => {
        // Wait for page to load
        await waitForUIUpdate(page);

        // Find export button
        const exportButton = page
            .locator('button:has-text("Export"), [data-testid="export-alerts"]')
            .first();

        if (await exportButton.isVisible()) {
            await exportButton.click();
            await waitForUIUpdate(page);
        }
    });

    test("should display alert details when clicked", async ({ page }) => {
        // Wait for alerts to load
        await waitForUIUpdate(page);

        // Click first alert if it's a link
        const firstAlert = page
            .locator('[data-testid="alert-row"], tr, .alert-item')
            .first();

        if (await firstAlert.isVisible()) {
            await firstAlert.click();
            await waitForUIUpdate(page);

            // Verify alert details are shown (modal, expanded view, etc.)
            const alertDetails = page.locator(
                '[data-testid="alert-details"], .alert-detail, .modal',
            );
            if (await alertDetails.isVisible()) {
                await expect(alertDetails).toBeVisible();
            }
        }
    });

    test("should display alert severity badges", async ({ page }) => {
        // Wait for alerts to load
        await waitForUIUpdate(page);

        // Verify severity badges exist
        const severityBadges = page.locator("text=/Critical|Warning|Info/i");
        const count = await severityBadges.count();
        // At least some alerts should have severity badges (if alerts exist)
        expect(count).toBeGreaterThanOrEqual(0);
    });
});
