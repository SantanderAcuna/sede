// Test usando Performance API y DevTools para ver qué pasa realmente

import { chromium } from 'playwright';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  const networkLog = [];

  page.on('response', async resp => {
    const url = resp.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      // Intentar obtener los headers del request de varias formas
      let cookies = 'unknown';

      try {
        // En Playwright, no podemos obtener el header Cookie directamente,
        // pero podemos ver si la respuesta tiene Set-Cookie
        const setCookie = resp.headers()['set-cookie'] || '';
        cookies = setCookie ? `Set-Cookie en respuesta: ${setCookie.substring(0, 100)}...` : 'no set-cookie en respuesta';
      } catch (e) {
        cookies = `error: ${e.message}`;
      }

      networkLog.push({
        url: url.replace('http://127.0.0.1:5190', ''),
        status: resp.status(),
        method: resp.request().method(),
        cookies,
      });
    }
  });

  try {
    await page.goto('http://127.0.0.1:5190/admin/acceso');
    await page.waitForLoadState('networkidle');

    await page.locator('input[type="email"]').first().fill('jose.acuna@santamarta.gov.co');
    await page.locator('input[type="password"]').first().fill('85154239');
    await page.locator('button[type="submit"]').first().click();
    await page.waitForURL(/\/admin\/?$/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    console.log('\n=== F5 #1 ===');
    networkLog.length = 0;
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    networkLog.forEach(e => console.log(`  [${e.status}] ${e.method} ${e.url}`));
    if (networkLog.some(e => e.status === 200)) console.log('  ✓ 200 OK');
    else console.log('  ✗ No 200 OK');

    console.log(`URL: ${page.url()}`);

    console.log('\n=== F5 #2 ===');
    networkLog.length = 0;
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    networkLog.forEach(e => console.log(`  [${e.status}] ${e.method} ${e.url}`));
    if (networkLog.some(e => e.status === 200)) console.log('  ✓ 200 OK');
    else console.log('  ✗ No 200 OK');

    console.log(`URL: ${page.url()}`);

  } catch (err) {
    console.error('ERROR:', err.message);
  } finally {
    await browser.close();
  }
}

runTest();