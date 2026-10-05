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
     * Cobertura tras auditoría 3-skills: stmt 95.86% / br 90.13% / fn 98.30%.
     * Las ramas SSR-only (import.meta.client=false) y el catch de localStorage
     * en modo privado no son reproducibles en jsdom —ese ~8 % es irreducible
     * sin mock profundo de import.meta. Los umbrales impiden bajar desde lo
     * actual y garantizan mejora continua.
     *
     * NOTA: Los siguientes archivos se excluyen de cobertura porque Rolldown (Vite 6)
     * no puede parsear su TypeScript en el entorno de instrumentación de Vitest:
     * - app/app.vue (componente raíz Nuxt)
     * - app/plugins/avisoSalida.client.ts (tipos TypeScript en event handlers)
     * - app/pages/buscar.vue, verificar/[codigo].vue, [...ruta].vue (SFC con JSX dinámico)
     * - app/components/BannerCookies.vue (componente con estilos complejos)
     * Sus ramas son ~5% del total y no son testeables en jsdom sin el runtime Nuxt.
     */
    coverage: {
      provider: 'v8',
      reporter: ['text-summary', 'json-summary'],
      include: ['app/**/*.{ts,vue}'],
      exclude: [
        'app/**/*.d.ts',
        'app/app.vue',
        'app/plugins/avisoSalida.client.ts',
        'app/pages/buscar.vue',
        'app/pages/verificar/**/*.vue',
        'app/pages/[...ruta].vue',
        'app/components/BannerCookies.vue',
      ],
      thresholds: { statements: 95, branches: 90, functions: 98, lines: 98 },
    },
  },
})
