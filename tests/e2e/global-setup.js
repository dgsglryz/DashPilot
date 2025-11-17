/**
 * Global Test Setup
 * 
 * Provides common setup/teardown for all E2E tests.
 * Handles authentication, database seeding, and common wait strategies.
 * Creates authenticated storage state to reuse across tests for faster execution.
 * 
 * @module tests/e2e/global-setup
 */

import { chromium } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/**
 * Global setup function - runs once before all tests
 * 
 * Creates authenticated storage state that can be reused across all tests.
 * This significantly speeds up test execution by avoiding repeated logins.
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

  // Create authenticated storage state for reuse across tests
  console.log('Creating authenticated storage state...');
  
  try {
    const browser = await chromium.launch();
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();

    // Login once
    await loginAsAdmin(page, { email: 'admin@test.com', password: 'password' });

    // Save storage state to file
    const storageStatePath = join(__dirname, '.auth', 'storage-state.json');
    mkdirSync(dirname(storageStatePath), { recursive: true });
    
    const storageState = await context.storageState();
    writeFileSync(storageStatePath, JSON.stringify(storageState, null, 2));

    // Close temporary context
    await context.close();
    await browser.close();

    console.log(`Authenticated storage state saved to ${storageStatePath}`);
  } catch (error) {
    console.warn(`Warning: Could not create authenticated storage state: ${error.message}`);
    console.warn('Tests will need to login individually (slower).');
  }
}

export default globalSetup;

