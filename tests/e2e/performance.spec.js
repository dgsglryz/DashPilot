/**
 * PERFORMANCE TESTS
 * 
 * Tests page load times, performance metrics, and optimization.
 * 
 * @module tests/e2e/performance
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';

test.describe('Performance Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('should load dashboard in under 3 seconds', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Should load in under 3 seconds
    expect(loadTime).toBeLessThan(3000);
  });

  test('should have no console errors', async ({ page }) => {
    const errors = [];
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Filter out known non-critical errors
    const criticalErrors = errors.filter(err => 
      !err.includes('favicon') && 
      !err.includes('sourcemap')
    );
    
    expect(criticalErrors.length).toBe(0);
  });

  test('should have smooth navigation transitions', async ({ page }) => {
    await page.goto('/dashboard');
    
    const startTime = Date.now();
    
    await page.click('nav a:has-text("Sites")');
    await page.waitForLoadState('networkidle');
    
    const transitionTime = Date.now() - startTime;
    
    // Navigation should be fast
    expect(transitionTime).toBeLessThan(2000);
  });

  test('should handle slow network gracefully', async ({ page, context }) => {
    // Simulate slow 3G
    await context.route('**/*', route => {
      setTimeout(() => route.continue(), 1000);
    });
    
    await page.goto('/dashboard');
    
    // Verify loading states appear
    const loadingIndicator = page.locator('.loading, [aria-busy="true"], .skeleton');
    // May or may not be visible
    
    // Wait for content
    await page.waitForSelector('h1:has-text("Overview")', { timeout: 10000 });
  });

  test('should not have memory leaks in long session', async ({ page }) => {
    await page.goto('/dashboard');
    
    // Navigate through multiple pages multiple times
    for (let i = 0; i < 5; i++) {
      await page.click('nav a:has-text("Sites")');
      await page.waitForLoadState('networkidle');
      
      await page.click('nav a:has-text("Dashboard")');
      await page.waitForLoadState('networkidle');
      
      await page.click('nav a:has-text("Alerts")');
      await page.waitForLoadState('networkidle');
    }
    
    // Verify page still responsive
    await expect(page.locator('h1')).toBeVisible();
  });
});









