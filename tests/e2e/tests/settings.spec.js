import { test, expect } from '@playwright/test';

test.describe('Settings Tab', () => {
  test('should display script snippet', async ({ page }) => {
    await page.goto('/');
    
    // Navigate to Settings tab
    await page.getByRole('tab', { name: 'Settings' }).click();
    
    // Check for script snippet
    await expect(page.getByText('Tracking Script')).toBeVisible();
    await expect(page.locator('.code-snippet')).toBeVisible();
    
    // Verify snippet contains necessary elements
    const snippetText = await page.locator('.code-snippet').textContent();
    expect(snippetText).toContain('track.js');
    expect(snippetText).toContain('initAnalytics');
    expect(snippetText).toContain('/api/events');
  });

  test('should copy script to clipboard', async ({ page, context }) => {
    // Grant clipboard permissions
    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    
    await page.goto('/');
    
    // Navigate to Settings tab
    await page.getByRole('tab', { name: 'Settings' }).click();
    
    // Click copy button
    await page.getByRole('button', { name: /Copy to Clipboard/i }).click();
    
    // Verify button shows "Copied!"
    await expect(page.getByRole('button', { name: /Copied!/i })).toBeVisible();
    
    // Verify clipboard content
    const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
    expect(clipboardText).toContain('track.js');
    expect(clipboardText).toContain('initAnalytics');
  });

  test('should display data collection information', async ({ page }) => {
    await page.goto('/');
    
    // Navigate to Settings tab
    await page.getByRole('tab', { name: 'Settings' }).click();
    
    // Check for data collection section
    await expect(page.getByText('What data is collected?')).toBeVisible();
    await expect(page.getByText(/Timestamp/i)).toBeVisible();
    await expect(page.getByText(/Page URL/i)).toBeVisible();
    await expect(page.getByText(/Browser/i)).toBeVisible();
  });
});
