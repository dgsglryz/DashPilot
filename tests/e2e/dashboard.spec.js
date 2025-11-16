/**
 * Dashboard E2E Tests
 * 
 * Tests dashboard functionality:
 * - View all stats cards
 * - Verify charts render
 * - Activity feed shows items
 * - Navigation to other sections
 * 
 * @module tests/e2e/dashboard
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { goToSites, goToAlerts, goToClients, goToTasks } = require('./helpers/navigation');

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await loginAsAdmin(page);
    await page.goto('/dashboard');
  });

  test('should display dashboard with all stats cards', async ({ page }) => {
    // Verify page title
    await expect(page.locator('h1:has-text("Overview")')).toBeVisible();

    // Verify stats cards exist (at least 3-4 cards)
    const statsCards = page.locator('[data-testid="stats-card"]');
    const count = await statsCards.count();
    expect(count).toBeGreaterThanOrEqual(3);

    // Verify specific stat cards are visible
    await expect(page.locator('text=/Site Monitoring|SEO Performance|Revenue Overview/i')).toBeVisible();
  });

  test('should display performance chart', async ({ page }) => {
    // Verify performance chart exists
    const chart = page.locator('[data-testid="performance-chart"]');
    await expect(chart).toBeVisible({ timeout: 10000 });
  });

  test('should display activity feed', async ({ page }) => {
    // Scroll to activity feed section
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

    // Verify activity feed section exists
    const activityFeed = page.locator('text=/Activity Feed/i');
    await expect(activityFeed).toBeVisible();
  });

  test('should display current operations section', async ({ page }) => {
    // Verify current operations section
    await expect(page.locator('text=/Current operations/i')).toBeVisible();
    await expect(page.locator('text=/Health Checks|Queue Workers|Cache Status/i')).toBeVisible();
  });

  test('should navigate to sites from dashboard', async ({ page }) => {
    // Click Sites navigation
    await goToSites(page);

    // Verify on sites page
    await expect(page).toHaveURL(/\/sites/);
    await expect(page.locator('h1:has-text("Sites")')).toBeVisible();
  });

  test('should navigate to alerts from dashboard', async ({ page }) => {
    // Click Alerts navigation
    await goToAlerts(page);

    // Verify on alerts page
    await expect(page).toHaveURL(/\/alerts/);
  });

  test('should navigate to clients from dashboard', async ({ page }) => {
    // Click Clients navigation
    await goToClients(page);

    // Verify on clients page
    await expect(page).toHaveURL(/\/clients/);
  });

  test('should navigate to tasks from dashboard', async ({ page }) => {
    // Click Tasks navigation
    await goToTasks(page);

    // Verify on tasks page
    await expect(page).toHaveURL(/\/tasks/);
  });

  test('should display featured sites section', async ({ page }) => {
    // Scroll to featured sites
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

    // Verify featured sites section
    await expect(page.locator('text=/Featured sites/i')).toBeVisible();
  });

  test('should click on featured site and navigate to site detail', async ({ page }) => {
    // Wait for featured sites to load
    await page.waitForSelector('a[href*="/sites/"]', { timeout: 10000 });

    // Click first featured site
    const firstSiteLink = page.locator('a[href*="/sites/"]').first();
    await firstSiteLink.click();

    // Verify navigated to site detail page
    await expect(page).toHaveURL(/\/sites\/\d+/);
  });

  test('should display live updates indicator', async ({ page }) => {
    // Verify live updates indicator
    await expect(page.locator('text=/Live updates|Paused/i')).toBeVisible();
  });
});

