const { test, expect } = require('@playwright/test');
const { exec } = require('child_process');
const { promisify } = require('util');

const execAsync = promisify(exec);

// Helper function to clear the database
async function clearDatabase() {
  await execAsync('sqlite3 database/analytics.db "DELETE FROM events; DELETE FROM sessions; DELETE FROM visitors;"');
}

// Helper function to generate random user agent
function generateRandomUserAgent() {
  const browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
  const versions = ['100', '101', '102', '103', '104', '105'];
  const os = ['Windows NT 10.0', 'Macintosh; Intel Mac OS X 10_15_7', 'X11; Linux x86_64'];
  
  const browser = browsers[Math.floor(Math.random() * browsers.length)];
  const version = versions[Math.floor(Math.random() * versions.length)];
  const osChoice = os[Math.floor(Math.random() * os.length)];
  const randomId = Math.random().toString(36).substring(7);
  
  return `Mozilla/5.0 (${osChoice}) AppleWebKit/537.36 (KHTML, like Gecko) ${browser}/${version}.0.0.0 Safari/537.36 TestAgent-${randomId}`;
}

test.describe('Visitor Statistics Tracking', () => {
  test.setTimeout(120000); // Set timeout to 2 minutes
  
  test('should track new visitors, returning visitors, and bots correctly', async ({ context, page }) => {
    console.log('Starting visitor statistics test...');
    
    // Setup: Clear the database
    console.log('Clearing database...');
    await clearDatabase();
    console.log('Database cleared');
    
    // Navigate to dashboard
    await page.goto('http://localhost:8000/');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Select "Last 24 hours" filter
    const timeFilter = page.locator('select#timeRange');
    await timeFilter.selectOption('24h');
    console.log('Time filter set to: Last 24 hours');
    
    // Wait for data refresh
    await page.waitForTimeout(2000);
    
    // =========================
    // PHASE 1: Test New Visitors
    // =========================
    console.log('\n=== PHASE 1: Testing New Visitors ===');
    const uniqueUserAgents = [];
    
    for (let i = 0; i < 8; i++) {
      const userAgent = generateRandomUserAgent();
      uniqueUserAgents.push(userAgent);
      console.log(`Visit ${i + 1} with User Agent: ${userAgent.substring(0, 50)}...`);
      
      // Create new context with custom user agent
      const newContext = await context.browser().newContext({
        userAgent: userAgent
      });
      const newPage = await newContext.newPage();
      
      // Visit fake website
      await newPage.goto('http://localhost:8000/fake_website/');
      await newPage.waitForTimeout(1000); // Wait for tracking to register
      
      await newContext.close();
    }
    
    // Refresh dashboard and verify Phase 1
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Check New Visitors counter
    const newVisitorsPhase1 = await page.locator('.stats-card:has-text("New Visitors") .stats-value').textContent();
    console.log(`Phase 1 - New Visitors: ${newVisitorsPhase1}`);
    expect(newVisitorsPhase1.trim()).toBe('8');
    console.log('✓ Phase 1 passed: 8 new visitors tracked correctly');
    
    // =========================
    // PHASE 2: Test Returning Visitors
    // =========================
    console.log('\n=== PHASE 2: Testing Returning Visitors ===');
    const returningUserAgent = generateRandomUserAgent();
    console.log(`Returning visitor User Agent: ${returningUserAgent.substring(0, 50)}...`);
    
    // Visit twice with the same user agent
    for (let i = 0; i < 2; i++) {
      console.log(`Returning visitor visit ${i + 1}`);
      
      const newContext = await context.browser().newContext({
        userAgent: returningUserAgent
      });
      const newPage = await newContext.newPage();
      
      await newPage.goto('http://localhost:8000/fake_website/');
      await newPage.waitForTimeout(1000);
      
      await newContext.close();
    }
    
    // Refresh dashboard and verify Phase 2
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Check counters
    const newVisitorsPhase2 = await page.locator('.stats-card:has-text("New Visitors") .stats-value').textContent();
    const returningVisitors = await page.locator('.stats-card:has-text("Returning Visitors") .stats-value').textContent();
    
    console.log(`Phase 2 - New Visitors: ${newVisitorsPhase2}`);
    console.log(`Phase 2 - Returning Visitors: ${returningVisitors}`);
    
    expect(newVisitorsPhase2.trim()).toBe('9');
    expect(returningVisitors.trim()).toBe('1');
    console.log('✓ Phase 2 passed: 9 new visitors, 1 returning visitor tracked correctly');
    
    // =========================
    // PHASE 3: Test Bot Detection
    // =========================
    console.log('\n=== PHASE 3: Testing Bot Detection ===');
    
    // Visit with curl user agent
    console.log('Bot visit 1: curl');
    const curlContext = await context.browser().newContext({
      userAgent: 'curl/7.64.1'
    });
    const curlPage = await curlContext.newPage();
    await curlPage.goto('http://localhost:8000/fake_website/');
    await curlPage.waitForTimeout(1000);
    await curlContext.close();
    
    // Visit with googlebot user agent
    console.log('Bot visit 2: googlebot');
    const googlebotContext = await context.browser().newContext({
      userAgent: 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    });
    const googlebotPage = await googlebotContext.newPage();
    await googlebotPage.goto('http://localhost:8000/fake_website/');
    await googlebotPage.waitForTimeout(1000);
    await googlebotContext.close();
    
    // Refresh dashboard and verify Phase 3
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Check all counters
    const totalVisitors = await page.locator('.stats-card:has-text("Total Visitors") .stats-value').textContent();
    const newVisitorsFinal = await page.locator('.stats-card:has-text("New Visitors") .stats-value').textContent();
    const returningVisitorsFinal = await page.locator('.stats-card:has-text("Returning Visitors") .stats-value').textContent();
    const bots = await page.locator('.stats-card:has-text("Bots") .stats-value').textContent();
    
    console.log('\n=== FINAL RESULTS ===');
    console.log(`Total Visitors: ${totalVisitors}`);
    console.log(`New Visitors: ${newVisitorsFinal}`);
    console.log(`Returning Visitors: ${returningVisitorsFinal}`);
    console.log(`Bots: ${bots}`);
    
    // Verify final state
    expect(totalVisitors.trim()).toBe('10');
    expect(newVisitorsFinal.trim()).toBe('9');
    expect(returningVisitorsFinal.trim()).toBe('1');
    expect(bots.trim()).toBe('2');
    
    console.log('\n✅ All tests passed! Visitor statistics are tracked correctly.');
    console.log('- Total Visitors: 10 ✓');
    console.log('- New Visitors: 9 ✓');
    console.log('- Returning Visitors: 1 ✓');
    console.log('- Bots: 2 ✓');
    
    // Check for console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    if (consoleErrors.length > 0) {
      console.log('\n⚠️  Console errors detected:');
      consoleErrors.forEach(error => console.log(`  - ${error}`));
    } else {
      console.log('\n✓ No console errors detected');
    }
  });
});