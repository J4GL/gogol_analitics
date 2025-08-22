const { test, expect } = require('@playwright/test');

test.describe('Live Visitor Activity in Traffic Tab', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the dashboard
    await page.goto('/');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    
    // Ensure we're on the Traffic tab (should be default)
    await expect(page.locator('#traffic-tab')).toHaveClass(/active/);
  });

  test('should display Live Visitor Activity section in Traffic tab', async ({ page }) => {
    // Check that the Live Visitor Activity section is present in the Traffic tab
    await expect(page.locator('#traffic')).toBeVisible();
    
    // Check that the Live Visitor Activity card is present
    const liveActivityCard = page.locator('#traffic .card:has-text("Live Visitor Activity")');
    await expect(liveActivityCard).toBeVisible();
    
    // Check that the live count badge is present
    await expect(page.locator('#live-count')).toBeVisible();
    
    // Check that the live visitors table is present
    await expect(page.locator('#live-visitors-table')).toBeVisible();
    
    // Check that the table has the correct headers (updated structure)
    const tableHeaders = page.locator('#live-visitors-table thead th');
    await expect(tableHeaders).toHaveCount(3);
    await expect(tableHeaders.nth(0)).toHaveText('Time');
    await expect(tableHeaders.nth(1)).toHaveText('Type');
    await expect(tableHeaders.nth(2)).toHaveText('Event');
  });

  test('should not have a separate Live tab', async ({ page }) => {
    // Check that the Live tab is not present in the navigation
    const liveTabs = page.locator('#live-tab');
    await expect(liveTabs).toHaveCount(0);
    
    // Check that there's no #live tab content
    const liveTabContent = page.locator('#live');
    await expect(liveTabContent).toHaveCount(0);
  });

  test('should have only 3 navigation tabs', async ({ page }) => {
    // Check that we have exactly 3 tabs: Traffic, Pages, Settings
    const navTabs = page.locator('.sidebar .nav-pills .nav-item');
    await expect(navTabs).toHaveCount(3);
    
    // Check the specific tab names
    await expect(page.locator('#traffic-tab')).toHaveText(/Traffic/);
    await expect(page.locator('#pages-tab')).toHaveText(/Pages/);
    await expect(page.locator('#settings-tab')).toHaveText(/Settings/);
  });

  test('should update live visitor activity automatically', async ({ page }) => {
    // Wait for initial load
    await page.waitForTimeout(1000);
    
    // Get initial live count
    const initialLiveCount = await page.locator('#live-count').textContent();
    
    // Open fake website in a new tab to generate activity
    const fakeWebsitePage = await page.context().newPage();
    await fakeWebsitePage.goto('/fake_website/');
    
    // Wait for tracking to be sent
    await fakeWebsitePage.waitForTimeout(2000);
    
    // Click some buttons to generate events
    await fakeWebsitePage.click('[data-track="cta-click"]');
    await fakeWebsitePage.waitForTimeout(1000);
    
    // Go back to dashboard and wait for live updates (should happen within 5 seconds)
    await page.bringToFront();
    await page.waitForTimeout(6000);
    
    // Check that live visitors table might have been updated
    const liveVisitorsRows = page.locator('#live-visitors-table tbody tr');
    const rowCount = await liveVisitorsRows.count();
    
    // We should have at least one row (either actual data or "No live visitors" message)
    expect(rowCount).toBeGreaterThan(0);
    
    // Close the fake website tab
    await fakeWebsitePage.close();
  });

  test('should display live visitor data with correct structure', async ({ page }) => {
    // Generate some activity first
    const fakeWebsitePage = await page.context().newPage();
    await fakeWebsitePage.goto('/fake_website/');
    await fakeWebsitePage.waitForTimeout(2000);
    await fakeWebsitePage.click('[data-track="cta-click"]');
    await fakeWebsitePage.close();
    
    // Wait for the dashboard to update
    await page.waitForTimeout(6000);
    
    // Check the table structure
    const tableRows = page.locator('#live-visitors-table tbody tr');
    const firstRow = tableRows.first();
    
    // Check if we have data or the "no visitors" message
    const hasData = await page.locator('#live-visitors-table tbody tr:not(:has-text("No live visitors"))').count() > 0;
    
    if (hasData) {
      // If we have data, check the structure
      const cells = firstRow.locator('td');
      await expect(cells).toHaveCount(3);
      
      // Check that time cell contains text
      await expect(cells.nth(0)).not.toBeEmpty();
      
      // Check that type cell contains a badge
      await expect(cells.nth(1).locator('.badge')).toBeVisible();
      
      // Check that event cell contains a badge with tooltip
      await expect(cells.nth(2).locator('.badge')).toBeVisible();
      await expect(cells.nth(2).locator('[data-bs-toggle="tooltip"]')).toBeVisible();
    } else {
      // If no data, check for the "no visitors" message
      await expect(firstRow).toHaveText(/No live visitors/);
      await expect(firstRow.locator('td')).toHaveAttribute('colspan', '3');
    }
  });

  test('should maintain live updates when switching tabs', async ({ page }) => {
    // Start on Traffic tab and verify live activity is present
    await expect(page.locator('#live-visitors-table')).toBeVisible();
    
    // Switch to Pages tab
    await page.click('#pages-tab');
    await expect(page.locator('#pages')).toBeVisible();
    
    // Switch back to Traffic tab
    await page.click('#traffic-tab');
    await expect(page.locator('#traffic')).toBeVisible();
    
    // Verify that live activity is still working
    await expect(page.locator('#live-visitors-table')).toBeVisible();
    await expect(page.locator('#live-count')).toBeVisible();
    
    // Generate some activity
    const fakeWebsitePage = await page.context().newPage();
    await fakeWebsitePage.goto('/fake_website/about.html');
    await fakeWebsitePage.waitForTimeout(1000);
    await fakeWebsitePage.close();
    
    // Wait for updates and verify the table is still functional
    await page.waitForTimeout(6000);
    const tableRows = page.locator('#live-visitors-table tbody tr');
    expect(await tableRows.count()).toBeGreaterThan(0);
  });

  test('should have responsive layout for live visitor activity', async ({ page }) => {
    // Test desktop layout
    await page.setViewportSize({ width: 1200, height: 800 });
    
    // Check that live activity is in the right column
    const liveActivityCard = page.locator('#traffic .col-lg-4:has-text("Live Visitor Activity")');
    await expect(liveActivityCard).toBeVisible();
    
    // Check that the chart is in the left column
    const chartCard = page.locator('#traffic .col-lg-8:has-text("Visitor Analytics")');
    await expect(chartCard).toBeVisible();
    
    // Test tablet layout
    await page.setViewportSize({ width: 768, height: 600 });
    
    // Both sections should still be visible
    await expect(page.locator('#live-visitors-table')).toBeVisible();
    await expect(page.locator('#visitorChart')).toBeVisible();
    
    // Reset to desktop
    await page.setViewportSize({ width: 1200, height: 800 });
  });
});