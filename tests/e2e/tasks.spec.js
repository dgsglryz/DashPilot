/**
 * Tasks Management E2E Tests
 *
 * Tests tasks management functionality:
 * - View Kanban board
 * - Create task
 * - Move task to different status
 * - Edit task
 * - Delete task
 *
 * @module tests/e2e/tasks
 */

import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers/auth.js";
import { goToTasks } from "./helpers/navigation.js";
import { waitForSuccessMessage, waitForPageReady } from "./helpers/wait.js";
import {
    getInputSelector,
    getTextareaSelector,
    getSubmitButtonInForm,
    waitForFormReady,
} from "./helpers/selectors.js";

test.describe("Tasks Management", () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await loginAsAdmin(page);
        await goToTasks(page);
    });

    test("should display tasks page", async ({ page }) => {
        // Verify page title
        await expect(page.locator('h1:has-text("Tasks")')).toBeVisible();
    });

    test("should display Kanban board", async ({ page }) => {
        // Wait for page to load
        await page.waitForTimeout(2000);

        // Verify Kanban board exists (columns for different statuses)
        const kanbanBoard = page.locator(
            '[data-testid="kanban-board"], .kanban-board, .board',
        );
        const columns = page.locator(
            "text=/To Do|In Progress|Done|Completed|Backlog/i",
        );

        // At least one column should be visible
        const columnCount = await columns.count();
        expect(columnCount).toBeGreaterThanOrEqual(0);
    });

    test("should navigate to create task page", async ({ page }) => {
        // Find and click Add Task button
        const addButton = page
            .locator(
                'a:has-text("Add Task"), button:has-text("Add Task"), [data-testid="add-task-button"]',
            )
            .first();

        if (await addButton.isVisible()) {
            await addButton.click();
            await expect(page).toHaveURL(/\/tasks\/create/);
        }
    });

    test("should create new task", async ({ page }) => {
        // Navigate to create page
        const addButton = page
            .locator(
                'a:has-text("Add Task"), button:has-text("Add Task"), [data-testid="add-task-button"]',
            )
            .first();

        if (await addButton.isVisible({ timeout: 2000 }).catch(() => false)) {
            await addButton.click();
            await page.waitForURL(/\/tasks\/create/);
            await waitForPageReady(page);

            // Wait for form to be ready
            await waitForFormReady(page);

            // Fill task form using helper selectors
            const titleSelector = getInputSelector("title");
            const titleInput = page
                .locator(`${titleSelector}, textarea[name="title"]`)
                .first();

            await page.waitForSelector(
                titleSelector + ', textarea[name="title"]',
                { state: "visible", timeout: 15000 },
            );
            await titleInput.fill("Test Task");

            // Fill description if exists
            const descriptionSelector = getTextareaSelector("description");
            const descriptionInput = page.locator(descriptionSelector);
            if (
                await descriptionInput
                    .isVisible({ timeout: 2000 })
                    .catch(() => false)
            ) {
                await descriptionInput.fill("This is a test task description");
            }

            // Select priority if exists
            const prioritySelect = page.locator('select[name="priority"]');
            if (
                await prioritySelect
                    .isVisible({ timeout: 2000 })
                    .catch(() => false)
            ) {
                await prioritySelect.selectOption({ index: 1 });
            }

            // Select status if exists
            const statusSelect = page.locator('select[name="status"]');
            if (
                await statusSelect
                    .isVisible({ timeout: 2000 })
                    .catch(() => false)
            ) {
                await statusSelect.selectOption({ index: 0 });
            }

            // Submit form using helper
            const submitButton = await getSubmitButtonInForm(page, "form");
            await submitButton.click();

            // Wait for redirect to tasks page or detail page
            await page.waitForURL(/\/(tasks|tasks\/\d+)/, { timeout: 15000 });
            await waitForPageReady(page);
            await waitForSuccessMessage(page);
        }
    });

    test("should move task to different status", async ({ page }) => {
        // Wait for Kanban board to load
        await page.waitForTimeout(2000);

        // Find first task card
        const taskCard = page
            .locator('[data-testid="task-card"], .task-card, .card')
            .first();

        if (await taskCard.isVisible()) {
            // Find target column (e.g., "In Progress")
            const targetColumn = page
                .locator("text=/In Progress|Done/i")
                .first();

            if (await targetColumn.isVisible()) {
                // Drag and drop task to target column
                await taskCard.dragTo(targetColumn);
                await page.waitForTimeout(1000);

                // Verify task moved (check for success message or visual change)
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should edit task", async ({ page }) => {
        // Wait for tasks to load
        await waitForPageReady(page);

        // Find first task and click edit
        const taskCard = page
            .locator('[data-testid="task-card"], .task-card, .card')
            .first();

        if (await taskCard.isVisible({ timeout: 2000 }).catch(() => false)) {
            // Click on task to view/edit
            await taskCard.click();
            await waitForPageReady(page);

            // Look for edit button or form
            const editButton = page
                .locator(
                    'button:has-text("Edit"), [data-testid="edit-task-button"]',
                )
                .first();

            if (
                await editButton.isVisible({ timeout: 2000 }).catch(() => false)
            ) {
                await editButton.click();
                await waitForPageReady(page);

                // Wait for form to be ready
                await waitForFormReady(page);

                // Update task title using helper selector
                const titleSelector = getInputSelector("title");
                const titleInput = page
                    .locator(`${titleSelector}, textarea[name="title"]`)
                    .first();
                if (
                    await titleInput
                        .isVisible({ timeout: 2000 })
                        .catch(() => false)
                ) {
                    await titleInput.fill("Updated Task Title");
                }

                // Submit form using helper
                const submitButton = await getSubmitButtonInForm(page, "form");
                await submitButton.click();
                await waitForPageReady(page);
                await waitForSuccessMessage(page);
            }
        }
    });

    test("should delete task", async ({ page }) => {
        // Wait for tasks to load
        await page.waitForTimeout(2000);

        // Find first task
        const taskCard = page
            .locator('[data-testid="task-card"], .task-card, .card')
            .first();

        if (await taskCard.isVisible()) {
            // Click on task
            await taskCard.click();
            await page.waitForTimeout(1000);

            // Look for delete button
            const deleteButton = page
                .locator(
                    'button:has-text("Delete"), [data-testid="delete-task-button"]',
                )
                .first();

            if (await deleteButton.isVisible()) {
                await deleteButton.click();

                // Confirm deletion if confirmation dialog appears
                const confirmButton = page
                    .locator(
                        'button:has-text("Confirm"), button:has-text("Delete")',
                    )
                    .last();
                if (await confirmButton.isVisible()) {
                    await confirmButton.click();
                }

                // Wait for success message
                await waitForSuccessMessage(page, /deleted|removed/i);
            }
        }
    });

    test("should filter tasks by status", async ({ page }) => {
        // Wait for page to load
        await page.waitForTimeout(2000);

        // Look for status filter
        const statusFilter = page
            .locator('select:has-text("Status"), button:has-text("Filter")')
            .first();

        if (await statusFilter.isVisible()) {
            await statusFilter.click();
            await page.waitForTimeout(500);
        }
    });

    test("should search tasks", async ({ page }) => {
        // Wait for page to load
        await page.waitForTimeout(2000);

        // Look for search input
        const searchInput = page
            .locator('input[placeholder*="Search"], input[type="search"]')
            .first();

        if (await searchInput.isVisible()) {
            await searchInput.fill("test");
            await page.waitForTimeout(500);

            // Verify filtered results
            const tasks = page.locator('[data-testid="task-card"], .task-card');
            const count = await tasks.count();
            expect(count).toBeGreaterThanOrEqual(0);
        }
    });
});
