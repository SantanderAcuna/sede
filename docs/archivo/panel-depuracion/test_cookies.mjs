// Diagnóstico: inspeccionar cookies después de cada F5

import { chromium } from 'playwright';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Login
    await page.goto('http://127.0.0.1:5190/admin/acceso');
    await page.waitForLoadState('networkidle');

    await page.locator('input[type="email"]').first().fill('jose.acuna@santamarta.gov.co');
    await page.locator('input[type="password"]').first().fill('85154239');
    await page.locator('button[type="submit"]').first().click();

    await page.waitForURL(/\/admin\/?$/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    console.log('=== COOKIES TRAS LOGIN ===');
    let cookies = await context.cookies();
    cookies.forEach(c => {
      console.log(`  ${c.name} = ${c.value.substring(0, 30)}...`);
      console.log(`    domain=${c.domain}, httpOnly=${c.httpOnly}, sameSite=${c.sameSite}, expires=${c.expires}, secure=${c.secure}, path=${c.path}`);
    });

    // F5 #1
    console.log('\n=== F5 #1 ===');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    cookies = await context.cookies();
    console.log('  Cookies:');
    cookies.forEach(c => {
      console.log(`    ${c.name} = ${c.value.substring(0, 30)}...`);
    });

    // F5 #2
    console.log('\n=== F5 #2 ===');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    cookies = await context.cookies();
    console.log('  Cookies:');
    cookies.forEach(c => {
      console.log(`    ${c.name} = ${c.value.substring(0, 30)}...`);
    });

    // F5 #3
    console.log('\n=== F5 #3 ===');
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    cookies = await context.cookies();
    console.log('  Cookies:');
    cookies.forEach(c => {
      console.log(`    ${c.name} = ${c.value.substring(0, 30)}...`);
    });

  } catch (err) {
    console.error('ERROR:', err.message);
  } finally {
    await browser.close();
  }
}

runTest();