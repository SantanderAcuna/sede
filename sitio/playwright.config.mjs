/**
 * Proyectos de Playwright para verificar la Sede en varios navegadores
 * (T-29, RNF-B1-033, RNF-B3-005; AC-29.1).
 *
 * Los motores:
 *   chromium  Chrome        firefox  Firefox        webkit  Safari (motor WebKit)
 *   msedge    Edge (canal `msedge`; hace falta tenerlo instalado)
 *
 * Y cada uno en versión escritorio y en versión teléfono, para AC-29.3
 * (controles ≥ 44 × 44 px con emulación móvil en cada motor).
 *
 * **Límites que conviene saber**
 *  - `webkit` es el motor de Safari, no Safari: no hay las particularidades de
 *    iOS (barra de direcciones que cambia el alto útil, zoom al enfocar campos de
 *    menos de 16 px, etc.). Para eso hace falta un dispositivo real.
 *  - Firefox no admite `isMobile`: su proyecto móvil es ancho estrecho + táctil.
 *
 * Uso:
 *   BASE_URL=https://… npx playwright test                 todos los proyectos
 *   BASE_URL=https://… npx playwright test --project=webkit
 *   BASE_URL=https://… npx playwright test --project=msedge   (con Edge instalado)
 *
 * Los scripts `tests/*.mjs` (diseno, movil, kit-ui, cookies…) usan el mismo
 * reparto de motores con la variable `NAVEGADOR` (ver `tests/ayudas/motor.mjs`).
 */
import { defineConfig, devices } from '@playwright/test'

const BASE_URL = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/+$/, '')

/** Teléfono con el que se mide: 390 × 844, como en `tests/movil.mjs`. */
const TELEFONO = { viewport: { width: 390, height: 844 }, hasTouch: true }

export default defineConfig({
  testDir: './tests/e2e',
  testMatch: '**/*.spec.mjs',
  fullyParallel: true,
  // Un reintento absorbe la latencia de la red hacia staging; si falla dos veces
  // seguidas es un fallo.
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['json', { outputFile: process.env.RESULTADOS ?? 'resultados-navegadores.json' }]],
  use: { baseURL: BASE_URL, locale: 'es-CO', trace: 'retain-on-failure' },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    { name: 'msedge', use: { ...devices['Desktop Edge'], channel: 'msedge' } },

    { name: 'chromium-movil', use: { ...devices['Desktop Chrome'], ...TELEFONO, isMobile: true } },
    { name: 'firefox-movil', use: { ...devices['Desktop Firefox'], ...TELEFONO } },
    { name: 'webkit-movil', use: { ...devices['Desktop Safari'], ...TELEFONO, isMobile: true } },
    { name: 'msedge-movil', use: { ...devices['Desktop Edge'], channel: 'msedge', ...TELEFONO, isMobile: true } },
  ],
})
