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
     * Cobertura actual ~57 % líneas / ~44 % funciones (71 tests).
     * Los umbrales se ajustan ligeramente por encima de lo actual para crear
     * un efecto trinquete: la cobertura puede mejorar pero no empeorar.
     * A medida que se agreguen tests para servicios, componentes y manejo
     * de errores, los valores subirán.
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.d.ts', 'src/main.ts'],
      thresholds: {
        statements: 53,
        branches: 35,
        functions: 43,
        lines: 56,
      },
    },
  },
})
