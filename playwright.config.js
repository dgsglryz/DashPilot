/**
 * Playwright E2E Test Configuration
 * 
 * Comprehensive E2E testing setup for DashPilot operations dashboard.
 * Tests all admin workflows: authentication, sites management, alerts, clients, tasks, and settings.
 * 
 * @see https://playwright.dev/docs/test-configuration
 */
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  // Test directory
  testDir: './tests/e2e',

  // Test timeout (30 seconds per test)
  timeout: 30 * 1000,

  // Expect timeout (5 seconds for assertions)
  expect: {
    timeout: 5000
  },

  // Retry failed tests (2 retries in CI, 0 locally for faster feedback)
  retries: process.env.CI ? 2 : 0,

  // Parallel workers (1 in CI for stability, auto locally)
  workers: process.env.CI ? 1 : undefined,

  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list']
  ],

  // Shared settings for all tests
  use: {
    // Base URL for all tests
    baseURL: process.env.APP_URL || 'http://localhost:8000',

    // Screenshot on failure
    screenshot: 'only-on-failure',

    // Video on failure
    video: 'retain-on-failure',

    // Trace on first retry
    trace: 'on-first-retry',

    // Viewport size
    viewport: { width: 1920, height: 1080 },

    // Action timeout
    actionTimeout: 10000,

    // Navigation timeout
    navigationTimeout: 30000,
  },

  // Test projects (browsers to test)
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    // Uncomment for multi-browser testing (adds time but increases coverage)
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },
  ],

  // Web server configuration (optional - Laravel handles this via Docker)
  // Uncomment if you want Playwright to start the server automatically
  // webServer: {
  //   command: 'docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120 * 1000,
  // },
});

