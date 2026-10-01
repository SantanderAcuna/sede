import { fileURLToPath } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

/**
 * Configuración de las pruebas unitarias del panel.
 *
 * El entorno es `jsdom` porque el enrutador se construye con historial del
 * navegador y los almacenes de Pinia necesitan un `window` donde vivir. Se
 * aplica el plugin de Vue para poder probar componentes el día que haga falta.
 */
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      // El mismo alias que en `vite.config.ts`: si sólo estuviera allí, las
      // pruebas no resolverían los imports `@/…` del código.
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  test: {
    environment: 'jsdom',
    include: ['tests/**/*.test.ts'],
    /* Ver la nota del `vitest.config.ts` del sitio: el umbral es un trinquete
       contra la regresión, no la cifra de RNF-B1-043, que hoy no se alcanza. */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.d.ts', 'src/main.ts'],
      thresholds: { statements: 3, branches: 0, functions: 4, lines: 3 },
    },
  },
})
