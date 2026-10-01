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
    /*
     * Con las pruebas de los componentes y del enrutador, el panel pasa del 3,9 %
     * al **70,9 % de líneas** (66,8 % de sentencias): RNF-B1-043 se alcanza en
     * líneas. Como en el sitio, el umbral es un trinquete por debajo de lo medido
     * para que no baje sin que nadie lo note.
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.d.ts', 'src/main.ts'],
      thresholds: { statements: 66, branches: 57, functions: 59, lines: 70 },
    },
  },
})
