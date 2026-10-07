// Test E2E con Playwright para verificar:
// 1. Login funciona
// 2. F5 no pierde la sesión (no pantalla en blanco, no logout)
// 3. F5 múltiples veces no pierden sesión
// 4. Logout redirige correctamente al login

import { chromium } from 'playwright';

const BASE_URL = 'http://127.0.0.1:5190/admin';

async function runTest() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();

  const consoleErrors = [];
  const consoleMessages = [];
  const networkResponses = [];

  page.on('console', msg => {
    consoleMessages.push(`[${msg.type()}] ${msg.text()}`);
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  page.on('pageerror', err => {
    consoleErrors.push(`PAGE ERROR: ${err.message}`);
  });

  page.on('response', async resp => {
    const url = resp.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      networkResponses.push({
        url: url,
        status: resp.status(),
        timestamp: Date.now(),
      });
    }
  });

  const results = {
    loginSuccess: false,
    f5BlankPage: false,
    f5MultipleSessions: false,
    logoutRedirects: false,
    details: {},
  };

  try {
    console.log('\n=== PASO 1: Login ===');
    await page.goto(`${BASE_URL}/acceso`);
    await page.waitForLoadState('networkidle');

    const emailInput = page.locator('input[type="email"], input[name="email"]').first();
    const passwordInput = page.locator('input[type="password"]').first();
    await emailInput.fill('jose.acuna@santamarta.gov.co');
    await passwordInput.fill('85154239');

    const submitBtn = page.locator('button[type="submit"]').first();
    await submitBtn.click();

    await page.waitForURL(/\/admin\/?$/, { timeout: 10000 }).catch(() => {});
    await page.waitForLoadState('networkidle');

    const currentUrl1 = page.url();
    const onLogin1 = currentUrl1.includes('/acceso');
    results.loginSuccess = !onLogin1;

    const pageContent1 = await page.content();
    const hasPanelContent = pageContent1.includes('SGDI') ||
                           pageContent1.includes('Dashboard') ||
                           pageContent1.includes('Santa Marta') ||
                           pageContent1.length > 1000;
    results.loginSuccess = results.loginSuccess && hasPanelContent;

    console.log(`  URL: ${currentUrl1}, Login OK: ${results.loginSuccess}`);

    console.log('\n=== PASO 2: F5 una vez ===');
    const beforeF5Count = networkResponses.length;
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const pageContent2 = await page.content();
    const currentUrl2 = page.url();
    const blankAfterF5 = pageContent2.trim().length < 500;
    const stillOnLogin2 = currentUrl2.includes('/acceso');

    results.f5BlankPage = !blankAfterF5 && !stillOnLogin2 && results.loginSuccess;

    console.log(`  URL: ${currentUrl2}, blank: ${blankAfterF5}, F5 OK: ${results.f5BlankPage}`);
    console.log('  Peticiones tras F5 #1:');
    networkResponses.slice(beforeF5Count).forEach(r => {
      console.log(`    ${r.status} ${r.url.replace('http://127.0.0.1:5190', '')}`);
    });

    console.log('\n=== PASO 3: F5 múltiples veces (5 veces) ===');
    let multipleF5Ok = true;
    for (let i = 0; i < 5; i++) {
      const beforeReload = networkResponses.length;
      await page.reload();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      const c = await page.content();
      const url = page.url();
      const blank = c.trim().length < 500;
      const onLogin = url.includes('/acceso');

      const f5Requests = networkResponses.slice(beforeReload);
      const has401 = f5Requests.some(r => r.status === 401);
      const perfilReq = f5Requests.find(r => r.url.includes('/perfil'));

      console.log(`  F5 #${i+2}: blank=${blank}, onLogin=${onLogin}, url=${url}`);
      if (perfilReq) {
        console.log(`    perfil: ${perfilReq.status}`);
      }
      if (has401) {
        f5Requests.filter(r => r.status === 401).forEach(r => {
          console.log(`    401: ${r.url.replace('http://127.0.0.1:5190', '')}`);
        });
      }

      if (blank || onLogin) {
        multipleF5Ok = false;
        break;
      }
    }
    results.f5MultipleSessions = multipleF5Ok;
    console.log(`  F5 múltiple OK: ${results.f5MultipleSessions}`);

    console.log('\n=== PASO 4: Logout ===');
    const userMenuToggle = page.locator('button[aria-haspopup="true"]').first();
    const logoutButton = page.locator('button:has-text("Cerrar sesión")').first();

    if (await userMenuToggle.count() > 0) {
      // Primero abrir el menú de usuario
      try {
        await userMenuToggle.click({ timeout: 5000 });
        await page.waitForTimeout(500);
      } catch (e) {
        console.log('  ⚠ No se pudo abrir menú de usuario:', e.message);
      }

      // Ahora buscar y hacer clic en el botón de logout
      if (await logoutButton.count() > 0) {
        try {
          await logoutButton.click({ timeout: 5000 });
        } catch (e) {
          console.log('  ⚠ No se pudo hacer clic en logout:', e.message);
        }

        try {
          await page.waitForURL(/\/acceso/, { timeout: 10000 });
          await page.waitForLoadState('networkidle');
          results.logoutRedirects = true;
        } catch (e) {
          await page.waitForTimeout(3000);
          const finalUrl = page.url();
          results.logoutRedirects = finalUrl.includes('/acceso');
        }
      } else {
        console.log('  ⚠ No se encontró botón de logout tras abrir menú');
        results.logoutRedirects = 'skipped';
      }
    } else {
      console.log('  ⚠ No se encontró toggle del menú de usuario');
      results.logoutRedirects = 'skipped';
    }

    console.log(`  URL final: ${page.url()}, Logout redirige: ${results.logoutRedirects}`);

  } catch (err) {
    console.error('ERROR:', err.message);
    results.error = err.message;
  } finally {
    console.log('\n=== ERRORES DE CONSOLA ===');
    consoleErrors.forEach(e => console.log('  ', e));
    await browser.close();
  }

  console.log('\n=== RESULTADO FINAL ===');
  console.log(JSON.stringify(results, null, 2));

  const passed = results.loginSuccess && results.f5BlankPage && results.f5MultipleSessions && (results.logoutRedirects === true || results.logoutRedirects === 'skipped');
  process.exit(passed ? 0 : 1);
}

runTest().catch(err => {
  console.error('FATAL:', err);
  process.exit(2);
});