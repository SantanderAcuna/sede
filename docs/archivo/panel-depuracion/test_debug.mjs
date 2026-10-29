// Test diagnóstico: rastrear peticiones HTTP en cada F5

import { chromium } from 'playwright';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  const events = [];

  page.on('request', req => {
    if (req.url().includes('/api/v1/') || req.url().includes('/sanctum/')) {
      events.push({
        type: 'REQ',
        url: req.url().replace('http://127.0.0.1:5190', ''),
        method: req.method(),
        time: Date.now(),
      });
    }
  });

  page.on('response', async resp => {
    const url = resp.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      events.push({
        type: 'RES',
        url: url.replace('http://127.0.0.1:5190', ''),
        status: resp.status(),
        time: Date.now(),
      });
    }
  });

  try {
    // Login
    await page.goto('http://127.0.0.1:5190/admin/acceso');
    await page.waitForLoadState('networkidle');

    await page.locator('input[type="email"]').first().fill('jose.acuna@santamarta.gov.co');
    await page.locator('input[type="password"]').first().fill('85154239');
    await page.locator('button[type="submit"]').first().click();

    await page.waitForURL(/\/admin\/?$/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    console.log('=== TRAS LOGIN ===');
    events.forEach(e => {
      console.log(`  [${e.type}] ${e.method || ''} ${e.status || ''} ${e.url}`);
    });
    events.length = 0;

    // F5 #1
    console.log('\n=== F5 #1 ===');
    const f5_1_start = Date.now();
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    events.forEach(e => {
      const elapsed = e.time - f5_1_start;
      console.log(`  [+${elapsed}ms] [${e.type}] ${e.method || ''} ${e.status || ''} ${e.url}`);
    });
    events.length = 0;

    console.log(`  URL final: ${page.url()}`);

    // F5 #2
    console.log('\n=== F5 #2 ===');
    const f5_2_start = Date.now();
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    events.forEach(e => {
      const elapsed = e.time - f5_2_start;
      console.log(`  [+${elapsed}ms] [${e.type}] ${e.method || ''} ${e.status || ''} ${e.url}`);
    });
    events.length = 0;

    console.log(`  URL final: ${page.url()}`);

  } catch (err) {
    console.error('ERROR:', err.message);
  } finally {
    await browser.close();
  }
}

runTest();