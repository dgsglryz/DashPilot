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

  // Test timeout (30 seconds per test - reduced for faster feedback)
  timeout: 30 * 1000,

  // Expect timeout (3 seconds for assertions - reduced)
  expect: {
    timeout: 3000
  },

  // Retry failed tests (2 retries in CI, 0 locally for faster feedback)
  retries: process.env.CI ? 2 : 0,

  // Parallel workers (optimized: 2 in CI, 6 locally for maximum speed)
  workers: process.env.CI ? 2 : 6,

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

    // Action timeout (reduced for faster failure detection)
    actionTimeout: 8000,

    // Navigation timeout (reduced)
    navigationTimeout: 20000,
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

  // Global setup/teardown
  globalSetup: './tests/e2e/global-setup.js',

  // Web server configuration (optional - Laravel handles this via Docker)
  // Uncomment if you want Playwright to start the server automatically
  // webServer: {
  //   command: 'docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120 * 1000,
  // },
});

