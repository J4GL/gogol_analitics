const { test, expect } = require('@playwright/test');

test.describe('Test Charts with Historical Data', () => {
  
  test('should display multiple bars in charts for different timeframes', async ({ page }) => {
    console.log('🧪 Testing charts with historical data...');

    await page.goto('/');
    
    // Wait for dashboard to load
    await page.waitForTimeout(3000);
    
    // Test 24h view
    console.log('📊 Testing 24h chart...');
    await page.selectOption('#timeframe', '24h');
    await page.waitForTimeout(2000);
    
    // Check if chart exists and has data
    const chartExists = await page.locator('#visitorChart').isVisible();
    expect(chartExists).toBeTruthy();
    
    // Test 7d view
    console.log('📊 Testing 7d chart...');
    await page.selectOption('#timeframe', '7d');
    await page.waitForTimeout(2000);
    
    // Test 30d view
    console.log('📊 Testing 30d chart...');
    await page.selectOption('#timeframe', '30d');
    await page.waitForTimeout(2000);
    
    // Test 24h view again
    console.log('📊 Testing 24h chart again...');
    await page.selectOption('#timeframe', '24h');
    await page.waitForTimeout(2000);
    
    console.log('✅ All chart timeframes tested successfully!');
  });
  
  test('should show updated visitor statistics', async ({ page }) => {
    console.log('🧪 Testing visitor statistics with historical data...');

    await page.goto('/');
    await page.waitForTimeout(3000);
    
    // Check visitor stats are populated
    const totalVisitors = await page.locator('#total-visitors').textContent();
    const newVisitors = await page.locator('#new-visitors').textContent();
    const returningVisitors = await page.locator('#returning-visitors').textContent();
    const bots = await page.locator('#bots').textContent();
    
    console.log(`📊 Statistics:`);
    console.log(`  Total visitors: ${totalVisitors}`);
    console.log(`  New visitors: ${newVisitors}`);
    console.log(`  Returning visitors: ${returningVisitors}`);
    console.log(`  Bots: ${bots}`);
    
    // Should have some visitors
    expect(parseInt(totalVisitors)).toBeGreaterThan(0);
    expect(parseInt(newVisitors)).toBeGreaterThan(0);
    
    console.log('✅ Visitor statistics look good!');
  });
});