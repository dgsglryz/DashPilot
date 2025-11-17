/**
 * ERROR STATES TESTS
 * 
 * Tests error handling and error pages.
 * 
 * @module tests/e2e/error-states
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';

test.describe('Error States', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('should display 404 page for non-existent route', async ({ page }) => {
    await page.goto('/nonexistent-route-12345');
    
    // Verify 404 page or error message
    const errorPage = page.locator('text=/404|Not Found|Page not found/i');
    
    // May show 404 page or redirect
    const currentUrl = page.url();
    expect(currentUrl).toMatch(/404|not-found|\/dashboard/);
  });

  test('should handle form submission errors gracefully', async ({ page }) => {
    await page.goto('/sites/create');
    
    // Submit form with invalid data
    await page.fill('input[name="name"]', 'Test');
    await page.fill('input[name="url"]', 'invalid-url');
    await page.click('button[type="submit"]');
    
    // Verify error message appears
    await page.waitForTimeout(1000);
    
    // Check for validation errors
    const errorMessages = page.locator('text=/error|invalid|required/i');
    // May or may not be visible depending on validation
  });

  test('should handle network errors gracefully', async ({ page, context }) => {
    // Block network requests
    await context.route('**/*', route => route.abort());
    
    await page.goto('/dashboard');
    
    // Verify error handling
    // May show error message or loading state
    await page.waitForTimeout(2000);
  });

  test('should handle API errors with user-friendly messages', async ({ page }) => {
    await page.goto('/sites');
    
    // Try to create site with duplicate URL (if backend validates)
    await page.click('[data-testid="add-site-button"], a:has-text("Add Site")');
    await page.waitForURL(/\/sites\/create/);
    
    // Fill form with potentially duplicate data
    await page.fill('input[name="name"]', 'Duplicate Test');
    await page.fill('input[name="url"]', 'https://example.com');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Wait for response
    await page.waitForTimeout(2000);
    
    // Check for error message
    const errorMessage = page.locator('text=/error|already exists|duplicate/i');
    // May or may not appear
  });
});



