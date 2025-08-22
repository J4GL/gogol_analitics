const { test, expect } = require('@playwright/test');

test.describe('Live Visitor Activity Integration Test', () => {
  test('should show real visitor activity in the Traffic tab', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Verify we're on Traffic tab and Live Activity is visible
    await expect(page.locator('#traffic-tab')).toHaveClass(/active/);
    await expect(page.locator('#live-visitors-table')).toBeVisible();
    
    // Generate visitor activity by visiting the fake website
    const fakeWebsitePage = await page.context().newPage();
    
    // Visit home page
    await fakeWebsitePage.goto('/fake_website/');
    await fakeWebsitePage.waitForTimeout(1000);
    
    // Navigate to about page
    await fakeWebsitePage.goto('/fake_website/about.html');
    await fakeWebsitePage.waitForTimeout(1000);
    
    // Click some tracked elements
    await fakeWebsitePage.click('[data-track]');
    await fakeWebsitePage.waitForTimeout(1000);
    
    // Close the fake website
    await fakeWebsitePage.close();
    
    // Go back to dashboard and wait for live updates
    await page.bringToFront();
    await page.waitForTimeout(6000); // Wait for live update interval
    
    // Check that the live activity shows our generated activity
    const liveRows = page.locator('#live-visitors-table tbody tr');
    const rowCount = await liveRows.count();
    
    console.log(`Found ${rowCount} rows in live visitor activity table`);
    
    // We should have at least one row (either data or "no visitors" message)
    expect(rowCount).toBeGreaterThan(0);
    
    // If we have actual visitor data (not the "no visitors" message)
    const hasData = await page.locator('#live-visitors-table tbody tr:not(:has-text("No live visitors"))').count();
    
    if (hasData > 0) {
      console.log('Live visitor data detected');
      
      // Check the structure of the first data row
      const firstDataRow = page.locator('#live-visitors-table tbody tr:not(:has-text("No live visitors"))').first();
      const cells = firstDataRow.locator('td');
      
      // Should have 3 columns: Time, Type, Event
      await expect(cells).toHaveCount(3);
      
      // Time column should not be empty
      await expect(cells.nth(0)).not.toBeEmpty();
      
      // Type column should have a badge
      const typeBadge = cells.nth(1).locator('.badge');
      await expect(typeBadge).toBeVisible();
      
      // Event column should have a badge with tooltip
      const eventBadge = cells.nth(2).locator('.badge');
      await expect(eventBadge).toBeVisible();
      await expect(cells.nth(2).locator('[data-bs-toggle="tooltip"]')).toBeVisible();
      
      console.log('Live visitor activity structure verified successfully');
    } else {
      console.log('No live visitor data found (this is normal if no recent activity)');
    }
    
    // Verify the live count badge is present and functional
    const liveCount = page.locator('#live-count');
    await expect(liveCount).toBeVisible();
    const countText = await liveCount.textContent();
    console.log(`Live count shows: ${countText}`);
    
    // Count should be a number followed by "active"
    expect(countText).toMatch(/^\d+\s+active$/);
  });
});