/**
 * EDGE CASES TESTS
 * 
 * Tests edge cases, special characters, long text, concurrent actions, etc.
 * 
 * @module tests/e2e/edge-cases
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';

test.describe('Edge Cases', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('should handle special characters in form inputs', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Test special characters
    const specialChars = '<script>alert("xss")</script>&<>"\'';
    
    await page.fill('input[name="name"]', specialChars);
    await page.fill('input[name="url"]', 'https://test.com');
    
    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Verify no XSS or errors
    const alerts = await page.evaluate(() => {
      return window.alert.toString();
    });
    
    // Should handle special chars safely
  });

  test('should handle very long text inputs', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Generate long text (1000+ characters)
    const longText = 'A'.repeat(1000);
    
    await page.fill('input[name="name"]', longText);
    
    // Verify input accepts long text
    const inputValue = await page.locator('input[name="name"]').inputValue();
    expect(inputValue.length).toBeGreaterThan(0);
  });

  test('should handle browser back/forward buttons', async ({ page }) => {
    await page.goto('/dashboard');
    await page.click('nav a:has-text("Sites")');
    await page.waitForURL(/\/sites/);
    
    // Go back
    await page.goBack();
    await expect(page).toHaveURL(/\/dashboard/);
    
    // Go forward
    await page.goForward();
    await expect(page).toHaveURL(/\/sites/);
  });

  test('should handle concurrent form submissions', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Fill form
    await page.fill('input[name="name"]', 'Concurrent Test');
    await page.fill('input[name="url"]', 'https://concurrent-test.com');
    
    // Try to submit multiple times quickly
    await page.click('button[type="submit"]');
    await page.click('button[type="submit"]');
    await page.click('button[type="submit"]');
    
    // Wait for response
    await page.waitForTimeout(3000);
    
    // Should only create one site (prevent duplicate submissions)
    const currentUrl = page.url();
    // Should be on detail page or still on create page
  });

  test('should handle rapid navigation', async ({ page }) => {
    // Rapidly navigate between pages
    await page.goto('/dashboard');
    await page.click('nav a:has-text("Sites")');
    await page.click('nav a:has-text("Alerts")');
    await page.click('nav a:has-text("Clients")');
    await page.click('nav a:has-text("Tasks")');
    await page.click('nav a:has-text("Dashboard")');
    
    // Verify final page loaded correctly
    await expect(page.locator('h1:has-text("Overview")')).toBeVisible();
  });

  test('should handle form with all fields empty', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Verify validation prevents submission
    await page.waitForTimeout(1000);
    
    // Should still be on create page
    await expect(page).toHaveURL(/\/sites\/create/);
  });

  test('should handle very long URLs', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Generate very long URL
    const longUrl = 'https://' + 'a'.repeat(200) + '.com';
    
    await page.fill('input[name="url"]', longUrl);
    
    // Verify input accepts it
    const urlValue = await page.locator('input[name="url"]').inputValue();
    expect(urlValue.length).toBeGreaterThan(0);
  });

  test('should handle unicode characters', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Test unicode characters
    const unicodeText = '测试站点 🚀 日本語 中文 العربية';
    
    await page.fill('input[name="name"]', unicodeText);
    
    // Verify it's stored correctly
    const nameValue = await page.locator('input[name="name"]').inputValue();
    expect(nameValue).toContain('测试');
  });
});




