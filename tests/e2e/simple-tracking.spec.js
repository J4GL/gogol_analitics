const { test, expect } = require('@playwright/test');

test.describe('Simple Tracking Test', () => {
  
  test('should track basic pageview and show in API', async ({ page }) => {
    console.log('🧪 Starting simple tracking test...');

    // Monitor API requests
    const apiRequests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        console.log(`📡 API Request: ${request.method()} ${request.url()}`);
        const postData = request.postData();
        if (postData) {
          try {
            const data = JSON.parse(postData);
            console.log(`📝 Tracking data:`, {
              visitor_id: data.visitor_id?.substring(0, 8) + '...',
              event_type: data.event_type,
              page: data.page,
              os: data.os,
              browser: data.browser
            });
            apiRequests.push(data);
          } catch (e) {
            console.log('❌ Failed to parse tracking data:', postData);
          }
        }
      }
    });

    // Monitor API responses
    page.on('response', response => {
      if (response.url().includes('/api/collect')) {
        console.log(`✅ API Response: ${response.status()} ${response.statusText()}`);
      }
    });

    // Visit test website
    console.log('🌐 Visiting test website...');
    await page.goto('/test/');
    
    // Wait for tracking to complete
    await page.waitForTimeout(2000);
    
    // Verify tracking request was sent
    console.log(`📊 Total tracking requests: ${apiRequests.length}`);
    expect(apiRequests.length).toBeGreaterThan(0);
    
    const firstRequest = apiRequests[0];
    expect(firstRequest.event_type).toBe('pageview');
    expect(firstRequest.visitor_id).toBeDefined();
    expect(firstRequest.page).toContain('/test/');
    
    console.log('✅ Basic tracking working!');
  });

  test('should navigate between pages and track each', async ({ page }) => {
    console.log('🧪 Testing multi-page navigation...');

    const apiRequests = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        const postData = request.postData();
        if (postData) {
          try {
            const data = JSON.parse(postData);
            if (data.event_type === 'pageview') {
              apiRequests.push(data);
              console.log(`📄 Pageview tracked: ${data.page}`);
            }
          } catch (e) {
            // Ignore parsing errors
          }
        }
      }
    });

    // Visit multiple pages
    await page.goto('/test/');
    await page.waitForTimeout(1000);

    await page.goto('/test/about.html');
    await page.waitForTimeout(1000);

    await page.goto('/test/products.html');
    await page.waitForTimeout(1000);

    console.log(`📊 Total pageviews tracked: ${apiRequests.length}`);
    expect(apiRequests.length).toBeGreaterThanOrEqual(3);
    
    // Verify different pages were tracked
    const pages = apiRequests.map(req => req.page);
    expect(pages.some(p => p.includes('test/'))).toBeTruthy();
    expect(pages.some(p => p.includes('about.html'))).toBeTruthy();
    expect(pages.some(p => p.includes('products.html'))).toBeTruthy();
    
    console.log('✅ Multi-page tracking working!');
  });

  test('should load dashboard without errors', async ({ page }) => {
    console.log('🧪 Testing dashboard load...');

    await page.goto('/');
    
    // Check basic dashboard elements load
    await expect(page.locator('h1')).toContainText('Gogol Analytics');
    await expect(page.locator('.tab-button:has-text("Traffic")')).toBeVisible();
    await expect(page.locator('.tab-button:has-text("Settings")')).toBeVisible();
    
    // Check stats cards exist
    await expect(page.locator('#total-visitors')).toBeVisible();
    await expect(page.locator('#new-visitors')).toBeVisible();
    await expect(page.locator('#returning-visitors')).toBeVisible();
    await expect(page.locator('#bots')).toBeVisible();
    
    console.log('✅ Dashboard loads correctly!');
  });

  test('should show tracking code in settings', async ({ page }) => {
    console.log('🧪 Testing settings tab...');

    await page.goto('/');
    
    // Click settings tab
    await page.click('.tab-button:has-text("Settings")');
    
    // Check tracking code is visible
    await expect(page.locator('#trackingCode')).toBeVisible();
    
    // Check copy button works
    await page.click('#copyCode');
    await expect(page.locator('#copyCode')).toContainText('Copied!');
    
    // Wait for button to reset
    await page.waitForTimeout(2500);
    await expect(page.locator('#copyCode')).toContainText('Copy Code');
    
    console.log('✅ Settings tab working!');
  });

  test('should track visitor information correctly', async ({ page }) => {
    console.log('🧪 Testing visitor information detection...');

    const trackingData = [];
    page.on('request', request => {
      if (request.url().includes('/api/collect')) {
        const postData = request.postData();
        if (postData) {
          try {
            const data = JSON.parse(postData);
            trackingData.push(data);
          } catch (e) {
            // Ignore
          }
        }
      }
    });

    await page.goto('/test/');
    await page.waitForTimeout(1000);

    expect(trackingData.length).toBeGreaterThan(0);
    
    const data = trackingData[0];
    expect(data.visitor_id).toBeDefined();
    expect(data.timestamp).toBeDefined();
    expect(data.os).toBeDefined();
    expect(data.browser).toBeDefined();
    expect(data.device_type).toMatch(/^(PC|MOBILE)$/);
    expect(data.resolution).toMatch(/^\d+x\d+$/);
    
    console.log('📊 Visitor info detected:', {
      os: data.os,
      browser: data.browser,
      device_type: data.device_type,
      resolution: data.resolution,
      country: data.country
    });
    
    console.log('✅ Visitor detection working!');
  });
});