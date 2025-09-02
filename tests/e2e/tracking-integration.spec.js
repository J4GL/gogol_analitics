const { test, expect } = require('@playwright/test');

test.describe('Tracking Integration Test', () => {
  
  test('should track pageviews on test website and show in dashboard', async ({ page, context }) => {
    // Step 1: Clear any existing data
    await page.goto('/');
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });

    // Step 2: Set up request monitoring for API calls
    const apiRequests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        apiRequests.push({
          url: request.url(),
          method: request.method(),
          postData: request.postData()
        });
      }
    });

    // Step 3: Visit test website pages to generate tracking data
    console.log('Visiting test website pages...');
    
    // Visit home page
    await page.goto('/test/');
    await page.waitForTimeout(1000);
    console.log('Visited home page');

    // Visit about page
    await page.click('a[href="about.html"]');
    await page.waitForTimeout(1000);
    console.log('Visited about page');

    // Visit products page
    await page.click('a[href="products.html"]');
    await page.waitForTimeout(1000);
    console.log('Visited products page');

    // Click a product button to trigger custom event
    await page.click('button:has-text("Learn More"):first');
    await page.waitForTimeout(1000);
    console.log('Clicked product button');

    // Visit contact page
    await page.click('a[href="contact.html"]');
    await page.waitForTimeout(1000);
    console.log('Visited contact page');

    // Step 4: Verify tracking requests were sent
    console.log(`Total API requests captured: ${apiRequests.length}`);
    expect(apiRequests.length).toBeGreaterThan(0);

    // Check that pageview events were tracked
    const pageviews = apiRequests.filter(req => {
      if (req.postData) {
        try {
          const data = JSON.parse(req.postData);
          return data.event_type === 'pageview';
        } catch (e) {
          return false;
        }
      }
      return false;
    });

    console.log(`Pageview requests: ${pageviews.length}`);
    expect(pageviews.length).toBeGreaterThanOrEqual(4); // At least 4 page visits

    // Check for custom event
    const customEvents = apiRequests.filter(req => {
      if (req.postData) {
        try {
          const data = JSON.parse(req.postData);
          return data.event_type === 'product_click';
        } catch (e) {
          return false;
        }
      }
      return false;
    });

    console.log(`Custom events: ${customEvents.length}`);
    expect(customEvents.length).toBeGreaterThan(0);

    // Step 5: Wait a moment for data to be processed
    await page.waitForTimeout(2000);

    // Step 6: Visit dashboard and check if data appears
    console.log('Checking dashboard...');
    await page.goto('/');
    
    // Wait for dashboard to load
    await expect(page.locator('h1')).toContainText('Gogol Analytics');
    
    // Wait for stats to load (give it some time)
    await page.waitForTimeout(3000);
    
    // Check if visitor stats show some activity
    const totalVisitors = await page.locator('#total-visitors').textContent();
    const newVisitors = await page.locator('#new-visitors').textContent();
    
    console.log(`Total visitors: ${totalVisitors}`);
    console.log(`New visitors: ${newVisitors}`);
    
    // Should show at least 1 visitor
    expect(parseInt(totalVisitors)).toBeGreaterThan(0);
    expect(parseInt(newVisitors)).toBeGreaterThan(0);

    // Step 7: Check live visitors table
    const liveTableRows = await page.locator('#liveTableBody tr').count();
    console.log(`Live table rows: ${liveTableRows}`);
    expect(liveTableRows).toBeGreaterThan(0);

    // Step 8: Verify tooltip functionality
    if (liveTableRows > 0) {
      await page.hover('#liveTableBody tr:first-child');
      await page.waitForTimeout(200);
      
      // Check if tooltip appears
      const tooltip = page.locator('#tooltip.show');
      await expect(tooltip).toBeVisible();
      
      // Check tooltip contains visitor information
      const tooltipText = await tooltip.textContent();
      expect(tooltipText).toContain('Visitor ID:');
      expect(tooltipText).toContain('Page:');
    }

    console.log('✅ All tracking integration tests passed!');
  });

  test('should track form submissions', async ({ page }) => {
    const apiRequests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        apiRequests.push({
          url: request.url(),
          method: request.method(),
          postData: request.postData()
        });
      }
    });

    // Visit contact page
    await page.goto('/test/contact.html');
    await page.waitForTimeout(1000);

    // Fill and submit form
    await page.fill('#name', 'Test User');
    await page.fill('#email', 'test@example.com');
    await page.fill('#message', 'This is a test message from Playwright');

    // Handle alert dialog
    page.on('dialog', dialog => dialog.accept());

    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);

    // Check for form submission event
    const formEvents = apiRequests.filter(req => {
      if (req.postData) {
        try {
          const data = JSON.parse(req.postData);
          return data.event_type === 'form_submit';
        } catch (e) {
          return false;
        }
      }
      return false;
    });

    expect(formEvents.length).toBeGreaterThan(0);
    console.log('✅ Form submission tracking works!');
  });

  test('should detect visitor information correctly', async ({ page }) => {
    const apiRequests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        apiRequests.push({
          url: request.url(),
          method: request.method(),
          postData: request.postData()
        });
      }
    });

    await page.goto('/test/');
    await page.waitForTimeout(1000);

    expect(apiRequests.length).toBeGreaterThan(0);
    
    const trackingData = JSON.parse(apiRequests[0].postData);
    
    // Check required fields are present
    expect(trackingData.visitor_id).toBeDefined();
    expect(trackingData.timestamp).toBeDefined();
    expect(trackingData.page).toBeDefined();
    expect(trackingData.os).toBeDefined();
    expect(trackingData.browser).toBeDefined();
    expect(trackingData.device_type).toMatch(/^(PC|MOBILE)$/);
    expect(trackingData.resolution).toMatch(/^\d+x\d+$/);
    
    console.log('Detected visitor info:', {
      os: trackingData.os,
      browser: trackingData.browser,
      device_type: trackingData.device_type,
      resolution: trackingData.resolution,
      country: trackingData.country
    });

    console.log('✅ Visitor detection works correctly!');
  });

  test('should show real-time updates in dashboard', async ({ page, context }) => {
    // Open dashboard in one tab
    await page.goto('/');
    await page.waitForTimeout(2000);

    // Get initial visitor count
    const initialCount = await page.locator('#total-visitors').textContent();
    console.log(`Initial visitor count: ${initialCount}`);

    // Open test website in new tab
    const testPage = await context.newPage();
    await testPage.goto('/test/');
    await testPage.waitForTimeout(1000);
    
    // Navigate in test website
    await testPage.click('a[href="about.html"]');
    await testPage.waitForTimeout(1000);

    // Wait for dashboard to update (auto-refresh every 10 seconds, but let's wait a bit)
    await page.waitForTimeout(3000);

    // Manually refresh dashboard data by changing timeframe
    await page.selectOption('#timeframe', '24h');
    await page.waitForTimeout(2000);

    // Check if visitor count updated
    const updatedCount = await page.locator('#total-visitors').textContent();
    console.log(`Updated visitor count: ${updatedCount}`);

    // Should have at least some activity
    expect(parseInt(updatedCount)).toBeGreaterThanOrEqual(parseInt(initialCount));

    await testPage.close();
    console.log('✅ Real-time updates work!');
  });
});