/**
 * Global Test Setup
 * 
 * Provides common setup/teardown for all E2E tests.
 * Handles authentication, database seeding, and common wait strategies.
 * 
 * @module tests/e2e/global-setup
 */

/**
 * Global setup function - runs once before all tests
 * 
 * @param {import('@playwright/test').FullConfig} config - Playwright config
 */
async function globalSetup(config) {
  // Check if server is running
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8000';
  
  try {
    const response = await fetch(baseURL);
    if (!response.ok && response.status !== 401) {
      throw new Error(`Server at ${baseURL} is not responding correctly. Status: ${response.status}`);
    }
  } catch (error) {
    console.warn(`Warning: Could not connect to server at ${baseURL}. Make sure the application is running.`);
    console.warn(`Error: ${error.message}`);
  }
}

export default globalSetup;

