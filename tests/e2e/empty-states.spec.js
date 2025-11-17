/**
 * EMPTY STATES TESTS
 * 
 * Tests empty states for all pages when no data exists.
 * 
 * @module tests/e2e/empty-states
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';

test.describe('Empty States', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('should display empty state on sites page when no sites', async ({ page }) => {
    await page.goto('/sites');
    await page.waitForTimeout(2000);
    
    // Check for empty state
    const emptyState = page.locator('text=/No sites|empty|get started/i');
    
    // If no sites exist, verify empty state
    const sitesTable = page.locator('[data-testid="sites-table"]');
    const hasSites = await sitesTable.locator('[data-testid="site-row"]').count() > 0;
    
    if (!hasSites) {
      await expect(emptyState).toBeVisible();
      
      // Verify "Add Site" button in empty state
      const addButton = page.locator('button:has-text("Add"), a:has-text("Add Site")');
      await expect(addButton).toBeVisible();
    }
  });

  test('should display empty state on alerts page when no alerts', async ({ page }) => {
    await page.goto('/alerts');
    await page.waitForTimeout(2000);
    
    const emptyState = page.locator('text=/No alerts|All systems|running smoothly/i');
    
    const alertsList = page.locator('[data-testid="alerts-list"]');
    const hasAlerts = await alertsList.locator('[data-testid="alert-card"]').count() > 0;
    
    if (!hasAlerts) {
      await expect(emptyState).toBeVisible();
    }
  });

  test('should display empty state on tasks page when no tasks', async ({ page }) => {
    await page.goto('/tasks');
    await page.waitForTimeout(2000);
    
    const emptyState = page.locator('text=/No tasks|empty|create/i');
    
    const kanbanBoard = page.locator('[data-testid="kanban-board"]');
    const hasTasks = await kanbanBoard.locator('[data-testid="task-card"]').count() > 0;
    
    if (!hasTasks) {
      await expect(emptyState).toBeVisible();
      
      // Verify "Create Task" button
      const createButton = page.locator('[data-testid="add-task-button"], a:has-text("Create Task")');
      await expect(createButton).toBeVisible();
    }
  });

  test('should display empty state on clients page when no clients', async ({ page }) => {
    await page.goto('/clients');
    await page.waitForTimeout(2000);
    
    const emptyState = page.locator('text=/No clients|empty|first client/i');
    
    const clientsTable = page.locator('[data-testid="clients-table"]');
    const hasClients = await clientsTable.locator('[data-testid="client-row"]').count() > 0;
    
    if (!hasClients) {
      await expect(emptyState).toBeVisible();
    }
  });
});




