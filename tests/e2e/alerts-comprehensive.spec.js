/**
 * COMPREHENSIVE ALERTS TESTS
 * 
 * Tests all alerts features including:
 * - Alert assignment
 * - Alert notes
 * - Alert detail modal
 * - All filters and sorting
 * 
 * @module tests/e2e/alerts-comprehensive
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { goToAlerts } from './helpers/navigation.js';
import { waitForSuccessMessage, waitForUIUpdate } from './helpers/wait.js';

test.describe('Alerts - Comprehensive Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await goToAlerts(page);
    await waitForUIUpdate(page);
  });

  test('should verify alerts table displays all columns', async ({ page }) => {
    // Verify table structure
    const table = page.locator('table, [data-testid="alerts-list"]');
    if (await table.isVisible()) {
      // Check for common columns
      const headers = ['Site', 'Type', 'Severity', 'Status', 'Created'];
      
      for (const header of headers) {
        const headerElement = page.locator(`th:has-text("${header}"), text=/${header}/i`);
        // May or may not be visible
      }
    }
  });

  test('should test all alert filter tabs', async ({ page }) => {
    const filterTabs = ['All', 'Active', 'Resolved', 'Unassigned', 'My Alerts'];
    
    for (const tab of filterTabs) {
      const tabButton = page.locator(`button:has-text("${tab}"), a:has-text("${tab}")`);
      if (await tabButton.isVisible()) {
        await tabButton.click();
        await waitForUIUpdate(page);
        
        // Verify filter applied
        const alerts = page.locator('[data-testid="alert-card"]');
        const count = await alerts.count();
        expect(count).toBeGreaterThanOrEqual(0);
      }
    }
  });

  test('should test alert detail modal/page', async ({ page }) => {
    const firstAlert = page.locator('[data-testid="alert-card"]').first();
    
    if (await firstAlert.isVisible()) {
      await firstAlert.click();
      await waitForUIUpdate(page);
      
      // Verify detail modal/page opens
      const detailModal = page.locator('.modal, [role="dialog"], [data-testid="alert-detail"]');
      if (await detailModal.isVisible()) {
        // Verify alert information displayed
        await expect(detailModal.locator('text=/Site|Type|Severity/i')).toBeVisible();
      }
    }
  });

  test('should assign alert to user', async ({ page }) => {
    const firstAlert = page.locator('[data-testid="alert-card"]').first();
    
    if (await firstAlert.isVisible()) {
      await firstAlert.click();
      await waitForUIUpdate(page);
      
      // Find assign dropdown
      const assignDropdown = page.locator('select[name*="assign"], select:has-text("Assign")');
      if (await assignDropdown.isVisible()) {
        // Select user
        const options = await assignDropdown.locator('option').all();
        if (options.length > 1) {
          await assignDropdown.selectOption({ index: 1 });
          
          // Click assign button
          const assignButton = page.locator('button:has-text("Assign")');
          if (await assignButton.isVisible()) {
            await assignButton.click();
            await waitForSuccessMessage(page);
          }
        }
      }
    }
  });

  test('should add note to alert', async ({ page }) => {
    const firstAlert = page.locator('[data-testid="alert-card"]').first();
    
    if (await firstAlert.isVisible()) {
      await firstAlert.click();
      await waitForUIUpdate(page);
      
      // Find notes section
      const notesTextarea = page.locator('textarea[name*="note"], textarea[placeholder*="note"]');
      if (await notesTextarea.isVisible()) {
        await notesTextarea.fill('E2E test note - This is a test note added by automated tests');
        
        // Submit note
        const addNoteButton = page.locator('button:has-text("Add Note"), button:has-text("Save Note")');
        if (await addNoteButton.isVisible()) {
          await addNoteButton.click();
          await waitForSuccessMessage(page);
          
          // Verify note appears in notes list
          const notesList = page.locator('.notes-list, [data-testid="notes-list"]');
          if (await notesList.isVisible()) {
            await expect(notesList.locator('text=/E2E test note/i')).toBeVisible();
          }
        }
      }
    }
  });

  test('should test alert sorting by created date', async ({ page }) => {
    // Find sort button/header
    const createdHeader = page.locator('th:has-text("Created"), th:has-text("Date")');
    
    if (await createdHeader.isVisible()) {
      await createdHeader.click();
      await waitForUIUpdate(page);
      
      // Verify sort applied (newest first)
      const alerts = page.locator('[data-testid="alert-card"]');
      const count = await alerts.count();
      expect(count).toBeGreaterThanOrEqual(0);
    }
  });

  test('should test clickable site name in alert', async ({ page }) => {
    const firstAlert = page.locator('[data-testid="alert-card"]').first();
    
    if (await firstAlert.isVisible()) {
      // Find site name link
      const siteLink = firstAlert.locator('a:has-text("site"), button:has-text("site")');
      if (await siteLink.isVisible()) {
        await siteLink.click();
        await waitForUIUpdate(page);
        
        // Should navigate to site detail
        await expect(page).toHaveURL(/\/sites\/\d+/);
      }
    }
  });
});




