import { defineConfig } from 'vitest/config'

/**
 * Configuración de las pruebas unitarias del sitio.
 *
 * `define` convierte `import.meta.client` en `true`. Es una constante que Nuxt
 * inyecta al compilar y que fuera de Nuxt no existe: sin esto, todas las ramas
 * de cliente de los composables quedarían apagadas y no habría nada que probar.
 *
 * No se usa `@nuxt/test-utils` a propósito: lo que se prueba aquí es **lógica
 * pura** —la validez del consentimiento de cookies, la clasificación de enlaces
 * externos y las invariantes de la configuración de rutas—, y levantar el
 * framework entero para eso costaría mucho más de lo que aporta. Las pruebas que
 * sí necesitan el sitio en marcha son la puerta de accesibilidad
 * (`tests/accesibilidad.mjs`), que arranca el servidor compilado de verdad.
 */
export default defineConfig({
  define: { 'import.meta.client': 'true' },
  test: {
    environment: 'jsdom',
    include: ['tests/**/*.test.ts'],
    /*
     * La cobertura se mide sobre el código del producto, no sobre las pruebas ni
     * sobre lo generado. RNF-B1-043 pide un 70 %: hoy no se alcanza y la cifra
     * está publicada en la auditoría; lo que impide esta configuración es que
     * baje sin que nadie lo note (el umbral es un trinquete, no el objetivo).
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['app/**/*.{ts,vue}'],
      exclude: ['app/**/*.d.ts', 'app/config/**'],
      thresholds: { statements: 40, branches: 36, functions: 44, lines: 45 },
    },
  },
})
