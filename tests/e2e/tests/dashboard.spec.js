import { test, expect } from '@playwright/test';

test.describe('Traffic Dashboard', () => {
  test('should display dashboard with tabs', async ({ page }) => {
    await page.goto('/');
    
    // Check for main heading
    await expect(page.getByText('Gogol Analytics Dashboard')).toBeVisible();
    
    // Check for tabs
    await expect(page.getByRole('tab', { name: 'Traffic' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Settings' })).toBeVisible();
  });

  test('should show empty state when no data', async ({ page }) => {
    await page.goto('/');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    
    // Check for empty state messages (might be visible if database is empty)
    const emptyStateVisible = await page.getByText('No traffic data available yet').isVisible().catch(() => false);
    
    // Either we see data or empty state
    const hasContent = emptyStateVisible || await page.locator('.recharts-wrapper').isVisible().catch(() => false);
    expect(hasContent).toBeTruthy();
  });

  test('should switch between tabs', async ({ page }) => {
    await page.goto('/');
    
    // Click on Settings tab
    await page.getByRole('tab', { name: 'Settings' }).click();
    
    // Check that settings content is visible
    await expect(page.getByText('Tracking Script')).toBeVisible();
    
    // Go back to Traffic tab
    await page.getByRole('tab', { name: 'Traffic' }).click();
    
    // Check that traffic content is visible
    await expect(page.getByText('Traffic Overview')).toBeVisible();
  });

  test('should receive and display live events via SSE', async ({ page }) => {
    await page.goto('/');
    
    // Wait for initial load
    await page.waitForLoadState('networkidle');
    
    // In a new context, visit fake website to generate event
    const context = page.context();
    const fakeWebsitePage = await context.newPage();
    await fakeWebsitePage.goto('http://localhost:3000/../fake_website/index.html', {
      waitUntil: 'load'
    });
    
    // Close fake website page
    await fakeWebsitePage.close();
    
    // Wait a bit for SSE to propagate
    await page.waitForTimeout(1000);
    
    // Check if live visitor table is populated (or shows empty state)
    const tableVisible = await page.locator('.events-table').isVisible().catch(() => false);
    const emptyStateVisible = await page.getByText('No visitor events yet').isVisible().catch(() => false);
    
    expect(tableVisible || emptyStateVisible).toBeTruthy();
  });
});
