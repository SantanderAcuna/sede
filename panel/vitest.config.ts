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
     * Cobertura tras auditoría 3-skills: stmt 66.18% / br 49.87% / fn 61.2% / ln 69.63%.
     * Trinquete: los umbrales superan lo actual para garantizar mejora continua.
     * Meta: stmt ≥67% / br ≥50% / fn ≥62% / ln ≥70%.
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.d.ts', 'src/main.ts'],
      thresholds: {
        statements: 67,
        branches: 50,
        functions: 62,
        lines: 70,
      },
    },
  },
})
