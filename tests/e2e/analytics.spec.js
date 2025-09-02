const { test, expect } = require('@playwright/test');

test.describe('Gogol Analytics E2E Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Clear localStorage before each test
    await page.goto('http://localhost:8001');
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  });

  test('should load tracking script on test website', async ({ page }) => {
    await page.goto('http://localhost:8001');
    
    // Check if the tracking script is loaded
    const trackerLoaded = await page.evaluate(() => {
      return typeof window.gogol !== 'undefined';
    });
    
    expect(trackerLoaded).toBeTruthy();
  });

  test('should track pageview on page load', async ({ page }) => {
    // Set up request interception
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        requests.push(request);
      }
    });
    
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(1000); // Wait for tracking to fire
    
    // Check if pageview was tracked
    expect(requests.length).toBeGreaterThan(0);
    
    const trackingRequest = requests[0];
    expect(trackingRequest.method()).toBe('POST');
    
    const postData = JSON.parse(trackingRequest.postData());
    expect(postData.event_type).toBe('pageview');
    expect(postData.page).toContain('localhost:8001');
  });

  test('should track navigation between pages', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        requests.push(request);
      }
    });
    
    // Navigate to home page
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(500);
    
    // Navigate to about page
    await page.click('a[href="about.html"]');
    await page.waitForTimeout(500);
    
    // Navigate to products page
    await page.click('a[href="products.html"]');
    await page.waitForTimeout(500);
    
    // Should have tracked at least 3 pageviews
    expect(requests.length).toBeGreaterThanOrEqual(3);
  });

  test('should track custom events on product page', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    await page.goto('http://localhost:8001/products.html');
    await page.waitForTimeout(500);
    
    // Click on a product button
    await page.click('button:has-text("Learn More"):first');
    await page.waitForTimeout(500);
    
    // Find the custom event
    const customEvent = requests.find(r => r.event_type === 'product_click');
    expect(customEvent).toBeDefined();
    expect(customEvent.product).toBe('analytics');
  });

  test('should track form submission on contact page', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    await page.goto('http://localhost:8001/contact.html');
    await page.waitForTimeout(500);
    
    // Fill and submit form
    await page.fill('#name', 'Test User');
    await page.fill('#email', 'test@example.com');
    await page.fill('#message', 'This is a test message');
    
    // Handle alert dialog
    page.on('dialog', dialog => dialog.accept());
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(500);
    
    // Find the form submission event
    const formEvent = requests.find(r => r.event_type === 'form_submit');
    expect(formEvent).toBeDefined();
    expect(formEvent.form).toBe('contact');
  });

  test('should generate consistent visitor ID', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    // First visit
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(500);
    
    // Navigate to another page
    await page.click('a[href="about.html"]');
    await page.waitForTimeout(500);
    
    // Check visitor IDs are consistent
    expect(requests.length).toBeGreaterThanOrEqual(2);
    const visitorIds = requests.map(r => r.visitor_id);
    expect(new Set(visitorIds).size).toBe(1); // All should be the same
  });

  test('should detect browser, OS, and device type', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(500);
    
    expect(requests.length).toBeGreaterThan(0);
    const data = requests[0];
    
    // Check detected properties
    expect(data.os).toBeDefined();
    expect(data.browser).toBeDefined();
    expect(data.device_type).toMatch(/^(PC|MOBILE)$/);
    expect(data.resolution).toMatch(/^\d+x\d+$/);
    expect(data.timezone).toBeDefined();
  });

  test('should load analytics dashboard', async ({ page }) => {
    await page.goto('http://localhost:8000');
    
    // Check if dashboard loads
    await expect(page.locator('h1')).toContainText('Gogol Analytics');
    
    // Check if tabs are present
    await expect(page.locator('.tab-button:has-text("Traffic")')).toBeVisible();
    await expect(page.locator('.tab-button:has-text("Settings")')).toBeVisible();
    
    // Check if stats cards are present
    await expect(page.locator('#total-visitors')).toBeVisible();
    await expect(page.locator('#new-visitors')).toBeVisible();
    await expect(page.locator('#returning-visitors')).toBeVisible();
    await expect(page.locator('#bots')).toBeVisible();
  });

  test('should switch between dashboard tabs', async ({ page }) => {
    await page.goto('http://localhost:8000');
    
    // Initially Traffic tab should be active
    await expect(page.locator('#traffic')).toBeVisible();
    await expect(page.locator('#settings')).not.toBeVisible();
    
    // Click Settings tab
    await page.click('.tab-button:has-text("Settings")');
    
    // Settings tab should now be visible
    await expect(page.locator('#settings')).toBeVisible();
    await expect(page.locator('#traffic')).not.toBeVisible();
    
    // Check if tracking code is present
    await expect(page.locator('#trackingCode')).toBeVisible();
  });

  test('should copy tracking code to clipboard', async ({ page, context }) => {
    // Grant clipboard permissions
    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    
    await page.goto('http://localhost:8000');
    await page.click('.tab-button:has-text("Settings")');
    
    // Click copy button
    await page.click('#copyCode');
    
    // Check if button text changes
    await expect(page.locator('#copyCode')).toContainText('Copied!');
    
    // Wait for button to reset
    await page.waitForTimeout(2500);
    await expect(page.locator('#copyCode')).toContainText('Copy Code');
  });

  test('should show live visitor data with tooltip', async ({ page }) => {
    // First generate some traffic
    const testPage = await page.context().newPage();
    await testPage.goto('http://localhost:8001');
    await testPage.waitForTimeout(500);
    await testPage.goto('http://localhost:8001/about.html');
    await testPage.waitForTimeout(500);
    
    // Now check dashboard
    await page.goto('http://localhost:8000');
    await page.waitForTimeout(2000); // Wait for data to load
    
    // Check if live table has data
    const rows = await page.locator('#liveTableBody tr').count();
    expect(rows).toBeGreaterThan(0);
    
    // Hover over first row to show tooltip
    await page.hover('#liveTableBody tr:first-child');
    await page.waitForTimeout(100);
    
    // Check if tooltip is visible
    await expect(page.locator('#tooltip.show')).toBeVisible();
    
    // Check tooltip contains expected data
    const tooltipText = await page.locator('#tooltip').textContent();
    expect(tooltipText).toContain('Visitor ID:');
    expect(tooltipText).toContain('Page:');
    expect(tooltipText).toContain('Browser:');
    
    await testPage.close();
  });

  test('should update chart when timeframe changes', async ({ page }) => {
    await page.goto('http://localhost:8000');
    
    // Initial load with 24h
    await page.waitForTimeout(1000);
    
    // Change timeframe to 7 days
    await page.selectOption('#timeframe', '7d');
    await page.waitForTimeout(1000);
    
    // Chart should still be visible
    await expect(page.locator('#visitorChart')).toBeVisible();
    
    // Change to 24 hours  
    await page.selectOption('#timeframe', '24h');
    await page.waitForTimeout(1000);
    
    // Chart should still be visible
    await expect(page.locator('#visitorChart')).toBeVisible();
  });

  test('should track page visibility changes', async ({ page }) => {
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(500);
    
    // Simulate page becoming hidden (switching tabs)
    await page.evaluate(() => {
      document.dispatchEvent(new Event('visibilitychange'));
      Object.defineProperty(document, 'visibilityState', {
        value: 'hidden',
        writable: true
      });
      document.dispatchEvent(new Event('visibilitychange'));
    });
    
    await page.waitForTimeout(500);
    
    // Check if visibility event was tracked
    const visibilityEvent = requests.find(r => 
      r.event_type === 'page_hidden' || r.event_type === 'page_visible'
    );
    expect(visibilityEvent).toBeDefined();
  });
});

test.describe('Bot Detection', () => {
  test('should detect bot user agents', async ({ page }) => {
    // Override user agent to simulate a bot
    await page.setExtraHTTPHeaders({
      'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    });
    
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect.php')) {
        const postData = request.postData();
        if (postData) {
          requests.push(JSON.parse(postData));
        }
      }
    });
    
    await page.goto('http://localhost:8001');
    await page.waitForTimeout(500);
    
    expect(requests.length).toBeGreaterThan(0);
    const data = requests[0];
    
    // Bot detection should be triggered
    expect(data.is_bot).toBeTruthy();
  });
});