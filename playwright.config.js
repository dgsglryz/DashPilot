/**
 * Playwright E2E Test Configuration
 *
 * Comprehensive E2E testing setup for DashPilot operations dashboard.
 * Tests all admin workflows: authentication, sites management, alerts, clients, tasks, and settings.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
import { defineConfig, devices } from "@playwright/test";
import { existsSync } from "fs";

export default defineConfig({
    // Test directory
    testDir: "./tests/e2e",

    // Test timeout (15 seconds per test - aggressive for speed)
    timeout: 15 * 1000,

    // Expect timeout (2 seconds for assertions - faster failure detection)
    expect: {
        timeout: 2000,
    },

    // Retry failed tests (0 retries for maximum speed)
    retries: 0,

    // Parallel workers (8 workers - reduced to prevent server overload)
    // Use 8 workers locally for balance between speed and stability, 4 in CI
    // Note: 16 workers was causing server overload and login timeouts
    workers: process.env.CI ? 4 : (process.env.WORKERS ? parseInt(process.env.WORKERS) : 8),

    // Maximum failures before stopping test run (run entire suite to see all failures)
    maxFailures: undefined,

    // Reporter configuration
    reporter: [
        ["html", { outputFolder: "playwright-report", open: "never" }],
        ["list"],
    ],

    // Shared settings for all tests
    use: {
        // Base URL for all tests
        baseURL: process.env.APP_URL || "http://localhost:8000",

        // Disable screenshots (only on failure in CI)
        screenshot: process.env.CI ? "only-on-failure" : "off",

        // Disable video (only on failure in CI)
        video: process.env.CI ? "retain-on-failure" : "off",

        // Disable trace (only on failure in CI)
        trace: process.env.CI ? "on-first-retry" : "off",

        // Viewport size (smaller for faster rendering)
        viewport: { width: 1280, height: 720 },

        // Reuse authenticated storage state if available
        ...(existsSync("./tests/e2e/.auth/storage-state.json")
            ? { storageState: "./tests/e2e/.auth/storage-state.json" }
            : {}),

        // Action timeout (aggressive for speed)
        actionTimeout: 5000,

        // Navigation timeout (aggressive for speed)
        navigationTimeout: 10000,

        // Ignore HTTPS errors (useful for local development)
        ignoreHTTPSErrors: true,

        // Disable animations for faster execution
        reducedMotion: "reduce",
    },

    // Test projects (browsers to test)
    projects: [
        {
            name: "chromium",
            use: {
                ...devices["Desktop Chrome"],
                // Use authenticated storage state from global setup (reuses login session)
                // Only use if file exists, otherwise tests will login individually
                ...(existsSync("./tests/e2e/.auth/storage-state.json")
                    ? { storageState: "./tests/e2e/.auth/storage-state.json" }
                    : {}),
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
    globalSetup: "./tests/e2e/global-setup.js",

    // Web server configuration (optional - Laravel handles this via Docker)
    // Uncomment if you want Playwright to start the server automatically
    // webServer: {
    //   command: 'docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000',
    //   url: 'http://localhost:8000',
    //   reuseExistingServer: !process.env.CI,
    //   timeout: 120 * 1000,
    // },
});
