// Diagnóstico profundo: por qué F5 #2 devuelve 401

import { chromium } from 'playwright';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('response', async resp => {
    const url = resp.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      const req = resp.request();
      const reqHeaders = req.headers();
      const resHeaders = resp.headers();

      console.log(`\n[${resp.status()}] ${req.method()} ${url.replace('http://127.0.0.1:5190', '')}`);
      console.log(`  Cookie: ${(reqHeaders['cookie'] || 'NONE').substring(0, 80)}...`);
      if (reqHeaders['x-xsrf-token']) {
        console.log(`  X-XSRF-TOKEN: ${reqHeaders['x-xsrf-token'].substring(0, 30)}...`);
      }
      if (resHeaders['set-cookie']) {
        console.log(`  Set-Cookie: ${resHeaders['set-cookie'].substring(0, 100)}...`);
      }
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

    console.log('\n>>> F5 #1 <<<');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    console.log(`URL: ${page.url()}`);

    console.log('\n>>> F5 #2 <<<');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    console.log(`URL: ${page.url()}`);

    console.log('\n>>> F5 #3 <<<');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    console.log(`URL: ${page.url()}`);

  } catch (err) {
    console.error('ERROR:', err.message);
  } finally {
    await browser.close();
  }
}

runTest();