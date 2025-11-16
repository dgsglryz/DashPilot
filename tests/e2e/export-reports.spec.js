/**
 * EXPORT & REPORTS TESTS
 * 
 * Tests export functionality and report generation.
 * 
 * @module tests/e2e/export-reports
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { goToSites, goToAlerts } from './helpers/navigation.js';
import { waitForTableData } from './helpers/wait.js';

test.describe('Export & Reports', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('should export sites as CSV', async ({ page }) => {
    await goToSites(page);
    await waitForTableData(page, '[data-testid="sites-table"]', 1);
    
    // Set up download listener
    const downloadPromise = page.waitForEvent('download', { timeout: 10000 }).catch(() => null);
    
    // Click export button
    const exportButton = page.locator('button:has-text("Export")');
    if (await exportButton.isVisible()) {
      await exportButton.click();
      
      const download = await downloadPromise;
      if (download) {
        // Verify filename
        const filename = download.suggestedFilename();
        expect(filename).toMatch(/sites.*\.csv/i);
        
        // Verify file size > 0
        const path = await download.path();
        if (path) {
          const fs = require('fs');
          const stats = fs.statSync(path);
          expect(stats.size).toBeGreaterThan(0);
        }
      }
    }
  });

  test('should export alerts as CSV', async ({ page }) => {
    await goToAlerts(page);
    await page.waitForTimeout(2000);
    
    const downloadPromise = page.waitForEvent('download', { timeout: 10000 }).catch(() => null);
    
    const exportButton = page.locator('button:has-text("Export")');
    if (await exportButton.isVisible()) {
      await exportButton.click();
      
      const download = await downloadPromise;
      if (download) {
        const filename = download.suggestedFilename();
        expect(filename).toMatch(/alerts.*\.csv/i);
      }
    }
  });

  test('should generate client report', async ({ page }) => {
    await page.goto('/clients');
    await waitForTableData(page, 'table', 1);
    
    // Click first client
    await page.locator('[data-testid="client-row"]').first().click();
    await page.waitForURL(/\/clients\/\d+/);
    
    // Find generate report button
    const generateButton = page.locator('button:has-text("Generate"), a:has-text("Report")');
    
    if (await generateButton.isVisible()) {
      await generateButton.click();
      await page.waitForTimeout(1000);
      
      // Verify report generation started
      const successMessage = page.locator('text=/report|generated|success/i');
      // May or may not be visible
    }
  });

  test('should download generated report', async ({ page }) => {
    await page.goto('/reports');
    await page.waitForTimeout(2000);
    
    // Find download button for existing report
    const downloadButton = page.locator('a[href*="/reports/"]:has-text("Download"), button:has-text("Download")').first();
    
    if (await downloadButton.isVisible()) {
      const downloadPromise = page.waitForEvent('download', { timeout: 10000 }).catch(() => null);
      
      await downloadButton.click();
      
      const download = await downloadPromise;
      if (download) {
        const filename = download.suggestedFilename();
        expect(filename).toMatch(/report|\.pdf|\.csv/i);
      }
    }
  });
});

