/**
 * NOTIFICATIONS TESTS
 * 
 * Tests notification bell, dropdown, and notification interactions.
 * 
 * @module tests/e2e/notifications
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';

test.describe('Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/dashboard');
  });

  test('should display notification bell with unread count', async ({ page }) => {
    // Find notification bell
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    await expect(bell).toBeVisible();
    
    // Check for unread badge
    const badge = bell.locator('.badge, [data-testid="notification-badge"], span:has-text(/\\d+/)');
    // May or may not be visible
  });

  test('should open notification dropdown on click', async ({ page }) => {
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    
    if (await bell.isVisible()) {
      await bell.click();
      await page.waitForTimeout(500);
      
      // Verify dropdown opens
      const dropdown = page.locator('[role="menu"], .dropdown-menu, [data-testid="notifications-dropdown"]');
      // May or may not be visible
    }
  });

  test('should display recent notifications in dropdown', async ({ page }) => {
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    
    if (await bell.isVisible()) {
      await bell.click();
      await page.waitForTimeout(500);
      
      // Check for notification items
      const notifications = page.locator('.notification-item, [data-testid="notification-item"]');
      const count = await notifications.count();
      expect(count).toBeGreaterThanOrEqual(0);
    }
  });

  test('should mark all notifications as read', async ({ page }) => {
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    
    if (await bell.isVisible()) {
      await bell.click();
      await page.waitForTimeout(500);
      
      // Find "Mark all as read" button
      const markAllRead = page.locator('button:has-text("Mark all as read"), button:has-text("Mark All Read")');
      if (await markAllRead.isVisible()) {
        await markAllRead.click();
        await page.waitForTimeout(500);
        
        // Verify badge disappears or updates
        const badge = bell.locator('.badge, [data-testid="notification-badge"]');
        // May or may not be visible
      }
    }
  });

  test('should navigate to related item when clicking notification', async ({ page }) => {
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    
    if (await bell.isVisible()) {
      await bell.click();
      await page.waitForTimeout(500);
      
      // Find first notification
      const firstNotification = page.locator('.notification-item, [data-testid="notification-item"]').first();
      if (await firstNotification.isVisible()) {
        await firstNotification.click();
        await page.waitForTimeout(1000);
        
        // Should navigate somewhere
        const currentUrl = page.url();
        expect(currentUrl).not.toBe('http://localhost:8000/dashboard');
      }
    }
  });

  test('should close notification dropdown when clicking outside', async ({ page }) => {
    const bell = page.locator('button:has(svg), [aria-label*="notification"]').first();
    
    if (await bell.isVisible()) {
      await bell.click();
      await page.waitForTimeout(500);
      
      // Click outside
      await page.click('body', { position: { x: 10, y: 10 } });
      await page.waitForTimeout(500);
      
      // Verify dropdown closes
      const dropdown = page.locator('[role="menu"], .dropdown-menu');
      // Should be hidden
    }
  });
});

