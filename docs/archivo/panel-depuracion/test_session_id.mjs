// Test: comparar session_id exacto entre login y perfil

import { chromium } from 'playwright';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    await page.goto('http://127.0.0.1:5190/admin/acceso');
    await page.waitForLoadState('networkidle');

    await page.locator('input[type="email"]').first().fill('jose.acuna@santamarta.gov.co');
    await page.locator('input[type="password"]').first().fill('85154239');
    await page.locator('button[type="submit"]').first().click();
    await page.waitForURL(/\/admin\/?$/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    const cookiesAfterLogin = await context.cookies();
    const sessionCookieLogin = cookiesAfterLogin.find(c => c.name.includes('session'));
    console.log('Session cookie tras login:');
    console.log(`  name: ${sessionCookieLogin.name}`);
    console.log(`  value: ${sessionCookieLogin.value.substring(0, 50)}...`);
    console.log(`  sameSite: ${sessionCookieLogin.sameSite}`);
    console.log(`  path: ${sessionCookieLogin.path}`);
    console.log(`  domain: ${sessionCookieLogin.domain}`);
    console.log(`  expires: ${sessionCookieLogin.expires}`);
    console.log(`  httpOnly: ${sessionCookieLogin.httpOnly}`);

    // F5 #1
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const cookiesAfterF5_1 = await context.cookies();
    const sessionCookieF5_1 = cookiesAfterF5_1.find(c => c.name.includes('session'));
    console.log('\nSession cookie tras F5 #1:');
    console.log(`  value: ${sessionCookieF5_1.value.substring(0, 50)}...`);
    console.log(`  MISMA: ${sessionCookieF5_1.value === sessionCookieLogin.value}`);

    // F5 #2
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const cookiesAfterF5_2 = await context.cookies();
    const sessionCookieF5_2 = cookiesAfterF5_2.find(c => c.name.includes('session'));
    console.log('\nSession cookie tras F5 #2:');
    console.log(`  value: ${sessionCookieF5_2.value.substring(0, 50)}...`);
    console.log(`  MISMA: ${sessionCookieF5_2.value === sessionCookieLogin.value}`);

    // Verificar cuántas sesiones hay en la BD
    console.log('\n=== Sesiones en BD ===');

  } catch (err) {
    console.error('ERROR:', err.message);
  } finally {
    await browser.close();
  }
}

runTest();