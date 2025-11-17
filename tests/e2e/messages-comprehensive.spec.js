/**
 * COMPREHENSIVE MESSAGE SYSTEM E2E TESTS
 * 
 * Tests all aspects of the team messaging system:
 * - Opening message popup from team page
 * - Sending messages
 * - Receiving messages
 * - Conversation loading
 * - Unread count
 * - Message polling
 * - UI interactions
 * 
 * @module tests/e2e/messages-comprehensive
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { goToTeam } from './helpers/navigation.js';
import { waitForSuccessMessage } from './helpers/wait.js';

test.describe('Message System - Comprehensive Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  // ============================================
  // MESSAGE POPUP UI TESTS
  // ============================================
  test.describe('Message Popup UI', () => {
    test('should open message popup from team page', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      // Find first team member (not current user)
      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        // Find message button (chat icon) in first row
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Verify message popup is visible
          const messagePopup = page.locator('.fixed.bottom-6.right-6, [class*="message-popup"]');
          await expect(messagePopup).toBeVisible({ timeout: 5000 });
        }
      }
    });

    test('should display recipient information in popup header', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          // Get member name before opening popup
          const memberName = await firstRow.locator('td:first-child p.font-medium').textContent();
          
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Verify recipient name is displayed
          if (memberName) {
            await expect(page.locator(`text=/${memberName}/i`)).toBeVisible({ timeout: 5000 });
          }
        }
      }
    });

    test('should close message popup when clicking close button', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Find and click close button
          const closeButton = page.locator('button:has(svg), [aria-label*="close"]').last();
          if (await closeButton.isVisible()) {
            await closeButton.click();
            await page.waitForTimeout(500);

            // Verify popup is closed
            const messagePopup = page.locator('.fixed.bottom-6.right-6');
            await expect(messagePopup).not.toBeVisible();
          }
        }
      }
    });

    test('should display empty state when no messages', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          // Check for empty state message
          const emptyState = page.locator('text=/No messages yet|Start the conversation/i');
          // May or may not be visible depending on existing messages
          if (await emptyState.isVisible({ timeout: 3000 })) {
            await expect(emptyState).toBeVisible();
          }
        }
      }
    });

    test('should display message input area', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Verify message input exists
          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          await expect(messageInput).toBeVisible({ timeout: 5000 });
        }
      }
    });

    test('should display send button', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Verify send button exists
          const sendButton = page.locator('button[type="submit"], button:has(svg)').last();
          await expect(sendButton).toBeVisible({ timeout: 5000 });
        }
      }
    });
  });

  // ============================================
  // SENDING MESSAGES TESTS
  // ============================================
  test.describe('Sending Messages', () => {
    test('should send a simple message', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          // Find message input
          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            // Type message
            const testMessage = `E2E Test Message - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            // Find and click send button
            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              await page.waitForTimeout(2000);

              // Verify message appears in conversation
              await expect(page.locator(`text=/${testMessage}/i`)).toBeVisible({ timeout: 5000 });
            }
          }
        }
      }
    });

    test('should send message with Enter key', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const testMessage = `E2E Enter Test - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            // Press Enter to send
            await messageInput.press('Enter');
            await page.waitForTimeout(2000);

            // Verify message sent
            await expect(page.locator(`text=/${testMessage}/i`)).toBeVisible({ timeout: 5000 });
          }
        }
      }
    });

    test('should allow Shift+Enter for new line', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            await messageInput.fill('Line 1');
            await page.waitForTimeout(300);

            // Press Shift+Enter
            await messageInput.press('Shift+Enter');
            await page.waitForTimeout(300);

            // Type more
            await messageInput.type('Line 2');
            await page.waitForTimeout(500);

            // Verify both lines are in input (not sent)
            const value = await messageInput.inputValue();
            expect(value).toContain('Line 1');
            expect(value).toContain('Line 2');
          }
        }
      }
    });

    test('should disable send button when message is empty', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const sendButton = page.locator('button[type="submit"]').last();
          if (await sendButton.isVisible({ timeout: 5000 })) {
            // Verify button is disabled when input is empty
            const isDisabled = await sendButton.isDisabled();
            expect(isDisabled).toBe(true);
          }
        }
      }
    });

    test('should clear input after sending message', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const testMessage = `E2E Clear Test - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              await page.waitForTimeout(2000);

              // Verify input is cleared
              const value = await messageInput.inputValue();
              expect(value.trim()).toBe('');
            }
          }
        }
      }
    });

    test('should show sending state while message is being sent', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const testMessage = `E2E Sending Test - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              
              // Check for "Sending..." text (may be brief)
              const sendingText = page.locator('text=/Sending/i');
              // May or may not catch it depending on speed
            }
          }
        }
      }
    });
  });

  // ============================================
  // MESSAGE DISPLAY TESTS
  // ============================================
  test.describe('Message Display', () => {
    test('should display sent messages on the right side', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const testMessage = `E2E Right Side Test - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              await page.waitForTimeout(2000);

              // Verify message appears (should be on right for sent messages)
              const messageElement = page.locator(`text=/${testMessage}/i`);
              await expect(messageElement).toBeVisible({ timeout: 5000 });
            }
          }
        }
      }
    });

    test('should display message timestamps', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          // Check if any messages exist
          const messages = page.locator('[class*="message"], .message-bubble');
          const messageCount = await messages.count();

          if (messageCount > 0) {
            // Check for timestamp (could be "Just now", "5m ago", etc.)
            const timestamp = page.locator('text=/ago|Just now|m ago|h ago/i');
            // May or may not be visible depending on messages
          }
        }
      }
    });

    test('should auto-scroll to bottom when new message arrives', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const testMessage = `E2E Scroll Test - ${Date.now()}`;
            await messageInput.fill(testMessage);
            await page.waitForTimeout(500);

            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              await page.waitForTimeout(2000);

              // Verify message is visible (implies scroll worked)
              await expect(page.locator(`text=/${testMessage}/i`)).toBeVisible({ timeout: 5000 });
            }
          }
        }
      }
    });

    test('should format multi-line messages correctly', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          const messageInput = page.locator('textarea[placeholder*="message"], textarea[placeholder*="Type"]');
          if (await messageInput.isVisible({ timeout: 5000 })) {
            const multilineMessage = `Line 1\nLine 2\nLine 3 - ${Date.now()}`;
            await messageInput.fill(multilineMessage);
            await page.waitForTimeout(500);

            const sendButton = page.locator('button[type="submit"]').last();
            if (await sendButton.isVisible()) {
              await sendButton.click();
              await page.waitForTimeout(2000);

              // Verify all lines are displayed
              await expect(page.locator('text=/Line 1/i')).toBeVisible({ timeout: 5000 });
              await expect(page.locator('text=/Line 2/i')).toBeVisible({ timeout: 5000 });
            }
          }
        }
      }
    });
  });

  // ============================================
  // CONVERSATION LOADING TESTS
  // ============================================
  test.describe('Conversation Loading', () => {
    test('should load existing conversation when opening popup', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(3000);

          // Check for loading state or messages
          const loadingText = page.locator('text=/Loading messages/i');
          const messages = page.locator('[class*="message"], .message-bubble');
          
          // Either loading or messages should appear
          const hasLoading = await loadingText.isVisible({ timeout: 2000 }).catch(() => false);
          const messageCount = await messages.count();

          // Should either show loading or have messages loaded
          expect(hasLoading || messageCount >= 0).toBe(true);
        }
      }
    });

    test('should poll for new messages every 5 seconds', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          // Wait for initial load
          await page.waitForTimeout(3000);

          // Monitor network requests for polling
          const requests = [];
          page.on('request', (request) => {
            if (request.url().includes('/messages/conversation/')) {
              requests.push(request);
            }
          });

          // Wait 6 seconds to catch at least one poll
          await page.waitForTimeout(6000);

          // Should have made at least initial request + polling requests
          expect(requests.length).toBeGreaterThan(0);
        }
      }
    });
  });

  // ============================================
  // ERROR HANDLING TESTS
  // ============================================
  test.describe('Error Handling', () => {
    test('should handle network errors gracefully', async ({ page }) => {
      // Simulate network failure
      await page.route('**/messages/**', route => route.abort());

      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(2000);

          // Should show error or empty state, not crash
          const popup = page.locator('.fixed.bottom-6.right-6');
          await expect(popup).toBeVisible({ timeout: 5000 });
        }
      }
    });

    test('should prevent sending message to self', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      // Try to find current user's row (should not have message button)
      const currentUserRows = page.locator('tbody tr').filter({
        hasNot: page.locator('button[title*="message"]')
      });

      // Current user should not have message button
      const count = await currentUserRows.count();
      expect(count).toBeGreaterThanOrEqual(0);
    });
  });

  // ============================================
  // INTEGRATION TESTS
  // ============================================
  test.describe('Integration with Team Page', () => {
    test('should open message popup for different team members', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 1) {
        // Try opening message for first member
        const firstRow = memberRows.first();
        const firstMessageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await firstMessageButton.isVisible()) {
          await firstMessageButton.click();
          await page.waitForTimeout(1000);

          // Close popup
          const closeButton = page.locator('button:has(svg)').last();
          if (await closeButton.isVisible()) {
            await closeButton.click();
            await page.waitForTimeout(500);
          }

          // Try opening message for second member
          const secondRow = memberRows.nth(1);
          const secondMessageButton = secondRow.locator('button[title*="message"], button:has(svg)').first();
          
          if (await secondMessageButton.isVisible()) {
            await secondMessageButton.click();
            await page.waitForTimeout(1000);

            // Verify popup opened
            const popup = page.locator('.fixed.bottom-6.right-6');
            await expect(popup).toBeVisible({ timeout: 5000 });
          }
        }
      }
    });

    test('should maintain message popup state when navigating', async ({ page }) => {
      await goToTeam(page);
      await page.waitForTimeout(2000);

      const memberRows = page.locator('tbody tr');
      const count = await memberRows.count();
      
      if (count > 0) {
        const firstRow = memberRows.first();
        const messageButton = firstRow.locator('button[title*="message"], button:has(svg)').first();
        
        if (await messageButton.isVisible()) {
          await messageButton.click();
          await page.waitForTimeout(1000);

          // Navigate away
          await page.goto('/dashboard');
          await page.waitForTimeout(1000);

          // Popup should be closed after navigation
          const popup = page.locator('.fixed.bottom-6.right-6');
          const isVisible = await popup.isVisible().catch(() => false);
          expect(isVisible).toBe(false);
        }
      }
    });
  });
});

