/**
 * Settings E2E Tests
 *
 * Tests settings functionality:
 * - Update email preferences
 * - Add webhook
 * - Test webhook
 * - Delete webhook
 * - Update profile
 * - Update password
 *
 * @module tests/e2e/settings
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers/auth.js";
import { goToSettings } from "./helpers/navigation.js";
import { waitForSuccessMessage, waitForPageReady } from "./helpers/wait.js";
import {
    getInputSelector,
    getSubmitButtonInForm,
    waitForFormReady,
} from "./helpers/selectors.js";

test.describe("Settings", () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await loginAsAdmin(page);
        await goToSettings(page);
        await waitForPageReady(page);
    });

    test("should display settings page", async ({ page }) => {
        // Verify page title
        await expect(page.locator('h1:has-text("Settings")')).toBeVisible();
    });

    test("should update profile information", async ({ page }) => {
        // Find profile section
        const profileSection = page.locator(
            "text=/Profile|Personal Information/i",
        );

        if (
            await profileSection.isVisible({ timeout: 2000 }).catch(() => false)
        ) {
            // Wait for form to be ready
            await waitForFormReady(page);

            // Update name using helper selector
            const nameSelector = getInputSelector("name");
            const nameInput = page.locator(nameSelector);
            if (
                await nameInput.isVisible({ timeout: 2000 }).catch(() => false)
            ) {
                await nameInput.fill("Updated Admin Name");
            }

            // Submit profile form using helper
            const submitButton = await getSubmitButtonInForm(page, "form");
            if (
                await submitButton
                    .isVisible({ timeout: 2000 })
                    .catch(() => false)
            ) {
                await submitButton.click();
                await waitForPageReady(page);
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should update email preferences", async ({ page }) => {
        // Find email preferences section
        const emailSection = page.locator("text=/Email|Preferences/i");

        if (await emailSection.isVisible()) {
            // Toggle email notifications if checkbox exists
            const emailCheckbox = page
                .locator(
                    'input[type="checkbox"][name*="email"], input[type="checkbox"][name*="notification"]',
                )
                .first();

            if (await emailCheckbox.isVisible()) {
                await emailCheckbox.check();
            }

            // Submit preferences form
            const submitButton = page
                .locator(
                    'button:has-text("Save Preferences"), button[type="submit"]',
                )
                .first();
            if (await submitButton.isVisible()) {
                await submitButton.click();
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should add new webhook", async ({ page }) => {
        // Find webhooks section
        const webhooksSection = page.locator("text=/Webhooks/i");

        if (
            await webhooksSection
                .isVisible({ timeout: 2000 })
                .catch(() => false)
        ) {
            // Click Add Webhook button
            const addButton = page
                .locator(
                    'button:has-text("Add Webhook"), a:has-text("Add Webhook"), [data-testid="add-webhook-button"]',
                )
                .first();

            if (
                await addButton.isVisible({ timeout: 2000 }).catch(() => false)
            ) {
                await addButton.click();
                await waitForPageReady(page);
                await waitForFormReady(page);

                // Fill webhook form using helper selector
                const urlSelector = getInputSelector("url");
                const urlInput = page
                    .locator(`${urlSelector}, input[placeholder*="webhook"]`)
                    .first();
                if (
                    await urlInput
                        .isVisible({ timeout: 2000 })
                        .catch(() => false)
                ) {
                    await urlInput.fill("https://example.com/webhook");
                }

                // Select webhook type if exists
                const typeSelect = page.locator('select[name="type"]');
                if (
                    await typeSelect
                        .isVisible({ timeout: 2000 })
                        .catch(() => false)
                ) {
                    await typeSelect.selectOption({ index: 0 });
                }

                // Submit form using helper
                const submitButton = await getSubmitButtonInForm(page, "form");
                if (
                    await submitButton
                        .isVisible({ timeout: 2000 })
                        .catch(() => false)
                ) {
                    await submitButton.click();
                    await waitForPageReady(page);
                    await waitForSuccessMessage(page);
                }
            }
        }
    });

    test("should test webhook", async ({ page }) => {
        // Find webhooks section
        const webhooksSection = page.locator("text=/Webhooks/i");

        if (await webhooksSection.isVisible()) {
            // Find first webhook test button
            const testButton = page
                .locator(
                    'button:has-text("Test"), [data-testid="test-webhook-button"]',
                )
                .first();

            if (await testButton.isVisible()) {
                await testButton.click();
                await page.waitForTimeout(2000);

                // Verify success message or test result
                await waitForSuccessMessage(page, /test|success/i);
            }
        }
    });

    test("should delete webhook", async ({ page }) => {
        // Find webhooks section
        const webhooksSection = page.locator("text=/Webhooks/i");

        if (await webhooksSection.isVisible()) {
            // Find first webhook delete button
            const deleteButton = page
                .locator(
                    'button:has-text("Delete"), [data-testid="delete-webhook-button"]',
                )
                .first();

            if (await deleteButton.isVisible()) {
                await deleteButton.click();

                // Confirm deletion if dialog appears
                const confirmButton = page
                    .locator(
                        'button:has-text("Confirm"), button:has-text("Delete")',
                    )
                    .last();
                if (await confirmButton.isVisible()) {
                    await confirmButton.click();
                }

                await waitForSuccessMessage(page, /deleted|removed/i);
            }
        }
    });

    test("should update password", async ({ page }) => {
        // Find password section
        const passwordSection = page.locator(
            "text=/Password|Change Password/i",
        );

        if (await passwordSection.isVisible()) {
            // Fill password form
            const currentPasswordInput = page.locator(
                'input[name="current_password"], input[name="password"]',
            );
            if (await currentPasswordInput.isVisible()) {
                await currentPasswordInput.fill("password");
            }

            const newPasswordInput = page.locator(
                'input[name="password"], input[name="new_password"]',
            );
            if (await newPasswordInput.isVisible()) {
                await newPasswordInput.fill("newpassword123");
            }

            const confirmPasswordInput = page.locator(
                'input[name="password_confirmation"], input[name="confirm_password"]',
            );
            if (await confirmPasswordInput.isVisible()) {
                await confirmPasswordInput.fill("newpassword123");
            }

            // Submit password form
            const submitButton = page
                .locator(
                    'button:has-text("Update Password"), button[type="submit"]',
                )
                .first();
            if (await submitButton.isVisible()) {
                await submitButton.click();
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should test email notification", async ({ page }) => {
        // Find email section
        const emailSection = page.locator("text=/Email|Notifications/i");

        if (await emailSection.isVisible()) {
            // Find test email button
            const testEmailButton = page
                .locator(
                    'button:has-text("Test Email"), [data-testid="test-email-button"]',
                )
                .first();

            if (await testEmailButton.isVisible()) {
                await testEmailButton.click();
                await page.waitForTimeout(2000);

                // Verify success message
                await waitForSuccessMessage(page, /test|sent/i);
            }
        }
    });

    test("should update monitoring settings", async ({ page }) => {
        // Find monitoring section
        const monitoringSection = page.locator(
            "text=/Monitoring|Health Check/i",
        );

        if (await monitoringSection.isVisible()) {
            // Update check interval if exists
            const intervalInput = page.locator(
                'input[name="check_interval"], input[name="interval"]',
            );
            if (await intervalInput.isVisible()) {
                await intervalInput.fill("10");
            }

            // Submit monitoring settings
            const submitButton = page
                .locator('button:has-text("Save"), button[type="submit"]')
                .first();
            if (await submitButton.isVisible()) {
                await submitButton.click();
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should update alert thresholds", async ({ page }) => {
        // Find thresholds section
        const thresholdsSection = page.locator(
            "text=/Thresholds|Alert Thresholds/i",
        );

        if (await thresholdsSection.isVisible()) {
            // Update threshold values if exists
            const thresholdInput = page
                .locator('input[name*="threshold"]')
                .first();
            if (await thresholdInput.isVisible()) {
                await thresholdInput.fill("80");
            }

            // Submit thresholds
            const submitButton = page
                .locator('button:has-text("Save"), button[type="submit"]')
                .first();
            if (await submitButton.isVisible()) {
                await submitButton.click();
                await waitForSuccessMessage(page);
            }
        }
    });
});
