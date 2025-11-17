/**
 * Playwright E2E Test Configuration
 * 
 * Comprehensive E2E testing setup for DashPilot operations dashboard.
 * Tests all admin workflows: authentication, sites management, alerts, clients, tasks, and settings.
 * 
 * @see https://playwright.dev/docs/test-configuration
 */
import { defineConfig, devices } from '@playwright/test';
import { existsSync } from 'fs';

export default defineConfig({
  // Test directory
  testDir: './tests/e2e',

  // Test timeout (30 seconds per test - optimized for faster feedback)
  timeout: 30 * 1000,

  // Expect timeout (3 seconds for assertions - faster failure detection)
  expect: {
    timeout: 3000
  },

  // Retry failed tests (2 retries in CI, 0 locally for faster feedback)
  retries: process.env.CI ? 2 : 0,

  // Parallel workers (optimized for performance)
  // Use 50% of CPU cores locally, 2 in CI for stability
  workers: process.env.CI ? 2 : '50%',

  // Maximum failures before stopping test run (fail fast)
  maxFailures: process.env.CI ? undefined : 5,

  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ['list']
  ],

  // Shared settings for all tests
  use: {
    // Base URL for all tests
    baseURL: process.env.APP_URL || 'http://localhost:8000',

    // Screenshot on failure only (saves disk space and time)
    screenshot: 'only-on-failure',

    // Video on failure only (saves disk space and time)
    video: 'retain-on-failure',

    // Trace on first retry only (saves disk space)
    trace: 'on-first-retry',

    // Viewport size (smaller for faster rendering)
    viewport: { width: 1280, height: 720 },

    // Action timeout (reduced for faster failure detection)
    actionTimeout: 8000,

    // Navigation timeout (reduced for faster failure detection)
    navigationTimeout: 20000,

    // Ignore HTTPS errors (useful for local development)
    ignoreHTTPSErrors: true,
  },

  // Test projects (browsers to test)
  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        // Use authenticated storage state from global setup (reuses login session)
        // Only use if file exists, otherwise tests will login individually
        ...(existsSync('./tests/e2e/.auth/storage-state.json') 
          ? { storageState: './tests/e2e/.auth/storage-state.json' }
          : {}
        ),
      },
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

