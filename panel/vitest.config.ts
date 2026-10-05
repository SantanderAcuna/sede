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
     * Cobertura tras auditoría 3-skills: ~66% líneas / ~45% ramas.
     * Trinquete: los umbrales superan lo actual para garantizar mejora continua.
     * Meta final: líneas ≥70% / ramas ≥57% / funciones ≥59% / statements ≥66%.
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.d.ts', 'src/main.ts'],
      thresholds: {
        statements: 62,
        branches: 45,
        functions: 56,
        lines: 66,
      },
    },
  },
})
